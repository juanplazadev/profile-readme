<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

final readonly class Link
{
    /** @param string $file asset name, written to assets/links/{file}.svg */
    public function __construct(
        public LinkIcon $icon,
        public string $label,
        public string $handle,
        public string $url,
        public string $file,
    ) {}
}
