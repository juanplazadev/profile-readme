<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Slice;

use JuanPlaza\Profile\Svg\Frame\Frames;

/** One SVG image in the README. */
interface Slice
{
    /** Path relative to the assets folder, e.g. "links/github.svg". */
    public function path(): string;

    public function render(Frames $frames): string;

    /** README alt text (plain, unescaped), so screen readers get the same content. */
    public function alt(): string;

    /** Where the image links to in the README, if anywhere. */
    public function href(): ?string;

    /** Share of the README row this image takes, in percent. Images share a row until they add up to 100. */
    public function width(): float;
}
