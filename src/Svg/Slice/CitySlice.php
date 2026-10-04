<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Data\CalendarDay;
use JuanPlaza\Profile\Svg\City\Prng;
use JuanPlaza\Profile\Svg\City\QuartileLevels;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Heading;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** An isometric night skyline with one building per day of the last year. */
final class CitySlice implements Slice
{
    private const int H = 680;
    private const float TW = 25, TH = 12.5;          // iso tile width / height
    private const float OX = 152.5, OY = 262;        // grid origin inside the slice
    private const int HMAX = 118;                    // tallest building, px
    private const array ROOFS = ['#0c2d6b', '#1554c0', '#2f81f7', '#1fd5ff'];   // navy → electric blue
    private const string WIN_ON = '#7df9ff', WIN_ON_SIDE = '#4cc9f0', WIN_OFF = '#111827';

    private Prng $rnd;
    private int $flicker = 0;

    /** @param list<CalendarDay> $calendar */
    public function __construct(
        private readonly array $calendar,
        private readonly string $updated,
        private readonly string $counter,
    ) {}

    public function path(): string
    {
        return 'contribution-city.svg';
    }

    public function alt(): string
    {
        $total = array_sum(array_map(fn(CalendarDay $d) => $d->count, $this->calendar));
        $busiest = $this->busiest();
        return 'Contribution city: an isometric night skyline with one building per day of the last year. '
            . number_format($total) . ' contributions'
            . ($busiest ? ', busiest day ' . $busiest->date->format('F j') . " with {$busiest->count}" : '') . '.';
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
        $counts = array_map(fn(CalendarDay $d) => $d->count, $this->calendar);
        $total = array_sum($counts);
        $peak = $counts ? max($counts) : 0;
        $this->rnd = new Prng("{$this->updated}-$total");
        $this->flicker = 0;

        $shapes = $this->buildings(QuartileLevels::from($counts), $peak);
        $stars = $this->stars();
        $busiest = $this->busiest();

        $n = Markup::num(...);
        [$x, $fl, $fr, $right] = [G::X, G::FL, G::FR, G::RIGHT];
        $info = array_filter([
            '<tspan class="cy" font-weight="700">' . number_format($total) . '</tspan> contributions · last 365 days',
            $busiest ? 'busiest day <tspan class="fg">' . $busiest->date->format('M j') . "</tspan> · {$busiest->count}" : '',
            count(array_filter($counts)) . ' active days',
        ]);
        $infoSvg = implode('', array_map(
            fn(int $i, string $t) => "<text x=\"$right\" y=\"" . (300 + $i * 20) . "\" text-anchor=\"end\" class=\"dim\" style=\"font-size:12px\">$t</text>",
            range(0, count($info) - 1), $info,
        ));
        $legend = implode('', array_map(
            fn(int $i, string $c) => '<rect x="' . ($x + 52 + $i * 16) . '" y="642" width="11" height="11" fill="' . $c . '"/>',
            range(0, 4), ['#161b22', ...self::ROOFS],
        ));
        $skyX = $x + 52 + 5 * 16 + 6;
        $heading = Heading::render(44, 'contribution-city', $this->counter);
        $prompt = Heading::prompt('render-city --last 365d', 'one building per day');

        $body = <<<SVG
            $heading
            $prompt
            <g>$stars</g>
            <circle cx="{$n($fr - 80)}" cy="160" r="40" fill="url(#moonglow)"/>
            <circle cx="{$n($fr - 80)}" cy="160" r="14" fill="#e6edf3"/>
            <circle cx="{$n($fr - 74)}" cy="155" r="12.5" fill="#03040a"/>
            <g class="plane"><g transform="translate(0 132)"><rect x="0" y="0" width="14" height="2" rx="1" fill="#484f58"/><circle class="bl" cx="0" cy="1" r="1.6" fill="#ff7b72"/><circle class="bl" cx="14" cy="1" r="1.6" fill="#f0f6fc" style="animation-delay:.7s"/></g></g>
            $infoSvg
            $shapes
            <text x="$x" y="652" class="dim" style="font-size:11px">quiet</text>$legend<text x="$skyX" y="652" class="dim" style="font-size:11px">skyscraper</text>
            SVG;

        $flyFrom = $fl - 40;
        $flyTo = $fr + 40;
        $css = <<<CSS
            @keyframes tw{0%,100%{opacity:.9}50%{opacity:.15}}
            @keyframes fl{0%,40%,100%{opacity:1}45%,60%{opacity:.1}}
            @keyframes blink{0%,90%,100%{opacity:0}93%{opacity:1}}
            @keyframes fly{from{transform:translate({$flyFrom}px,0)}to{transform:translate({$flyTo}px,-30px)}}
            .s0{animation:tw 3s infinite}
            .f0{animation:fl 5s infinite}.f1{animation:fl 7s infinite 2s}.f2{animation:fl 9s infinite 4s}
            .plane{animation:fly 26s linear infinite}.bl{animation:blink 1.4s infinite}
            CSS;
        $defs = '<radialGradient id="moonglow"><stop offset="0" stop-color="#f0f6fc" stop-opacity=".22"/><stop offset="1" stop-color="#f0f6fc" stop-opacity="0"/></radialGradient>';
        $desc = 'Contribution city: an isometric night skyline with one building per day of the last year, '
            . 'taller and brighter for busier days. ' . number_format($total) . ' contributions'
            . ($busiest ? ', busiest day ' . $busiest->date->format('F j') . " with {$busiest->count}" : '') . '.';

        return $frames->full(self::H, $body, 'Contribution city', $desc, text: '', css: $css, defs: $defs);
    }

    /** The first day with the highest count, or null when every day is zero. */
    private function busiest(): ?CalendarDay
    {
        $best = null;
        foreach ($this->calendar as $day) {
            if ($best === null || $day->count > $best->count) {
                $best = $day;
            }
        }
        return $best?->count ? $best : null;
    }

    private function buildings(QuartileLevels $levels, int $peak): string
    {
        if ($this->calendar === []) {
            return '';
        }
        $start = array_first($this->calendar)->date;
        $cells = array_map(fn(CalendarDay $d) => [
            intdiv((int) $start->diff($d->date)->days, 7),   // week column
            (int) $d->date->format('w'),                      // row, Sunday = 0
            $d->count,
        ], $this->calendar);
        usort($cells, fn(array $a, array $b) => [$a[0] + $a[1], $a[0]] <=> [$b[0] + $b[1], $b[0]]);   // back to front

        $shapes = [];
        foreach ($cells as [$w, $dow, $count]) {
            $cx = self::OX + ($w - $dow) * self::TW / 2;
            $cy = self::OY + ($w + $dow) * self::TH / 2;
            $L = [$cx - self::TW / 2, $cy];
            $R = [$cx + self::TW / 2, $cy];
            $T = [$cx, $cy - self::TH / 2];
            $B = [$cx, $cy + self::TH / 2];
            if ($count === 0) {
                $shapes[] = '<path d="M' . $this->p($T) . 'L' . $this->p($R) . 'L' . $this->p($B) . 'L' . $this->p($L) . 'Z" fill="#161b22" stroke="#0d1117" stroke-width=".6"/>';
                continue;
            }
            $h = 8 + (self::HMAX - 8) * sqrt($count / $peak);
            [$Tu, $Ru, $Bu, $Lu] = array_map(fn(array $q) => [$q[0], $q[1] - $h], [$T, $R, $B, $L]);
            $roof = self::ROOFS[$levels->level($count)];
            $shapes[] = '<path d="M' . $this->p($L) . 'L' . $this->p($B) . 'L' . $this->p($Bu) . 'L' . $this->p($Lu) . 'Z" fill="#1a2440"/>'
                . '<path d="M' . $this->p($B) . 'L' . $this->p($R) . 'L' . $this->p($Ru) . 'L' . $this->p($Bu) . 'Z" fill="#111831"/>'
                . '<path d="M' . $this->p($Tu) . 'L' . $this->p($Ru) . 'L' . $this->p($Bu) . 'L' . $this->p($Lu) . "Z\" fill=\"$roof\"/>";
            array_push($shapes, ...$this->windows($L, $B, $R, $h));
        }
        return implode('', $shapes);
    }

    /**
     * @param array{0: float, 1: float} $L
     * @param array{0: float, 1: float} $B
     * @param array{0: float, 1: float} $R
     * @return list<string>
     */
    private function windows(array $L, array $B, array $R, float $h): array
    {
        $on = $side = $off = $flickering = [];
        foreach (['l' => [$L, $B], 'r' => [$B, $R]] as $face => [$a, $b]) {
            $rows = max(0, (int) floor(($h - 6) / 7));
            for ($r = 0; $r < $rows; $r++) {
                $v0 = 5 + $r * 7;
                foreach ([.18, .58] as $u0) {
                    $lit = $this->rnd->next() < .55;
                    if (!$lit && $this->rnd->next() < .5) {
                        continue;
                    }
                    $pts = array_map(
                        fn(array $uv) => [$a[0] + ($b[0] - $a[0]) * $uv[0], $a[1] + ($b[1] - $a[1]) * $uv[0] - $uv[1]],
                        [[$u0, $v0], [$u0 + .26, $v0], [$u0 + .26, $v0 + 3.2], [$u0, $v0 + 3.2]],
                    );
                    $seg = 'M' . implode('L', array_map($this->p(...), $pts)) . 'Z';
                    if (!$lit) {
                        $off[] = $seg;
                    } elseif ($this->rnd->next() < .03) {
                        $flickering[] = [$seg, $face];
                    } elseif ($face === 'l') {
                        $on[] = $seg;
                    } else {
                        $side[] = $seg;
                    }
                }
            }
        }
        $out = [];
        if ($off) {
            $out[] = '<path d="' . implode('', $off) . '" fill="' . self::WIN_OFF . '"/>';
        }
        if ($on) {
            $out[] = '<path d="' . implode('', $on) . '" fill="' . self::WIN_ON . '"/>';
        }
        if ($side) {
            $out[] = '<path d="' . implode('', $side) . '" fill="' . self::WIN_ON_SIDE . '"/>';
        }
        foreach ($flickering as [$seg, $face]) {
            $this->flicker++;
            $out[] = '<path class="f' . ($this->flicker % 3) . "\" d=\"$seg\" fill=\"" . ($face === 'l' ? self::WIN_ON : self::WIN_ON_SIDE) . '"/>';
        }
        return $out;
    }

    /** Night sky in the empty top-right corner, keeping clear of the moon. */
    private function stars(): string
    {
        $out = [];
        for ($i = 0; $i < 46; $i++) {
            $x = 470 + $this->rnd->next() * 350;
            $y = 118 + $this->rnd->next() * 150;
            if ($x > 700 && $y < 215) {
                continue;
            }
            $cls = $i % 3 === 0 ? ' class="s' . ($i % 3) . '"' : '';
            $r = ['0.6', '0.8', '1.1'][$i % 3];
            $out[] = "<circle$cls cx=\"" . Markup::f1($x) . '" cy="' . Markup::f1($y) . "\" r=\"$r\" fill=\"#c9d1d9\" opacity=\""
                . sprintf('%.2f', .35 + $this->rnd->next() * .5) . '"/>';
        }
        return implode('', $out);
    }

    /** @param array{0: float, 1: float} $pt */
    private function p(array $pt): string
    {
        return Markup::f1($pt[0]) . ',' . Markup::f1($pt[1]);
    }
}
