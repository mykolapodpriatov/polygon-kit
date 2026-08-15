<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Predicate\PolygonOverlap;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class PolygonOverlapTest extends TestCase
{
    public function testDisjointSquaresDoNotIntersect(): void
    {
        $a = PolygonFixtures::unitSquare();
        $b = Polygon::fromArray([[5, 5], [6, 5], [6, 6], [5, 6]]);

        self::assertFalse(PolygonOverlap::intersects($a, $b));
        self::assertFalse(PolygonOverlap::intersects($b, $a));
    }

    public function testDisjointButOverlappingBoundingBoxesDoNotIntersect(): void
    {
        // Two triangles in opposite corners of a shared bounding box: the
        // bbox fast-reject can't rule these out, only the edge/containment
        // tests can.
        $a = Polygon::fromArray([[0, 0], [1, 0], [0, 1]]);
        $b = Polygon::fromArray([[4, 4], [4, 3], [3, 4]]);

        self::assertFalse(PolygonOverlap::intersects($a, $b));
    }

    public function testOverlappingSquaresIntersect(): void
    {
        $a = Polygon::fromArray([[0, 0], [4, 0], [4, 4], [0, 4]]);
        $b = Polygon::fromArray([[2, 2], [6, 2], [6, 6], [2, 6]]);

        self::assertTrue(PolygonOverlap::intersects($a, $b));
        self::assertTrue(PolygonOverlap::intersects($b, $a));
    }

    public function testOneFullyInsideOtherIntersects(): void
    {
        $outer = PolygonFixtures::bigSquare();
        $inner = Polygon::fromArray([[2, 2], [4, 2], [4, 4], [2, 4]]);

        self::assertTrue(PolygonOverlap::intersects($outer, $inner));
        self::assertTrue(PolygonOverlap::intersects($inner, $outer));
    }

    public function testTouchingAtASharedVertexOnlyIntersects(): void
    {
        $a = PolygonFixtures::unitSquare();
        // Shares only the corner (1, 1) with $a, otherwise disjoint.
        $b = Polygon::fromArray([[1, 1], [2, 1], [2, 2], [1, 2]]);

        self::assertTrue(PolygonOverlap::intersects($a, $b));
        self::assertTrue(PolygonOverlap::intersects($b, $a));
    }

    public function testTouchingAtASharedEdgeOnlyIntersects(): void
    {
        $a = PolygonFixtures::unitSquare();
        // Shares the full edge (1, 0)->(1, 1) with $a, otherwise disjoint.
        $b = Polygon::fromArray([[1, 0], [2, 0], [2, 1], [1, 1]]);

        self::assertTrue(PolygonOverlap::intersects($a, $b));
        self::assertTrue(PolygonOverlap::intersects($b, $a));
    }

    public function testNonConvexOverlappingConvexIntersects(): void
    {
        // Square straddling the L-shape's solid arm, not its missing notch.
        $lShape = PolygonFixtures::lShape(); // [0,0]-[2,0]-[2,1]-[1,1]-[1,2]-[0,2]
        $square = Polygon::fromArray([[-0.5, -0.5], [0.5, -0.5], [0.5, 0.5], [-0.5, 0.5]]);

        self::assertFalse($lShape->isConvex());
        self::assertTrue(PolygonOverlap::intersects($lShape, $square));
    }

    public function testNonConvexNotOverlappingConvexInItsNotchDoesNotIntersect(): void
    {
        // Entirely inside the L-shape's missing 1x1 notch at [1,1]-[2,2]:
        // proves the result isn't just "bounding boxes overlap" and isn't
        // gated on isConvex().
        $lShape = PolygonFixtures::lShape();
        $square = Polygon::fromArray([[1.2, 1.2], [1.8, 1.2], [1.8, 1.8], [1.2, 1.8]]);

        self::assertFalse($lShape->isConvex());
        self::assertFalse(PolygonOverlap::intersects($lShape, $square));
    }

    public function testExposedViaPolygonIntersects(): void
    {
        $a = Polygon::fromArray([[0, 0], [4, 0], [4, 4], [0, 4]]);
        $b = Polygon::fromArray([[2, 2], [6, 2], [6, 6], [2, 6]]);
        $c = Polygon::fromArray([[10, 10], [11, 10], [11, 11], [10, 11]]);

        self::assertTrue($a->intersects($b));
        self::assertFalse($a->intersects($c));
    }

    public function testSelfIntersectingFirstArgumentThrows(): void
    {
        $this->expectException(GeometryException::class);

        PolygonOverlap::intersects(PolygonFixtures::bowtie(), PolygonFixtures::unitSquare());
    }

    public function testSelfIntersectingSecondArgumentThrows(): void
    {
        $this->expectException(GeometryException::class);

        PolygonOverlap::intersects(PolygonFixtures::unitSquare(), PolygonFixtures::bowtie());
    }

    public function testPolygonIntersectsThrowsWhenSelfIsNonSimple(): void
    {
        $this->expectException(GeometryException::class);

        PolygonFixtures::bowtie()->intersects(PolygonFixtures::unitSquare());
    }

    public function testPolygonIntersectsThrowsWhenOtherIsNonSimple(): void
    {
        $this->expectException(GeometryException::class);

        PolygonFixtures::unitSquare()->intersects(PolygonFixtures::bowtie());
    }
}
