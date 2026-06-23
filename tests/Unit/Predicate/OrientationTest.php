<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Predicate\Orientation;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class OrientationTest extends TestCase
{
    public function testCounterClockwise(): void
    {
        self::assertSame(Orientation::CounterClockwise, PolygonFixtures::unitSquare()->orientation());
    }

    public function testClockwise(): void
    {
        self::assertSame(Orientation::Clockwise, PolygonFixtures::clockwiseSquare()->orientation());
    }

    public function testDegenerate(): void
    {
        $line = Polygon::fromArray([[0, 0], [1, 0], [2, 0]]);
        self::assertSame(Orientation::Degenerate, Orientation::of($line));
    }
}
