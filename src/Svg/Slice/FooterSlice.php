<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** "$ exit" — closes the frame. */
final readonly class FooterSlice implements Slice
{
    public function __construct(private string $handle) {}

    public function path(): string
    {
        return 'footer.svg';
    }

    public function alt(): string
    {
        return 'Connection closed.';
    }

    public function href(): ?string
    {
        return null;
    }

    public function width(): float
    {
        return 100;
    }

    public function render(Frames $frames): string
    {
        $x = G::X;
        $handle = Markup::esc($this->handle);
        $body = <<<SVG
            <text x="$x" y="30" class="dim"><tspan class="gr">$</tspan> exit</text>
            <text x="$x" y="52" class="dim">connection to <tspan class="cy">$handle</tspan> closed. <tspan fill="#484f58">// EOF</tspan></text>
            SVG;
        return $frames->full(80, $body, 'End of profile', 'Connection closed.', text: '', bottom: true);
    }
}
