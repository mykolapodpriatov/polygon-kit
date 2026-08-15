<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Measure\ClosestPoint;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class ClosestPointTest extends TestCase
{
    public function testPointOnLeftEdgeReturnsItself(): void
    {
        $square = PolygonFixtures::bigSquare(); // [0,0]..[10,10]
        $onEdge = new Point(0.0, 5.0);

        $closest = ClosestPoint::of($square, $onEdge);

        self::assertTrue($closest->equals($onEdge));
        self::assertSame($onEdge, $closest);
    }

    public function testPointLeftOfLeftEdgeProjectsOntoTheEdge(): void
    {
        $square = PolygonFixtures::bigSquare();
        $outside = new Point(-3.0, 5.0);

        $closest = ClosestPoint::of($square, $outside);

        self::assertEqualsWithDelta(0.0, $closest->x, 1e-9);
        self::assertEqualsWithDelta(5.0, $closest->y, 1e-9);
    }

    public function testVertexClosestCaseReturnsThatVertex(): void
    {
        $square = PolygonFixtures::bigSquare();
        // Off the top-right corner: nearest feature is the vertex (10, 10).
        $outside = new Point(13.0, 14.0);

        $closest = ClosestPoint::of($square, $outside);

        self::assertEqualsWithDelta(10.0, $closest->x, 1e-9);
        self::assertEqualsWithDelta(10.0, $closest->y, 1e-9);
    }

    public function testInsidePointReturnsItself(): void
    {
        $square = PolygonFixtures::bigSquare();
        $inside = new Point(5.0, 5.0);

        $closest = ClosestPoint::of($square, $inside);

        self::assertSame($inside, $closest);
    }

    public function testEquidistantEdgesPickLowestIndex(): void
    {
        $square = PolygonFixtures::unitSquare(); // edges 0:(0,0)-(1,0), 3:(0,1)-(0,0)
        // Equally far from the bottom and left edges; both project to (0, 0).
        $outside = new Point(-1.0, -1.0);

        $closest = ClosestPoint::of($square, $outside);

        self::assertEqualsWithDelta(0.0, $closest->x, 1e-9);
        self::assertEqualsWithDelta(0.0, $closest->y, 1e-9);
    }

    public function testExposedViaPolygonClosestPoint(): void
    {
        $square = PolygonFixtures::bigSquare();
        $point = new Point(-3.0, 5.0);

        $viaMeasure = ClosestPoint::of($square, $point);
        $viaPolygon = $square->closestPoint($point);

        self::assertTrue($viaPolygon->equals($viaMeasure));
    }
}
