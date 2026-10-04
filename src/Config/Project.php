<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

use JuanPlaza\Profile\Svg\Color;

final readonly class Project
{
    /**
     * @param string $slug  GitHub repo name; live star counts are looked up by it
     * @param int    $stars fallback when the repo isn't in the fetched stats
     * @param string $desc  wraps at 43 characters, three lines at most
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $url,
        public string $tag,
        public Color $tagColor,
        public string $stack,
        public string $desc,
        public int $stars = 0,
    ) {}
}
