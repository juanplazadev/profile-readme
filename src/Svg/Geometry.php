<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg;

/**
 * Every slice is W wide and a multiple of 40px tall (so the background grid lines up
 * across slices); the frame sits M px in from each side to leave room for its glow.
 */
final class Geometry
{
    public const int W = 880;
    public const int M = 16;
    public const int FL = self::M;            // frame left
    public const int FR = self::W - self::M;  // frame right
    public const int X = 52;                  // text left edge
    public const int RIGHT = self::FR - 36;   // text right edge
    public const int HW = self::W / 2;        // half-slice width (440, a multiple of 40)
}
