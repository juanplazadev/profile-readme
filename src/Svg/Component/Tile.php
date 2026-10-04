<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Component;

use JuanPlaza\Profile\Svg\Color;

/** A stat tile: small label, big glowing value, optional caption. */
final class Tile
{
    #[\NoDiscard]
    public static function render(float $x, float $y, float $w, float $h, string $label, string $value, string $sub, float $delay, bool $pending = false): string
    {
        $n = Markup::num(...);
        $e = Markup::esc(...);
        $cy = Color::Cyan->value;
        $vc = $pending ? '#484f58' : $cy;
        $glow = $pending ? '' : "<text x=\"{$n($x + 16)}\" y=\"{$n($y + 50)}\" font-weight=\"700\" fill=\"$cy\" filter=\"url(#g)\" opacity=\".55\" style=\"font-size:30px\">{$e($value)}</text>";
        $d = sprintf('%.2f', $delay);
        return <<<SVG
            <g class="ln" style="animation-delay:{$d}s">
            <rect x="{$n($x)}" y="{$n($y)}" width="{$n($w)}" height="{$n($h)}" fill="$cy" fill-opacity=".035" stroke="$cy" stroke-opacity=".35"/>
            <path d="M{$n($x)} {$n($y + 12)}V{$n($y)}H{$n($x + 12)}" fill="none" stroke="$cy" stroke-width="2"/>
            <text x="{$n($x + 16)}" y="{$n($y + 22)}" letter-spacing="1.5" class="dim" style="font-size:10.5px">{$e($label)}</text>
            $glow<text x="{$n($x + 16)}" y="{$n($y + 50)}" font-weight="700" fill="$vc" style="font-size:30px">{$e($value)}</text>
            <text x="{$n($x + 16)}" y="{$n($y + $h - 12)}" fill="#6e7681" style="font-size:12px">{$e($sub)}</text>
            </g>
            SVG;
    }
}
