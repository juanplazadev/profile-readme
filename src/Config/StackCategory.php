<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

final readonly class StackCategory
{
    /** @param list<string> $items */
    public function __construct(
        public string $name,
        public array $items,
    ) {}
}
