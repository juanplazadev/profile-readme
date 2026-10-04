<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** One "date › title  ♥ n  ▭ n  ↗" line in the writing section. */
final readonly class ArticleRowSlice implements Slice
{
    private const string HEART = 'M8 14.2 6.9 13.2C3 9.7.5 7.4.5 4.6.5 2.3 2.3.5 4.6.5c1.3 0 2.5.6 3.4 1.6C8.9 1.1 10.1.5 11.4.5c2.3 0 4.1 1.8 4.1 4.1 0 2.8-2.5 5.1-6.4 8.6z';
    private const string BUBBLE = 'M1.5 1.5h13v9h-7l-3.5 3v-3h-2.5z';
    private const int MAX_TITLE = 58;

    /** @param int $index zero-based position in the list */
    public function __construct(
        private Article $article,
        private int $index,
    ) {}

    public function path(): string
    {
        return 'writing/post-' . ($this->index + 1) . '.svg';
    }

    public function alt(): string
    {
        $a = $this->article;
        return "{$a->title} — published {$a->date()}, {$a->reactions} reactions, {$a->comments} comments";
    }

    public function href(): ?string
    {
        return $this->article->url;
    }

    public function width(): float
    {
        return 100;
    }

    public function render(Frames $frames): string
    {
        $a = $this->article;
        $n = Markup::num(...);
        $f1 = Markup::f1(...);
        $e = Markup::esc(...);
        $cy = Color::Cyan->value;
        $magenta = Color::Magenta->value;
        $x = G::X;
        $rx = G::RIGHT;

        $date = $a->date();
        $shown = Markup::len($a->title) <= self::MAX_TITLE
            ? $a->title
            : rtrim(mb_substr($a->title, 0, self::MAX_TITLE - 1), ' .,:;') . '…';
        [$rc, $cc] = [(string) $a->reactions, (string) $a->comments];

        // right block, fixed columns (room for 3 digits) so the rows line up
        $ax = $rx - 10;
        $cxNum = $ax - 18;
        $cxIcon = $cxNum - 3 * 7.8 - 22;
        $rxNum = $cxIcon - 16;
        $rxIcon = $rxNum - 3 * 7.8 - 22;
        $fill = $this->index % 2 === 0 ? '.04' : '0';
        $delay = sprintf('%.2f', .2 + $this->index * .08);
        [$heart, $bubble] = [self::HEART, self::BUBBLE];

        $body = <<<SVG
            <g class="ln" style="animation-delay:{$delay}s">
            <rect x="{$n($x - 10)}" y="4" width="{$n($rx - $x + 20)}" height="32" fill="$cy" fill-opacity="$fill"/>
            <text x="$x" y="25" class="dim" style="font-size:13px">$date</text>
            <text x="{$n($x + 94)}" y="25" class="cy" style="font-size:13px">›</text>
            <text x="{$n($x + 112)}" y="25" class="fg" style="font-size:14px">{$e($shown)}</text>
            <path transform="translate({$f1($rxIcon)} 13) scale(.8)" d="$heart" fill="$magenta"/>
            <text x="{$f1($rxNum)}" y="25" text-anchor="end" class="dim" style="font-size:13px">$rc</text>
            <path transform="translate({$f1($cxIcon)} 13) scale(.8)" d="$bubble" fill="none" stroke="$cy" stroke-width="1.5" stroke-linejoin="round"/>
            <text x="{$f1($cxNum)}" y="25" text-anchor="end" class="dim" style="font-size:13px">$cc</text>
            <path d="M{$n($ax - 6)} 25l8-8M{$n($ax - 4)} 17h6v6" fill="none" stroke="$cy" stroke-width="1.5"/>
            </g>
            SVG;
        return $frames->full(40, $body, $a->title, "{$a->title}. Published $date. $rc reactions, $cc comments.", text: '');
    }
}
