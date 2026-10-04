<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Data\Stats;
use JuanPlaza\Profile\Svg\Color;
use JuanPlaza\Profile\Svg\Component\Heading;
use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Component\Tile;
use JuanPlaza\Profile\Svg\Frame\Frames;
use JuanPlaza\Profile\Svg\Geometry as G;

/** Big GitHub tiles, a neofetch-style list, the language bar, and DEV tiles when there are any. */
final readonly class StatsSlice implements Slice
{
    private const array LANG_COLORS = [Color::Cyan, Color::Magenta, Color::Green, Color::Amber, Color::Violet, Color::Gray];
    private const array DEV_TILES = ['articles' => 'ARTICLES', 'reactions' => 'REACTIONS', 'comments' => 'COMMENTS', 'views' => 'VIEWS', 'followers' => 'DEV FOLLOWERS'];

    public function __construct(
        private Stats $stats,
        private string $githubUser,
        private ?string $devUser,
        private string $counter,
    ) {}

    public function path(): string
    {
        return 'stats.svg';
    }

    public function alt(): string
    {
        $d = $this->stats;
        $days = fn(int $n) => $n === 1 ? "$n day" : "$n days";
        $parts = [
            "{$d->stars} total stars",
            $d->activity(),
            "{$d->prs} pull requests ({$d->prsMerged} merged)",
            "current streak {$days($d->streakCurrent)}, longest {$days($d->streakLongest)}",
            "{$d->followers} followers",
            "{$d->forks} forks",
            'member since ' . $d->since()->format('F Y'),
        ];
        if ($d->hackathonWins !== null) {
            $parts[] = "{$d->hackathonWins} hackathon wins";
        }
        $langs = array_map(fn(array $item) => $item[0], array_filter($d->languageShares(), fn(array $item) => $item[0] !== 'Other'));
        $text = 'Stats: ' . implode('; ', $parts) . '.' . ($langs ? ' Top languages: ' . implode(', ', $langs) . '.' : '');
        $dev = $this->devTiles();
        if ($dev !== []) {
            $text .= ' DEV Community: ' . implode(', ', array_map(
                fn(string $label, int $v) => number_format($v) . ' ' . strtolower($label), array_keys($dev), $dev)) . '.';
        }
        return $text;
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
        $d = $this->stats;
        $n = Markup::num(...);
        $f1 = Markup::f1(...);
        $e = Markup::esc(...);
        $fmt = Markup::fmt(...);
        $cy = Color::Cyan->value;
        $x = G::X;

        $updated = new \DateTimeImmutable($d->updated, new \DateTimeZone('UTC'));
        $since = $d->since();
        $yrs = intdiv((int) $since->diff($updated)->days, 365);

        $parts = [
            Heading::render(44, 'stats', $this->counter),
            Heading::prompt("gh stats --user {$this->githubUser}"),
        ];

        // row 1: big tiles
        [$tw, $gap, $ty, $th] = [182, 16, 118, 92];
        $activity = $d->contributionsYear !== null
            ? ["CONTRIBUTIONS {$d->year}", $fmt($d->contributionsYear), $fmt($d->contributionsAll) . ' all time']
            : ["COMMITS {$d->year}", $fmt($d->commitsYear), $fmt($d->commitsAll) . ' all time'];   // older data files
        $tiles = [
            ['TOTAL STARS', $fmt($d->stars), 'across all repos'],
            $activity,
            ['PULL REQUESTS', $fmt($d->prs), $fmt($d->prsMerged) . ' merged'],
            ['CURRENT STREAK', "{$d->streakCurrent}d", "longest: {$d->streakLongest} days"],
        ];
        foreach ($tiles as $i => [$label, $value, $sub]) {
            $parts[] = Tile::render($x + $i * ($tw + $gap), $ty, $tw, $th, $label, $value, $sub, .25 + $i * .08);
        }

        // row 2: neofetch list + languages
        [$ry, $rh, $lw] = [$ty + $th + 16, 150, 300];
        $kv = [
            ['followers', $fmt($d->followers)],
            ['forks', $fmt($d->forks)],
            ['member since', $since->format('M Y') . " ({$yrs}y)"],
        ];
        if ($d->hackathonWins !== null) {
            $kv[] = ['hackathon wins', "{$d->hackathonWins} ★"];
        }
        $rows = implode("\n", array_map(
            fn(int $i, array $row) => '<text x="' . ($x + 16) . '" y="' . ($ry + 38 + $i * 28) . '" xml:space="preserve">'
                . "<tspan class=\"cy\">{$e($row[0])}</tspan><tspan class=\"dim\">" . str_repeat('.', 16 - strlen($row[0]))
                . "</tspan> <tspan class=\"fg\">{$e($row[1])}</tspan></text>",
            array_keys($kv), $kv,
        ));
        $parts[] = <<<SVG
            <g class="ln" style="animation-delay:.6s">
            <rect x="$x" y="$ry" width="$lw" height="$rh" fill="$cy" fill-opacity=".035" stroke="$cy" stroke-opacity=".35"/>
            <path d="M$x {$n($ry + 12)}V{$ry}H{$n($x + 12)}" fill="none" stroke="$cy" stroke-width="2"/>
            $rows
            </g>
            SVG;

        $lx = $x + $lw + $gap;
        $lwid = G::RIGHT - $lx;
        $items = $d->languageShares();
        [$bx, $by, $bw] = [$lx + 16, $ry + 42, $lwid - 32];
        $segs = $legend = [];
        $cx = $bx;
        foreach ($items as $i => [$lang, $share]) {
            $c = self::LANG_COLORS[$i]->value;
            $w = $bw * $share;
            $sw = $f1(max($w - 2, 1));
            $segs[] = "<rect x=\"{$f1($cx)}\" y=\"$by\" width=\"$sw\" height=\"8\" fill=\"$c\"/>";
            $segs[] = "<rect x=\"{$f1($cx)}\" y=\"$by\" width=\"$sw\" height=\"8\" fill=\"$c\" filter=\"url(#g)\" opacity=\".6\"/>";
            $cx += $w;

            $lxx = $bx + ($i % 2) * ($bw / 2);
            $lyy = $by + 36 + intdiv($i, 2) * 26;
            $legend[] = "<rect x=\"{$n($lxx)}\" y=\"{$n($lyy - 9)}\" width=\"9\" height=\"9\" fill=\"$c\"/>"
                . "<text x=\"{$n($lxx + 18)}\" y=\"$lyy\" class=\"fg\" style=\"font-size:13px\">{$e($lang)}</text>"
                . "<text x=\"{$n($lxx + $bw / 2 - 24)}\" y=\"$lyy\" text-anchor=\"end\" class=\"dim\" style=\"font-size:13px\">{$f1($share * 100)}%</text>";
        }
        $segs = implode('', $segs);
        $legend = implode('', $legend);
        $parts[] = <<<SVG
            <g class="ln" style="animation-delay:.7s">
            <rect x="$lx" y="$ry" width="$lwid" height="$rh" fill="$cy" fill-opacity=".035" stroke="$cy" stroke-opacity=".35"/>
            <path d="M$lx {$n($ry + 12)}V{$ry}H{$n($lx + 12)}" fill="none" stroke="$cy" stroke-width="2"/>
            <text x="{$n($lx + 16)}" y="{$n($ry + 24)}" letter-spacing="1.5" class="dim" style="font-size:10.5px">TOP LANGUAGES</text>
            <rect x="$bx" y="$by" width="$bw" height="8" fill="#11161d"/>
            $segs
            $legend
            </g>
            SVG;

        // row 3: DEV Community (tiles without data are left out; the row hides if all are missing)
        $dev = $this->devTiles();
        $fy = $ry + $rh + 30;
        if ($dev !== []) {
            $dy = $ry + $rh + 34;
            $parts[] = Heading::prompt("dev stats --user {$this->devUser}", delay: .8, y: $dy);
            $count = count($dev);
            $dw = (G::RIGHT - $x - ($count - 1) * 12) / $count;
            $i = 0;
            foreach ($dev as $label => $value) {
                $parts[] = Tile::render($x + $i * ($dw + 12), $dy + 16, $dw, 76, $label, $fmt($value), '', .9 + $i++ * .06);
            }
            $fy = $dy + 16 + 76 + 30;
        }
        $parts[] = '<text x="' . G::RIGHT . "\" y=\"$fy\" text-anchor=\"end\" fill=\"#484f58\" style=\"font-size:11px\">// last sync {$e($d->updated)}</text>";

        $desc = "GitHub stats: {$d->stars} total stars; {$d->activity()}; {$d->prs} pull requests ({$d->prsMerged} merged); "
            . "current streak {$d->streakCurrent} days, longest {$d->streakLongest}; {$d->followers} followers; {$d->forks} forks; "
            . 'member since ' . $since->format('F Y')
            . ($d->hackathonWins !== null ? "; {$d->hackathonWins} hackathon wins" : '') . '. '
            . 'Top languages: ' . implode(', ', array_map(fn(array $it) => "{$it[0]} {$f1($it[1] * 100)}%", $items)) . '.'
            . ($dev === [] ? '' : ' DEV Community: ' . implode(', ', array_map(
                fn(string $label, int $v) => $fmt($v) . ' ' . strtolower($label), array_keys($dev), $dev)) . '.');

        return $frames->full(Markup::up40($fy + 16), implode("\n", $parts), 'Stats', $desc, text: '0123456789,—%.★()d');
    }

    /** @return array<string, int> label => value, known values only */
    private function devTiles(): array
    {
        if ($this->devUser === null || $this->stats->dev === null) {
            return [];
        }
        $values = $this->stats->dev->toArray();
        $tiles = [];
        foreach (self::DEV_TILES as $key => $label) {
            if ($values[$key] !== null) {
                $tiles[$label] = $values[$key];
            }
        }
        return $tiles;
    }
}
