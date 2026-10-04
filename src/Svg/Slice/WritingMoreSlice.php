<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** ">> read all articles on DEV Community ↗" */
final readonly class WritingMoreSlice implements Slice
{
    public function __construct(private string $url) {}

    public function path(): string
    {
        return 'writing/all-articles.svg';
    }

    public function alt(): string
    {
        return 'Read all articles on DEV Community';
    }

    public function href(): ?string
    {
        return $this->url;
    }

    public function width(): float
    {
        return 100;
    }

    public function render(Frames $frames): string
    {
        $n = Markup::num(...);
        $x = G::X;
        $cy = Color::Cyan->value;
        $body = <<<SVG
            <g class="ln" style="animation-delay:.7s">
            <text x="$x" y="26" style="font-size:13px"><tspan class="gr">&gt;&gt;</tspan><tspan class="cy"> read all articles on DEV Community</tspan></text>
            <path d="M{$n($x + 298)} 26l8-8M{$n($x + 300)} 18h6v6" fill="none" stroke="$cy" stroke-width="1.5"/>
            </g>
            SVG;
        return $frames->full(40, $body, 'All articles', 'Read all articles on DEV Community', text: '');
    }
}
