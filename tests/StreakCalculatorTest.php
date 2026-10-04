<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Tests;

use JuanPlaza\Profile\Fetch\StreakCalculator;
use PHPUnit\Framework\TestCase;

final class StreakCalculatorTest extends TestCase
{
    private function days(string $start, array $counts): array
    {
        $d = new \DateTimeImmutable($start);
        $out = [];
        foreach ($counts as $n) {
            $out[$d->format('Y-m-d')] = $n;
            $d = $d->modify('+1 day');
        }
        return $out;
    }

    public function testCurrentStreakIncludesToday(): void
    {
        $days = $this->days('2026-01-01', [1, 1, 0, 2, 3, 4]);
        $this->assertSame([3, 3], new StreakCalculator()->calculate($days, new \DateTimeImmutable('2026-01-06')));
    }

    public function testCurrentStreakMayEndYesterday(): void
    {
        $days = $this->days('2026-01-01', [1, 1, 1, 1, 0]);
        $this->assertSame([4, 4], new StreakCalculator()->calculate($days, new \DateTimeImmutable('2026-01-05')));
    }

    public function testTwoQuietDaysEndTheStreak(): void
    {
        $days = $this->days('2026-01-01', [5, 5, 5, 5, 5, 0, 0]);
        $this->assertSame([0, 5], new StreakCalculator()->calculate($days, new \DateTimeImmutable('2026-01-07')));
    }

    public function testFutureDaysAreIgnored(): void
    {
        $days = $this->days('2026-01-01', [1, 1, 1, 1, 1, 1]);
        $this->assertSame([2, 2], new StreakCalculator()->calculate($days, new \DateTimeImmutable('2026-01-02')));
    }
}
