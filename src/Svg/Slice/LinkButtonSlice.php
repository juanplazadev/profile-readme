<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Config\Link;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Icons;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/**
 * One button in the links row. Each is its own image (so each can be its own <a>), but the
 * buttons are spaced to line up with the text column across the whole row.
 */
final readonly class LinkButtonSlice implements Slice
{
    private const int H = 80;
    private const int GAP = 39;

    public function __construct(
        private Link $link,
        private int $index,
        private int $count,
        private Icons $icons,
    ) {}

    public function path(): string
    {
        return "links/{$this->link->file}.svg";
    }

    public function alt(): string
    {
        return $this->link->label;
    }

    public function href(): ?string
    {
        return $this->link->url;
    }

    public function width(): float
    {
        return 100 / $this->count;
    }

    public function render(Frames $frames): string
    {
        $l = $this->link;
        $n = Markup::num(...);
        $e = Markup::esc(...);
        $cy = Color::Cyan->value;

        $seg = intdiv(G::W, $this->count);
        $btnW = (G::RIGHT - G::X - ($this->count - 1) * self::GAP) / $this->count;
        $lx = G::X + $this->index * ($btnW + self::GAP) - $seg * $this->index;
        [$by, $bh, $cut] = [12, 56, 12];
        $box = "M{$n($lx)} {$by}H{$n($lx + $btnW - $cut)}L{$n($lx + $btnW)} {$n($by + $cut)}V{$n($by + $bh)}H{$n($lx)}Z";
        $icon = $this->icons->markup($l->icon, $lx + 12, $by + 11);
        $delay = sprintf('%.2f', .25 + $this->index * .08);

        $body = <<<SVG
            <g class="ln" style="animation-delay:{$delay}s">
            <path d="$box" fill="$cy" fill-opacity=".05"/>
            <path d="$box" fill="none" stroke="$cy" stroke-opacity=".55"/>
            <path d="M{$n($lx + $btnW - $cut)} {$by}L{$n($lx + $btnW)} {$n($by + $cut)}" stroke="$cy" stroke-width="2"/>
            <g filter="url(#g)" opacity=".5">$icon</g>
            $icon
            <text x="{$n($lx + 38)}" y="{$n($by + 25)}" font-weight="700" class="cy" style="font-size:13px">{$e($l->label)}</text>
            <text x="{$n($lx + 12)}" y="{$n($by + 46)}" class="dim" style="font-size:10px">{$e($l->handle)}</text>
            </g>
            SVG;
        return $frames->segment(self::H, $this->index, $this->count, $body, $l->label, "{$l->label}: {$l->url}", text: '');
    }
}
