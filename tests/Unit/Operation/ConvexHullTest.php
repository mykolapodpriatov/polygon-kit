<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Operation\ConvexHull;
use PolygonKit\Predicate\Orientation;

final class ConvexHullTest extends TestCase
{
    public function testHullOfSquareWithInteriorPoints(): void
    {
        $points = [
            new Point(0, 0), new Point(4, 0), new Point(4, 4), new Point(0, 4),
            new Point(2, 2), new Point(1, 1), new Point(3, 1), // interior, must be dropped
        ];
        $hull = ConvexHull::of($points);

        self::assertSame(4, $hull->vertexCount());
        self::assertEqualsWithDelta(16.0, $hull->area(), 1e-9);
        self::assertSame(Orientation::CounterClockwise, $hull->orientation());
    }

    public function testCollinearInputThrows(): void
    {
        $this->expectException(GeometryException::class);
        ConvexHull::of([new Point(0, 0), new Point(1, 1), new Point(2, 2)]);
    }
}
