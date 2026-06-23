<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Measure\Perimeter;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class PerimeterTest extends TestCase
{
    public function testUnitSquarePerimeter(): void
    {
        self::assertEqualsWithDelta(4.0, Perimeter::of(PolygonFixtures::unitSquare()), 1e-9);
    }

    public function testTrianglePerimeter(): void
    {
        // legs 1 + 1 + hypotenuse sqrt(2)
        self::assertEqualsWithDelta(2.0 + M_SQRT2, Perimeter::of(PolygonFixtures::triangle()), 1e-9);
    }
}
