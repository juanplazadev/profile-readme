<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

final class StreakCalculator
{
    /**
     * The current streak may end yesterday if today has no contributions yet.
     *
     * @param array<string, int> $days Y-m-d => contribution count
     * @return array{0: int, 1: int} [current, longest]
     */
    public function calculate(array $days, \DateTimeImmutable $today): array
    {
        $todayKey = $today->format('Y-m-d');
        $dates = array_filter(array_keys($days), fn(string $d) => $d <= $todayKey);
        sort($dates);

        // walk every calendar day, so a date missing from the data breaks a run like a zero would
        $longest = $run = 0;
        $prev = null;
        foreach ($dates as $d) {
            $contiguous = $prev !== null && $this->next($prev) === $d;
            $run = $days[$d] > 0 ? ($contiguous ? $run + 1 : 1) : 0;
            $longest = max($longest, $run);
            $prev = $d;
        }

        $current = 0;
        $d = $today;
        if (($days[$d->format('Y-m-d')] ?? 0) === 0) {
            $d = $d->modify('-1 day');
        }
        while (($days[$d->format('Y-m-d')] ?? 0) > 0) {
            $current++;
            $d = $d->modify('-1 day');
        }
        return [$current, $longest];
    }

    private function next(string $ymd): string
    {
        return new \DateTimeImmutable($ymd)->modify('+1 day')->format('Y-m-d');
    }
}
