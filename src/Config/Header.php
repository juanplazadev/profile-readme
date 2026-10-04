<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

final readonly class Header
{
    /**
     * @param list<string> $lines     the ">>" lines under the name (three fit)
     * @param ?string      $highlight bold cyan text appended to the last line
     */
    public function __construct(
        public string $name,
        public string $barLeft,
        public string $barRight,
        public array $lines,
        public ?string $highlight,
        public string $title,
        public string $description,
    ) {
        if (count($lines) < 1 || count($lines) > 3) {
            throw new \InvalidArgumentException('The header has room for one to three lines');
        }
    }
}
