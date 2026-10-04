<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Component;

/** Small string helpers shared by every slice. */
final class Markup
{
    #[\NoDiscard]
    public static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** The characters a piece of SVG markup actually displays (so the font subset never misses one). */
    #[\NoDiscard]
    public static function visible(string $markup): string
    {
        return $markup
            |> (fn(string $s) => preg_replace('/<[^>]+>/', '', $s))
            |> (fn(string $s) => html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    /** A coordinate as SVG wants it: integers stay bare, fractions keep at most two decimals. */
    #[\NoDiscard]
    public static function num(int|float $v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }
        $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        return $s === '-0' ? '0' : $s;
    }

    /** One decimal, always (Python's :.1f). */
    #[\NoDiscard]
    public static function f1(int|float $v): string
    {
        return sprintf('%.1f', $v);
    }

    /** A count with thousands separators, or an em dash when unknown. */
    #[\NoDiscard]
    public static function fmt(?int $n): string
    {
        return $n === null ? '—' : number_format($n);
    }

    /** Round up to the next multiple of 40 (slice heights must be, so the grid lines up). */
    #[\NoDiscard]
    public static function up40(int|float $v): int
    {
        return (int) (ceil($v / 40) * 40);
    }

    /** Character count, for the monospace width estimates. */
    #[\NoDiscard]
    public static function len(string $s): int
    {
        return mb_strlen($s);
    }
}
