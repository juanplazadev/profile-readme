<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Data;

/** data/stats.json. Keys stay snake_case so the file format matches the original Python tool. */
final readonly class Stats
{
    /**
     * @param array<string, int> $languages bytes per language, largest first
     * @param array<string, int> $repoStars
     */
    public function __construct(
        public string $updated,
        public int $year,
        public string $createdAt,
        public int $stars = 0,
        public int $forks = 0,
        public int $followers = 0,
        public int $commitsYear = 0,
        public int $commitsAll = 0,
        public ?int $contributionsYear = null,
        public ?int $contributionsAll = null,
        public int $prs = 0,
        public int $prsMerged = 0,
        public int $streakCurrent = 0,
        public int $streakLongest = 0,
        public ?int $hackathonWins = null,
        public array $languages = [],
        public array $repoStars = [],
        public ?DevStats $dev = null,
    ) {}

    public static function empty(\DateTimeImmutable $today): self
    {
        return new self($today->format('Y-m-d'), (int) $today->format('Y'), $today->format('Y-m-d\TH:i:s\Z'));
    }

    /** @param array<string, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            updated: $d['updated'],
            year: $d['year'],
            createdAt: $d['created_at'],
            stars: $d['stars'] ?? 0,
            forks: $d['forks'] ?? 0,
            followers: $d['followers'] ?? 0,
            commitsYear: $d['commits_year'] ?? 0,
            commitsAll: $d['commits_all'] ?? 0,
            contributionsYear: $d['contributions_year'] ?? null,
            contributionsAll: $d['contributions_all'] ?? null,
            prs: $d['prs'] ?? 0,
            prsMerged: $d['prs_merged'] ?? 0,
            streakCurrent: $d['streak_current'] ?? 0,
            streakLongest: $d['streak_longest'] ?? 0,
            hackathonWins: $d['hackathon_wins'] ?? null,
            languages: $d['languages'] ?? [],
            repoStars: $d['repo_stars'] ?? [],
            dev: isset($d['dev']) ? DevStats::fromArray($d['dev']) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'updated' => $this->updated,
            'year' => $this->year,
            'created_at' => $this->createdAt,
            'stars' => $this->stars,
            'forks' => $this->forks,
            'followers' => $this->followers,
            'commits_year' => $this->commitsYear,
            'commits_all' => $this->commitsAll,
            'contributions_year' => $this->contributionsYear,
            'contributions_all' => $this->contributionsAll,
            'prs' => $this->prs,
            'prs_merged' => $this->prsMerged,
            'streak_current' => $this->streakCurrent,
            'streak_longest' => $this->streakLongest,
            'hackathon_wins' => $this->hackathonWins,
            'languages' => $this->languages ?: new \stdClass(),
            'repo_stars' => $this->repoStars ?: new \stdClass(),
            'dev' => $this->dev?->toArray(),
        ], fn($v) => $v !== null);
    }

    public function since(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(substr($this->createdAt, 0, 10), new \DateTimeZone('UTC'));
    }

    /** "1,009 contributions in 2026, 1,649 all time" — older data files only have commit counts. */
    public function activity(bool $thousands = false): string
    {
        $f = fn(int $n) => $thousands ? number_format($n) : (string) $n;
        return $this->contributionsYear !== null
            ? "{$f($this->contributionsYear)} contributions in {$this->year}, {$f($this->contributionsAll ?? 0)} all time"
            : "{$f($this->commitsYear)} commits in {$this->year}, {$f($this->commitsAll)} all time";
    }

    /**
     * Top five languages plus an "Other" bucket, as shares of the total.
     *
     * @return list<array{0: string, 1: float}>
     */
    public function languageShares(): array
    {
        $langs = $this->languages;
        arsort($langs);
        $total = array_sum($langs);
        if ($total <= 0) {
            return [];
        }
        $top = array_slice($langs, 0, 5, true);
        $items = array_map(fn($k, $v) => [(string) $k, $v / $total], array_keys($top), $top);
        $other = $total - array_sum($top);
        if ($other > 0) {
            $items[] = ['Other', $other / $total];
        }
        return $items;
    }
}
