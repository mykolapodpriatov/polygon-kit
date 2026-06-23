<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\ConvexIntersection;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class ConvexIntersectionTest extends TestCase
{
    public function testOverlappingSquares(): void
    {
        $a = Polygon::fromArray([[0, 0], [4, 0], [4, 4], [0, 4]]);
        $b = Polygon::fromArray([[2, 2], [6, 2], [6, 6], [2, 6]]);

        $overlap = ConvexIntersection::of($a, $b);

        self::assertNotNull($overlap);
        self::assertEqualsWithDelta(4.0, $overlap->area(), 1e-9); // 2x2 square
    }

    public function testDisjointSquaresReturnNull(): void
    {
        $a = Polygon::fromArray([[0, 0], [1, 0], [1, 1], [0, 1]]);
        $b = Polygon::fromArray([[5, 5], [6, 5], [6, 6], [5, 6]]);

        self::assertNull(ConvexIntersection::of($a, $b));
    }

    public function testOneInsideOtherReturnsInner(): void
    {
        $outer = Polygon::fromArray([[0, 0], [10, 0], [10, 10], [0, 10]]);
        $inner = Polygon::fromArray([[2, 2], [4, 2], [4, 4], [2, 4]]);

        $overlap = ConvexIntersection::of($outer, $inner);

        self::assertNotNull($overlap);
        self::assertEqualsWithDelta($inner->area(), $overlap->area(), 1e-9);
    }

    public function testIntersectionAreaNeverExceedsInputs(): void
    {
        $a = PolygonFixtures::bigSquare();
        $b = Polygon::fromArray([[5, 5], [15, 5], [15, 15], [5, 15]]);

        $overlap = ConvexIntersection::of($a, $b);

        self::assertNotNull($overlap);
        self::assertLessThanOrEqual(min($a->area(), $b->area()) + 1e-9, $overlap->area());
    }
}
