<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Config\Project;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Frame\Side;

/** A project card, drawn as the left or right half of a README row. */
final readonly class ProjectCardSlice implements Slice
{
    private const int H = 200;
    private const string STAR = 'M10 0l2.9 6.6 7.1.6-5.4 4.7 1.6 7L10 15.2 3.8 18.9l1.6-7L0 7.2l7.1-.6z';
    private const int WRAP = 43;

    public function __construct(
        private Project $project,
        private Side $side,
        private float $delay,
        private int $stars,
    ) {}

    public function path(): string
    {
        return "card-{$this->project->slug}.svg";
    }

    public function alt(): string
    {
        $p = $this->project;
        return "{$p->name} — " . mb_strtolower($p->tag) . ". {$p->desc} " . str_replace(' · ', ', ', $p->stack) . '.';
    }

    public function href(): ?string
    {
        return $this->project->url;
    }

    public function width(): float
    {
        return 50;
    }

    public function render(Frames $frames): string
    {
        $p = $this->project;
        $n = Markup::num(...);
        $e = Markup::esc(...);
        $cy = Color::Cyan->value;
        $tagc = $p->tagColor->value;

        $x0 = $this->side === Side::Left ? 52 : 10;   // card box, 378 wide, 20px gutter between the two cards
        [$cw, $y0, $ch, $pad, $cut] = [378, 12, 176, 18, 16];
        $tx = $x0 + $pad;
        $box = "M$x0 {$y0}H" . ($x0 + $cw - $cut) . 'L' . ($x0 + $cw) . ' ' . ($y0 + $cut) . 'V' . ($y0 + $ch)
            . 'H' . ($x0 + $cut) . "L$x0 " . ($y0 + $ch - $cut) . 'Z';

        $lines = explode("\n", wordwrap($p->desc, self::WRAP, "\n", true));
        if (count($lines) > 3) {
            throw new \LengthException("Project \"{$p->name}\": description wraps to " . count($lines) . ' lines at ' . self::WRAP . ' chars; three fit');
        }
        $desc = implode("\n", array_map(
            fn(int $i, string $l) => "<text x=\"$tx\" y=\"" . (100 + $i * 20) . "\" class=\"fg\" style=\"font-size:13px\">{$e($l)}</text>",
            array_keys($lines), $lines,
        ));
        $tagW = Markup::len($p->tag) * 7.6 + 16;
        $titleW = Markup::len($p->name) * 10.2;
        $sx = $x0 + $cw - $pad;
        $stars = (string) $this->stars;
        $starX = $sx - strlen($stars) * 7.2 - 20;
        $star = self::STAR;
        $d = sprintf('%.2f', $this->delay);

        $body = <<<SVG
            <g class="ln" style="animation-delay:{$d}s">
            <path d="$box" fill="$cy" fill-opacity=".035"/>
            <path d="$box" fill="none" stroke="$cy" stroke-opacity=".4"/>
            <path d="M{$n($x0 + $cw - $cut)} {$y0}L{$n($x0 + $cw)} {$n($y0 + $cut)}" stroke="$cy" stroke-width="2"/>
            <text x="$tx" y="{$n($y0 + 34)}" font-weight="700" fill="$cy" filter="url(#g)" opacity=".6" style="font-size:17px">{$e($p->name)}</text>
            <text x="$tx" y="{$n($y0 + 34)}" font-weight="700" class="cy" style="font-size:17px">{$e($p->name)}</text>
            <path d="M{$n($tx + $titleW + 11)} {$n($y0 + 31)}l8-8M{$n($tx + $titleW + 13)} {$n($y0 + 23)}h6v6" fill="none" stroke="$cy" stroke-width="1.6"/>
            <rect x="$tx" y="{$n($y0 + 46)}" width="{$n($tagW)}" height="18" fill="none" stroke="$tagc" stroke-opacity=".8"/>
            <text x="{$n($tx + 8)}" y="{$n($y0 + 59)}" letter-spacing="1" fill="$tagc" style="font-size:11px">{$e($p->tag)}</text>
            $desc
            <text x="$tx" y="{$n($y0 + 158)}" class="dim" style="font-size:12px">{$e($p->stack)}</text>
            <path transform="translate({$n($starX)} 158) scale(.55)" d="$star" fill="#e3b341"/>
            <text x="$sx" y="{$n($y0 + 158)}" text-anchor="end" class="dim" style="font-size:12px">$stars</text>
            </g>
            SVG;
        $stack = str_replace(' · ', ', ', $p->stack);
        return $frames->half(self::H, $this->side, $body, $p->name,
            "{$p->name}: {$p->desc} Built with $stack. $stars stars.", text: '');
    }
}
