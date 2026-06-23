<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Geometry;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Geometry\Point;

final class PointTest extends TestCase
{
    public function testRejectsNonFiniteCoordinates(): void
    {
        $this->expectException(InvalidPolygonException::class);
        new Point(NAN, 0.0);
    }

    public function testRejectsInfinity(): void
    {
        $this->expectException(InvalidPolygonException::class);
        new Point(0.0, INF);
    }

    public function testTranslationIsImmutable(): void
    {
        $p = new Point(1.0, 2.0);
        $moved = $p->withTranslation(3.0, 4.0);

        self::assertSame(1.0, $p->x);
        self::assertEqualsWithDelta(4.0, $moved->x, 1e-9);
        self::assertEqualsWithDelta(6.0, $moved->y, 1e-9);
    }

    public function testRotationByQuarterTurn(): void
    {
        $p = new Point(1.0, 0.0);
        $rotated = $p->withRotation(M_PI / 2, new Point(0.0, 0.0));

        self::assertEqualsWithDelta(0.0, $rotated->x, 1e-9);
        self::assertEqualsWithDelta(1.0, $rotated->y, 1e-9);
    }

    public function testDistanceAndEquality(): void
    {
        self::assertEqualsWithDelta(5.0, (new Point(0, 0))->distanceTo(new Point(3, 4)), 1e-9);
        self::assertTrue((new Point(1, 1))->equals(new Point(1.0 + 1e-12, 1.0)));
        self::assertFalse((new Point(1, 1))->equals(new Point(1.5, 1.0)));
    }
}
