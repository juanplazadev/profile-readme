<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Svg\City;

/**
 * Tiny deterministic PRNG (a 64-bit LCG seeded from SHA-256), so the same data always draws
 * the same windows and stars. Same algorithm as the original Python, so seeds match too.
 *
 * PHP ints are signed and overflow to float, so the mod-2^64 arithmetic goes through GMP.
 */
final class Prng
{
    private \GMP $state;
    private static ?\GMP $mod = null;

    public function __construct(string $seed)
    {
        self::$mod ??= gmp_pow(2, 64);
        $this->state = gmp_init(substr(hash('sha256', $seed), 0, 16), 16);
    }

    /** A float in [0, 1). */
    public function next(): float
    {
        $this->state = gmp_mod($this->state * gmp_init('6364136223846793005') + gmp_init('1442695040888963407'), self::$mod);
        return gmp_intval($this->state >> 11) / 2 ** 53;
    }
}
