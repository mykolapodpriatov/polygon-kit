<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Measure\ConvexityTest;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class ConvexityTestTest extends TestCase
{
    public function testConvexShapes(): void
    {
        self::assertTrue(ConvexityTest::isConvex(PolygonFixtures::unitSquare()));
        self::assertTrue(ConvexityTest::isConvex(PolygonFixtures::triangle()));
        self::assertTrue(ConvexityTest::isConvex(PolygonFixtures::pentagon()));
    }

    public function testConcaveShapeIsNotConvex(): void
    {
        self::assertFalse(ConvexityTest::isConvex(PolygonFixtures::lShape()));
    }

    public function testCollinearVertexStillConvex(): void
    {
        // Square with an extra collinear vertex on the bottom edge.
        $square = Polygon::fromArray([[0, 0], [1, 0], [2, 0], [2, 2], [0, 2]]);
        self::assertTrue(ConvexityTest::isConvex($square));
    }

    public function testSelfIntersectingStarIsNotConvex(): void
    {
        // Regular pentagram {5/2}. Every turn has the same sign, and the
        // edges still cross, so the ring is not a convex set.
        $points = [];
        foreach ([0, 2, 4, 1, 3] as $k) {
            $angle = -M_PI / 2 + (2 * M_PI * $k / 5);
            $points[] = [cos($angle), sin($angle)];
        }
        $star = Polygon::fromArray($points);

        self::assertFalse($star->isSimple());
        self::assertFalse(ConvexityTest::isConvex($star));
    }
}
