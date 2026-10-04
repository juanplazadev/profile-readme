<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Config;

/** Keys into resources/icons.json (24×24 Simple Icons / Material paths), plus a few drawn glyphs. */
enum LinkIcon: string
{
    case GitHub = 'github';
    case LinkedIn = 'linkedin';
    case Website = 'globe';
    case Email = 'mail';
    case Dev = 'devdotto';
    case X = 'x';
    case YouTube = 'youtube';
    case Discord = 'discord';

    /** LinkedIn isn't in Simple Icons, so it gets a plain "in" glyph instead of a path. */
    public function isGlyph(): bool
    {
        return $this === self::LinkedIn;
    }
}
