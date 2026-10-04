<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Readme;

use JuanPlaza\Profile\Svg\Component\Markup;
use JuanPlaza\Profile\Svg\Slice\Slice;

/**
 * Builds the whole profile README from the slice list, so it can never drift from the config:
 * one <img> per slice, wrapped in a link when the slice has one, with images that share a row
 * (link buttons, project cards) written on the same line so GitHub lays them out side by side.
 */
final readonly class ReadmeWriter
{
    /** @param string $assetsUrl how the README refers to the assets folder, e.g. "./profile/assets" */
    public function __construct(private string $assetsUrl) {}

    /** @param list<Slice> $slices */
    #[\NoDiscard]
    public function build(array $slices): string
    {
        $lines = [];
        $row = '';
        $filled = 0.0;
        foreach ($slices as $slice) {
            $row .= $this->image($slice);
            $filled += $slice->width();
            if ($filled >= 99.9) {
                $lines[] = $row;
                $row = '';
                $filled = 0.0;
            }
        }
        if ($row !== '') {
            $lines[] = $row;
        }
        return "<p align=\"center\">\n" . implode("\n", $lines) . "\n</p>\n";
    }

    private function image(Slice $slice): string
    {
        $img = '<img src="' . $this->attr("{$this->assetsUrl}/{$slice->path()}") . '" width="' . Markup::num($slice->width())
            . '%" align="top" alt="' . $this->attr($slice->alt()) . '">';
        $href = $slice->href();
        return $href === null ? $img : '<a href="' . $this->attr($href) . "\">$img</a>";
    }

    private function attr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
