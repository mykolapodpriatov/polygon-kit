<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\Simplify;

final class SimplifyTest extends TestCase
{
    public function testCollinearMidpointsAreRemoved(): void
    {
        // A square whose edges carry redundant collinear midpoints.
        $polygon = Polygon::fromArray([
            [0, 0], [5, 0], [10, 0],
            [10, 5], [10, 10],
            [5, 10], [0, 10],
            [0, 5],
        ]);

        $simplified = Simplify::douglasPeucker($polygon, 0.01);

        self::assertSame(4, $simplified->vertexCount());
        self::assertEqualsWithDelta(100.0, $simplified->area(), 1e-9);
    }

    public function testNeverDropsBelowThreeVertices(): void
    {
        $triangle = Polygon::fromArray([[0, 0], [10, 0], [0, 10]]);
        $simplified = Simplify::douglasPeucker($triangle, 1000.0);

        self::assertGreaterThanOrEqual(3, $simplified->vertexCount());
    }
}
