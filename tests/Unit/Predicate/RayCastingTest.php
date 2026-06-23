<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Predicate\RayCasting;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class RayCastingTest extends TestCase
{
    public function testInsideOutsideBoundary(): void
    {
        $square = PolygonFixtures::bigSquare();
        $method = new RayCasting();

        self::assertTrue($method->contains($square, new Point(5, 5)));   // inside
        self::assertFalse($method->contains($square, new Point(20, 5))); // outside
        self::assertTrue($method->contains($square, new Point(0, 5)));   // on edge
        self::assertTrue($method->contains($square, new Point(10, 10))); // on vertex
    }

    public function testConcaveNotch(): void
    {
        // Point inside the L-shape's "notch" must be reported outside.
        $l = PolygonFixtures::lShape();
        $method = new RayCasting();
        self::assertFalse($method->contains($l, new Point(1.5, 1.5)));
        self::assertTrue($method->contains($l, new Point(0.5, 0.5)));
    }
}
