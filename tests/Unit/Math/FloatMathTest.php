<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Math;

use PHPUnit\Framework\TestCase;
use PolygonKit\Math\FloatMath;

final class FloatMathTest extends TestCase
{
    public function testEqualWithinTolerance(): void
    {
        self::assertTrue(FloatMath::eq(1.0, 1.0 + 1e-12));
        self::assertFalse(FloatMath::eq(1.0, 1.1));
    }

    public function testStrictOrdering(): void
    {
        self::assertTrue(FloatMath::gt(2.0, 1.0));
        self::assertFalse(FloatMath::gt(1.0, 1.0));
        self::assertTrue(FloatMath::lt(1.0, 2.0));
        self::assertFalse(FloatMath::lt(1.0, 1.0));
    }

    public function testSign(): void
    {
        self::assertSame(1, FloatMath::sign(0.5));
        self::assertSame(-1, FloatMath::sign(-0.5));
        self::assertSame(0, FloatMath::sign(1e-12));
    }
}
