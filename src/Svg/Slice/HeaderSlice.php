<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Config\Header;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Component\Stagger;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** Title bar, "$ whoami", the glitching name and the bio lines. Opens the frame. */
final readonly class HeaderSlice implements Slice
{
    private const int H = 360;

    public function __construct(private Header $header) {}

    public function path(): string
    {
        return 'header.svg';
    }

    public function alt(): string
    {
        return "{$this->header->title} — {$this->header->description}";
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
        $hd = $this->header;
        $e = Markup::esc(...);
        $n = Markup::num(...);
        [$x, $fl, $fr, $m, $h] = [G::X, G::FL, G::FR, G::M, self::H];
        $cy = Color::Cyan->value;
        $green = Color::Green->value;
        $magenta = Color::Magenta->value;
        $name = $e($hd->name);

        $css = <<<'CSS'
            @keyframes type{from{width:0}}
            @keyframes flicker{0%{opacity:0}10%{opacity:1}14%{opacity:.2}22%{opacity:1}30%{opacity:.4}40%,100%{opacity:1}}
            @keyframes gm{0%,92%,100%{transform:translate(0,0)}93%{transform:translate(5px,-1px)}95%{transform:translate(-3px,1px)}97%{transform:translate(2px,0)}}
            @keyframes gc{0%,92%,100%{transform:translate(0,0)}93%{transform:translate(-5px,1px)}95%{transform:translate(4px,-1px)}97%{transform:translate(-2px,0)}}
            .typing{animation:type .7s steps(8) .3s both}
            .name{animation:flicker .9s linear 1.1s both}
            .gm{animation:gm 6s linear 2s infinite}.gc{animation:gc 6s linear 2s infinite}
            CSS;
        $defs = <<<SVG
            <pattern id="scan" width="4" height="3" patternUnits="userSpaceOnUse"><rect width="4" height="1" fill="#000" fill-opacity=".2"/></pattern>
            <filter id="tglow" x="-5%" y="-40%" width="110%" height="180%"><feGaussianBlur stdDeviation="9"/></filter>
            <filter id="sglow" x="-20%" y="-60%" width="140%" height="220%"><feGaussianBlur stdDeviation="3"/></filter>
            <clipPath id="typeclip"><rect class="typing" x="$x" y="80" width="140" height="30"/></clipPath>
            SVG;

        $rows = array_map(
            fn(string $line) => '<text class="fg" x="' . G::X . '" y="{y}"><tspan class="cy">&gt;&gt;</tspan> ' . $e($line),
            $hd->lines,
        );
        if ($hd->highlight !== null) {
            $rows[array_key_last($rows)] .= '<tspan class="cy" font-weight="700">' . $e($hd->highlight) . '</tspan>';
        }
        $rows = array_map(fn(string $r) => "$r</text>", $rows);
        [$descLines] = Stagger::rows($rows, 218, 24, 1.75, .25);

        $dotx = $n($fr - 20 - Markup::len($hd->barRight) * 8.2 - 16);
        $fw = $fr - $fl;
        $body = <<<SVG
            <rect x="$fl" y="$m" width="$fw" height="34" fill="$cy" fill-opacity=".08"/>
            <line x1="$fl" y1="{$n($m + 34)}" x2="$fr" y2="{$n($m + 34)}" stroke="$cy" stroke-opacity=".5"/>
            <text x="{$n($fl + 16)}" y="{$n($m + 22)}" letter-spacing="1" class="cy" style="font-size:12px">{$e($hd->barLeft)}</text>
            <text x="{$n($fr - 20)}" y="{$n($m + 22)}" letter-spacing="1" class="dim" text-anchor="end" style="font-size:12px">{$e($hd->barRight)}</text>
            <circle class="dot" cx="$dotx" cy="{$n($m + 18)}" r="4" fill="$green"/>
            <circle class="dot" cx="$dotx" cy="{$n($m + 18)}" r="4" fill="$green" filter="url(#sglow)"/>
            <g clip-path="url(#typeclip)"><text x="$x" y="102" class="dim"><tspan class="gr">$</tspan> whoami</text></g>
            <g class="name" font-weight="800" letter-spacing="2" style="font-size:56px">
            <text x="$x" y="172" fill="$cy" opacity=".55" filter="url(#tglow)" style="font-size:56px">$name</text>
            <g class="gm"><text x="{$n($x + 3)}" y="172" fill="$magenta" opacity=".75" style="font-size:56px">$name</text></g>
            <g class="gc"><text x="{$n($x - 3)}" y="172" fill="$cy" opacity=".85" style="font-size:56px">$name</text></g>
            <text x="$x" y="172" fill="#f0fbff" style="font-size:56px">$name</text>
            </g>
            $descLines
            <g class="ln" style="animation-delay:2.8s">
            <text x="$x" y="304" class="gr">$</text>
            <rect class="cursor" x="{$n($x + 18)}" y="291" width="10" height="17" fill="$cy"/>
            <rect class="cursor" x="{$n($x + 18)}" y="291" width="10" height="17" fill="$cy" filter="url(#sglow)"/>
            </g>
            <rect x="$fl" y="{$n($m + 35)}" width="$fw" height="{$n($h - $m - 35)}" fill="url(#scan)"/>
            SVG;

        return $frames->full($h, $body, $hd->title, $hd->description,
            text: $hd->barLeft . $hd->barRight . $hd->name . '$ whoami>>',
            top: true, css: $css, defs: $defs, weights: [400, 700, 800]);
    }
}
