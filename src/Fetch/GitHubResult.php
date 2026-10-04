<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

use JuanPlaza\Profile\Data\CalendarDay;

final readonly class GitHubResult
{
    /**
     * @param array<string, mixed> $stats    stats.json fields this source owns
     * @param list<CalendarDay>    $calendar last 53 weeks, starting on a Sunday
     */
    public function __construct(
        public array $stats,
        public array $calendar,
    ) {}
}
