<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Config\StackCategory;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Heading;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** One row of chips per stack category. */
final readonly class StackSlice implements Slice
{
    private const float CHAR_W = 0.6 * 13;   // monospace advance at 13px

    /** @param list<StackCategory> $stack */
    public function __construct(
        private array $stack,
        private string $counter,
    ) {}

    public function path(): string
    {
        return 'stack.svg';
    }

    public function alt(): string
    {
        return 'Tech stack. ' . implode(' ', array_map(
            fn(StackCategory $c) => ucfirst($c->name) . ': ' . implode(', ', $c->items) . '.',
            $this->stack,
        ));
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
        $n = Markup::num(...);
        $e = Markup::esc(...);
        $cy = Color::Cyan->value;
        $x0 = G::X;
        $parts = [Heading::render(44, 'stack', $this->counter), Heading::prompt('scan --loadout --top-level')];

        $y = 126;
        foreach ($this->stack as $r => $cat) {
            $parts[] = '<g class="ln" style="animation-delay:' . sprintf('%.2f', .25 + $r * .1) . 's">';
            $parts[] = "<text x=\"$x0\" y=\"{$n($y + 20)}\" class=\"dim\" style=\"font-size:13px\">{$e($cat->name)}</text>";
            $parts[] = "<text x=\"{$n($x0 + 104)}\" y=\"{$n($y + 20)}\" class=\"cy\" style=\"font-size:13px\">›</text>";
            $x = $x0 + 124;
            foreach ($cat->items as $item) {
                $w = Markup::len($item) * self::CHAR_W + 26;
                if ($x + $w > G::FR) {
                    throw new \LengthException("Stack row \"{$cat->name}\" is too wide at \"$item\"; split it into two categories");
                }
                $parts[] = "<rect x=\"{$n($x)}\" y=\"$y\" width=\"{$n($w)}\" height=\"30\" fill=\"$cy\" fill-opacity=\".06\" stroke=\"$cy\" stroke-opacity=\".55\"/>";
                $parts[] = "<path d=\"M{$n($x)} {$n($y + 8)}V{$y}H{$n($x + 8)}\" fill=\"none\" stroke=\"$cy\" stroke-width=\"2\"/>";
                $parts[] = "<text x=\"{$n($x + 13)}\" y=\"{$n($y + 20)}\" font-weight=\"700\" class=\"cy\" style=\"font-size:13px\">{$e($item)}</text>";
                $x += $w + 10;
            }
            $parts[] = '</g>';
            $y += 44;
        }

        return $frames->full(Markup::up40($y + 10), implode("\n", $parts), 'Tech stack', $this->alt(), text: '');
    }
}
