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
}
