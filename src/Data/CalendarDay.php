<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Data;

final readonly class CalendarDay
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $count,
    ) {}

    /**
     * @param list<array{0: string, 1: int}> $rows calendar.json: [["2025-09-28", 11], …]
     * @return list<self>
     */
    public static function listFrom(array $rows): array
    {
        return array_map(fn(array $r) => new self(new \DateTimeImmutable($r[0], new \DateTimeZone('UTC')), $r[1]), $rows);
    }

    /** @return array{0: string, 1: int} */
    public function toArray(): array
    {
        return [$this->date->format('Y-m-d'), $this->count];
    }
}
