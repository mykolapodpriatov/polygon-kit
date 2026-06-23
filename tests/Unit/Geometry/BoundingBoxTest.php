<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Geometry;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\BoundingBox;
use PolygonKit\Geometry\Point;

final class BoundingBoxTest extends TestCase
{
    public function testFromPointsAndDimensions(): void
    {
        $box = BoundingBox::fromPoints(new Point(0, 0), new Point(3, 0), new Point(3, 2));

        self::assertEqualsWithDelta(0.0, $box->minX, 1e-9);
        self::assertEqualsWithDelta(3.0, $box->maxX, 1e-9);
        self::assertEqualsWithDelta(2.0, $box->height(), 1e-9);
        self::assertEqualsWithDelta(6.0, $box->area(), 1e-9);
    }

    public function testContainsAndIntersects(): void
    {
        $a = new BoundingBox(0, 0, 4, 4);
        $b = new BoundingBox(2, 2, 6, 6);
        $c = new BoundingBox(10, 10, 12, 12);

        self::assertTrue($a->contains(new Point(2, 2)));
        self::assertFalse($a->contains(new Point(5, 5)));
        self::assertTrue($a->intersects($b));
        self::assertFalse($a->intersects($c));
    }
}
