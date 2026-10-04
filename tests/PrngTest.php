<?php

declare(strict_types=1);

namespace JuanPlaza\Profile\Tests;

use JuanPlaza\Profile\Svg\City\Prng;
use PHPUnit\Framework\TestCase;

final class PrngTest extends TestCase
{
    public function testSameSeedSameSequence(): void
    {
        $a = new Prng('2026-10-03-1322');
        $b = new Prng('2026-10-03-1322');
        for ($i = 0; $i < 100; $i++) {
            $this->assertSame($a->next(), $b->next());
        }
    }

    public function testValuesAreInUnitInterval(): void
    {
        $r = new Prng('x');
        for ($i = 0; $i < 1000; $i++) {
            $v = $r->next();
            $this->assertGreaterThanOrEqual(0.0, $v);
            $this->assertLessThan(1.0, $v);
        }
    }

    /** Values computed with the original Python _rng("2026-10-03-1322"). */
    public function testMatchesThePythonImplementation(): void
    {
        $r = new Prng('2026-10-03-1322');
        $this->assertSame([0.9729910804500489, 0.8804046837320771, 0.311028523189922], [$r->next(), $r->next(), $r->next()]);
    }
}
