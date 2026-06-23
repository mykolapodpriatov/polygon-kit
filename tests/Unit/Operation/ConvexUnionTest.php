<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\ConvexUnion;

final class ConvexUnionTest extends TestCase
{
    public function testAdjacentSquaresWhoseUnionIsConvex(): void
    {
        $left = Polygon::fromArray([[0, 0], [2, 0], [2, 2], [0, 2]]);
        $right = Polygon::fromArray([[2, 0], [4, 0], [4, 2], [2, 2]]);

        $union = ConvexUnion::of($left, $right);

        // The exact union is the 4x2 rectangle, which is convex -> area 8.
        self::assertEqualsWithDelta(8.0, $union->area(), 1e-9);
    }

    public function testUnionIsAtLeastAsLargeAsEitherInput(): void
    {
        $a = Polygon::fromArray([[0, 0], [2, 0], [2, 2], [0, 2]]);
        $b = Polygon::fromArray([[1, 1], [5, 1], [5, 3], [1, 3]]);

        $union = ConvexUnion::of($a, $b);

        self::assertGreaterThanOrEqual($a->area() - 1e-9, $union->area());
        self::assertGreaterThanOrEqual($b->area() - 1e-9, $union->area());
    }
}
