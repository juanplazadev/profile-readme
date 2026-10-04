<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Component;

use JuanPlaza\Profile\Config\LinkIcon;
use JuanPlaza\Profile\Svg\Color;

/** Brand icons from resources/icons.json, drawn in cyan. */
final readonly class Icons
{
    /** @param array<string, string> $paths icon key => 24×24 path data */
    public function __construct(private array $paths) {}

    public static function fromFile(string $file): self
    {
        return new self(json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR));
    }

    #[\NoDiscard]
    public function markup(LinkIcon $icon, float $x, float $y, int $size = 18): string
    {
        $n = Markup::num(...);
        $cy = Color::Cyan->value;
        if ($icon->isGlyph()) {
            return "<rect x=\"{$n($x)}\" y=\"{$n($y)}\" width=\"$size\" height=\"$size\" rx=\"3\" fill=\"$cy\"/>"
                . "<text x=\"{$n($x + $size / 2)}\" y=\"{$n($y + $size - 4.5)}\" text-anchor=\"middle\" font-weight=\"700\" fill=\"#03040a\" style=\"font-size:12px\">in</text>";
        }
        $d = $this->paths[$icon->value] ?? throw new \OutOfBoundsException("No icon \"{$icon->value}\" in icons.json");
        return "<path transform=\"translate({$n($x)} {$n($y)}) scale({$n($size / 24)})\" d=\"$d\" fill=\"$cy\"/>";
    }
}
