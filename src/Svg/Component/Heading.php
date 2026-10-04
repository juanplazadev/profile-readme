<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Component;

use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Geometry as G;

/** "~/name" with a glowing underline and the "// 03" section counter on the right. */
final class Heading
{
    #[\NoDiscard]
    public static function render(int $y, string $name, string $counter): string
    {
        $x = G::X;
        $r = G::RIGHT;
        $cy = Color::Cyan->value;
        $n = Markup::esc($name);
        $c = Markup::esc($counter);
        $ly = $y + 14;
        $lx = $x + 120;
        return <<<SVG
            <text x="$x" y="$y" font-weight="700" fill="$cy" filter="url(#g)" opacity=".8" style="font-size:20px">~/</text>
            <text x="$x" y="$y" font-weight="700" style="font-size:20px"><tspan class="cy">~/</tspan><tspan class="wh">$n</tspan></text>
            <text x="$r" y="$y" text-anchor="end" letter-spacing="2" fill="#6e7681" style="font-size:12px">$c</text>
            <line x1="$x" y1="$ly" x2="$r" y2="$ly" stroke="$cy" stroke-opacity=".4"/>
            <line x1="$x" y1="$ly" x2="$lx" y2="$ly" stroke="$cy" stroke-width="2"/>
            <line x1="$x" y1="$ly" x2="$lx" y2="$ly" stroke="$cy" stroke-width="3" filter="url(#g)"/>
            SVG;
    }

    /** The "$ command" prompt line under a heading. */
    #[\NoDiscard]
    public static function prompt(string $command, string $comment = '', float $delay = .15, int $y = 96): string
    {
        $x = G::X;
        $cmd = Markup::esc($command);
        $note = $comment === '' ? '' : ' <tspan fill="#484f58"># ' . Markup::esc($comment) . '</tspan>';
        $d = Markup::num($delay);
        return "<g class=\"ln\" style=\"animation-delay:{$d}s\"><text x=\"$x\" y=\"$y\" class=\"dim\"><tspan class=\"gr\">$</tspan> $cmd$note</text></g>";
    }
}
