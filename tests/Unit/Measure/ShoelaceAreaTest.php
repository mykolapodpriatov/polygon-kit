<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Measure\ShoelaceArea;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class ShoelaceAreaTest extends TestCase
{
    public function testUnitSquareArea(): void
    {
        self::assertEqualsWithDelta(1.0, ShoelaceArea::abs(PolygonFixtures::unitSquare()), 1e-9);
        self::assertEqualsWithDelta(1.0, ShoelaceArea::signed(PolygonFixtures::unitSquare()), 1e-9);
    }

    public function testClockwiseSquareHasNegativeSignedArea(): void
    {
        self::assertEqualsWithDelta(-1.0, ShoelaceArea::signed(PolygonFixtures::clockwiseSquare()), 1e-9);
        self::assertEqualsWithDelta(1.0, ShoelaceArea::abs(PolygonFixtures::clockwiseSquare()), 1e-9);
    }

    public function testTriangleArea(): void
    {
        self::assertEqualsWithDelta(0.5, ShoelaceArea::abs(PolygonFixtures::triangle()), 1e-9);
    }

    public function testLShapeArea(): void
    {
        self::assertEqualsWithDelta(3.0, ShoelaceArea::abs(PolygonFixtures::lShape()), 1e-9);
    }
}
