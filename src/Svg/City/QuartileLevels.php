<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\City;

/** GitHub-style activity levels: quartile thresholds over the non-zero days. */
final readonly class QuartileLevels
{
    /** @param array{0: int, 1: int, 2: int} $thresholds */
    private function __construct(public array $thresholds) {}

    /** @param list<int> $counts */
    public static function from(array $counts): self
    {
        $nz = array_values(array_filter($counts, fn(int $c) => $c > 0));
        if ($nz === []) {
            return new self([1, 1, 1]);
        }
        sort($nz);
        $q = fn(float $f) => $nz[min(count($nz) - 1, (int) (count($nz) * $f))];
        return new self([$q(.25), $q(.5), $q(.75)]);
    }

    /** 0 (quiet) to 3 (busiest quartile). */
    public function level(int $count): int
    {
        return count(array_filter($this->thresholds, fn(int $t) => $count > $t));
    }
}
