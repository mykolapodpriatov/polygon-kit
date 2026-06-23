<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Predicate\WindingNumber;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class WindingNumberTest extends TestCase
{
    public function testInsideOutsideBoundary(): void
    {
        $square = PolygonFixtures::bigSquare();
        $method = new WindingNumber();

        self::assertTrue($method->contains($square, new Point(5, 5)));
        self::assertFalse($method->contains($square, new Point(-1, 5)));
        self::assertTrue($method->contains($square, new Point(0, 5)));
    }

    public function testConcaveNotch(): void
    {
        $l = PolygonFixtures::lShape();
        $method = new WindingNumber();
        self::assertFalse($method->contains($l, new Point(1.5, 1.5)));
        self::assertTrue($method->contains($l, new Point(0.5, 0.5)));
    }
}
