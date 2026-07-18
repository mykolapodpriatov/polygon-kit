<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Predicate\SimplicityTest;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class SimplicityTestTest extends TestCase
{
    public function testConvexSquareIsSimple(): void
    {
        self::assertTrue(SimplicityTest::isSimple(PolygonFixtures::unitSquare()));
    }

    public function testConcaveShapeIsSimple(): void
    {
        // Adjacent edges only ever share their one legitimate endpoint.
        self::assertTrue(SimplicityTest::isSimple(PolygonFixtures::lShape()));
    }

    public function testTriangleIsSimple(): void
    {
        // Every edge pair is adjacent; the shared vertex must not read as a
        // self-intersection.
        self::assertTrue(SimplicityTest::isSimple(PolygonFixtures::triangle()));
    }

    public function testBowtieIsNotSimple(): void
    {
        // The two diagonals cross (a proper interior intersection).
        self::assertFalse(SimplicityTest::isSimple(PolygonFixtures::bowtie()));
    }

    public function testTJunctionIsNotSimple(): void
    {
        // A "crown" whose middle valley vertex (2, 0) lands exactly on the
        // interior of the non-adjacent base edge (4, 0)->(0, 0).
        $tJunction = Polygon::fromArray([[0, 0], [1, 2], [2, 0], [3, 2], [4, 0]]);
        self::assertFalse(SimplicityTest::isSimple($tJunction));
    }

    public function testCollinearOverlapIsNotSimple(): void
    {
        // Edge (0,0)->(2,0) and the next edge (2,0)->(1,0) run back along the
        // same line: they overlap on (1,0)->(2,0). Segment::intersectionWith()
        // returns null for collinear pairs, so this exercises the contains()
        // fallback.
        $overlap = Polygon::fromArray([[0, 0], [2, 0], [1, 0], [1, 2]]);
        self::assertFalse(SimplicityTest::isSimple($overlap));
    }

    public function testExposedViaPolygonIsSimple(): void
    {
        self::assertTrue(PolygonFixtures::unitSquare()->isSimple());
        self::assertFalse(PolygonFixtures::bowtie()->isSimple());
    }
}
