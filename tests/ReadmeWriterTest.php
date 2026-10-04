<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Tests;

use JuanPlaza\Profile\Readme\ReadmeWriter;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Slice\Slice;
use PHPUnit\Framework\TestCase;

final class ReadmeWriterTest extends TestCase
{
    private function slice(string $path, string $alt, ?string $href = null, float $width = 100): Slice
    {
        return new readonly class($path, $alt, $href, $width) implements Slice {
            public function __construct(private string $p, private string $a, private ?string $h, private float $w) {}

            public function path(): string
            {
                return $this->p;
            }

            public function render(Frames $frames): string
            {
                return '';
            }

            public function alt(): string
            {
                return $this->a;
            }

            public function href(): ?string
            {
                return $this->h;
            }

            public function width(): float
            {
                return $this->w;
            }
        };
    }

    public function testRowsAreFilledUpToFullWidth(): void
    {
        $readme = new ReadmeWriter('./profile/assets')->build([
            $this->slice('header.svg', 'Header'),
            $this->slice('links/a.svg', 'A', 'https://a.example', 25),
            $this->slice('links/b.svg', 'B', 'https://b.example', 25),
            $this->slice('links/c.svg', 'C', 'https://c.example', 25),
            $this->slice('links/d.svg', 'D', 'mailto:d@example.com', 25),
            $this->slice('card-x.svg', 'X', 'https://x.example', 50),
            $this->slice('card-y.svg', 'Y', 'https://y.example', 50),
            $this->slice('footer.svg', 'Bye'),
        ]);
        $lines = explode("\n", trim($readme));

        $this->assertSame('<p align="center">', $lines[0]);
        $this->assertSame('</p>', array_last($lines));
        $this->assertCount(6, $lines);   // <p>, header, links row, cards row, footer, </p>
        $this->assertSame('<img src="./profile/assets/header.svg" width="100%" align="top" alt="Header">', $lines[1]);
        $this->assertSame(4, substr_count($lines[2], '<a href='));
        $this->assertStringContainsString('<a href="mailto:d@example.com"><img src="./profile/assets/links/d.svg" width="25%"', $lines[2]);
        $this->assertSame(2, substr_count($lines[3], 'width="50%"'));
    }

    public function testFractionalWidthsAndEscaping(): void
    {
        $readme = new ReadmeWriter('./profile/assets')->build([
            $this->slice('links/a.svg', 'Say "hi" & <wave>', 'https://x.example/?a=1&b=2', 100 / 8),
        ]);
        $this->assertStringContainsString('width="12.5%"', $readme);
        $this->assertStringContainsString('href="https://x.example/?a=1&amp;b=2"', $readme);
        $this->assertStringContainsString('alt="Say &quot;hi&quot; &amp; &lt;wave&gt;"', $readme);
    }
}
