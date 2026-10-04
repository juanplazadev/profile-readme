<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\Component;

/** Lays rows out top to bottom, each fading in a little after the previous one. */
final class Stagger
{
    /**
     * @param list<?string> $rows markup containing "{y}", or null for a small spacer
     * @return array{0: string, 1: int} [markup, next y]
     */
    #[\NoDiscard]
    public static function rows(array $rows, int $y0, int $lineHeight, float $delay0 = .15, float $step = .12): array
    {
        $out = [];
        $y = $y0;
        $i = 0;
        foreach ($rows as $row) {
            if ($row === null) {
                $y += 14;
                continue;
            }
            $i++;
            $delay = sprintf('%.2f', $delay0 + $i * $step);
            $out[] = "<g class=\"ln\" style=\"animation-delay:{$delay}s\">" . str_replace('{y}', (string) $y, $row) . '</g>';
            $y += $lineHeight;
        }
        return [implode("\n", $out), $y];
    }
}
