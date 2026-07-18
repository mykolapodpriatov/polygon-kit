<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Measure\PointToPolygonDistance;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class PointToPolygonDistanceTest extends TestCase
{
    public function testInsidePointIsZero(): void
    {
        $square = PolygonFixtures::bigSquare(); // [0,0]..[10,10]

        self::assertSame(0.0, PointToPolygonDistance::of($square, new Point(5, 5)));
    }

    public function testOnEdgePointIsZero(): void
    {
        $square = PolygonFixtures::bigSquare();

        self::assertSame(0.0, PointToPolygonDistance::of($square, new Point(10, 5)));
    }

    public function testAtVertexIsZero(): void
    {
        $square = PolygonFixtures::bigSquare();

        self::assertSame(0.0, PointToPolygonDistance::of($square, new Point(0, 0)));
    }

    public function testOutsidePerpendicularToEdge(): void
    {
        $square = PolygonFixtures::bigSquare();

        // 3 units to the right of the right edge (x = 10) at mid-height.
        self::assertEqualsWithDelta(3.0, PointToPolygonDistance::of($square, new Point(13, 5)), 1e-9);
    }

    public function testOutsideNearestIsAVertex(): void
    {
        $square = PolygonFixtures::bigSquare();

        // Off the top-right corner (10, 10): nearest feature is the vertex.
        self::assertEqualsWithDelta(5.0, PointToPolygonDistance::of($square, new Point(13, 14)), 1e-9);
    }

    public function testConcavePolygonUsesNearestEdge(): void
    {
        $l = PolygonFixtures::lShape(); // [0,0],[2,0],[2,1],[1,1],[1,2],[0,2]

        // Point sitting in the notch, outside the polygon: nearest edge is the
        // horizontal edge y = 1 between x = 1 and x = 2.
        self::assertEqualsWithDelta(0.5, PointToPolygonDistance::of($l, new Point(1.5, 1.5)), 1e-9);
    }

    public function testExposedViaPolygonMethod(): void
    {
        $square = PolygonFixtures::bigSquare();
        $point = new Point(13, 5);

        self::assertSame(
            PointToPolygonDistance::of($square, $point),
            $square->distanceToPoint($point),
        );
    }
}
