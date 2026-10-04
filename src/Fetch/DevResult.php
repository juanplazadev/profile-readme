<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Fetch;

use JuanPlaza\Profile\Data\Article;
use JuanPlaza\Profile\Data\DevStats;

final readonly class DevResult
{
    /** @param list<Article> $latest newest first, at most five */
    public function __construct(
        public DevStats $stats,
        public array $latest,
    ) {}
}
