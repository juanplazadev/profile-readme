<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Font;

/**
 * Builds @font-face rules with JetBrains Mono subset to just the glyphs a slice shows,
 * using HarfBuzz's hb-subset and Google's woff2_compress (both installed in the image).
 *
 * Both tools are deterministic, so the same text always yields byte-identical fonts and
 * an unchanged profile produces no commit.
 */
final class FontSubsetter
{
    /** @var array<string, string> cache key => base64 woff2 */
    private array $cache = [];

    public function __construct(
        private readonly string $fontDir,
        private readonly string $hbSubset = 'hb-subset',
        private readonly string $woff2Compress = 'woff2_compress',
    ) {}

    public static function fromEnvironment(): self
    {
        return new self(getenv('FONT_DIR') ?: '/opt/fonts');
    }

    /** @param list<int> $weights */
    #[\NoDiscard]
    public function fontFaces(string $text, array $weights = [400, 700]): string
    {
        $glyphs = $this->glyphs($text . '0123456789');
        return implode('', array_map(
            fn(int $w) => "@font-face{font-family:'JBM';font-weight:$w;src:url(data:font/woff2;base64,"
                . $this->subset($w, $glyphs) . ") format('woff2')}",
            $weights,
        ));
    }

    /** Unique characters, sorted, so the cache key and the subset don't depend on order. */
    private function glyphs(string $text): string
    {
        $chars = array_unique(mb_str_split($text));
        sort($chars, SORT_STRING);
        return implode('', array_filter($chars, fn(string $c) => $c !== "\n" && $c !== "\r" && $c !== "\t"));
    }

    private function subset(int $weight, string $glyphs): string
    {
        $key = $weight . ':' . $glyphs;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $src = "{$this->fontDir}/jetbrains-mono-latin-$weight-normal.ttf";
        if (!is_file($src)) {
            throw new \RuntimeException("Missing font $src (run inside the Docker image, or set FONT_DIR to a folder of TTFs)");
        }

        $tmp = sys_get_temp_dir() . '/jbm-' . bin2hex(random_bytes(6));
        mkdir($tmp);
        try {
            file_put_contents("$tmp/text.txt", $glyphs);
            $this->run([$this->hbSubset, $src, "--text-file=$tmp/text.txt", '--layout-features-=*', "--output-file=$tmp/font.ttf"]);
            $this->run([$this->woff2Compress, "$tmp/font.ttf"]);
            return $this->cache[$key] = base64_encode((string) file_get_contents("$tmp/font.woff2"));
        } finally {
            array_map(unlink(...), glob("$tmp/*") ?: []);
            rmdir($tmp);
        }
    }

    /** @param list<string> $cmd */
    private function run(array $cmd): void
    {
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($proc === false) {
            throw new \RuntimeException("Could not start {$cmd[0]}");
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (($code = proc_close($proc)) !== 0) {
            throw new \RuntimeException("{$cmd[0]} exited with $code: " . trim($stderr ?: $stdout));
        }
    }
}
