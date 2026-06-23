<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Measure;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Measure\Centroid;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class CentroidTest extends TestCase
{
    public function testSquareCentroid(): void
    {
        $c = Centroid::of(PolygonFixtures::unitSquare());
        self::assertEqualsWithDelta(0.5, $c->x, 1e-9);
        self::assertEqualsWithDelta(0.5, $c->y, 1e-9);
    }

    public function testDegenerateFallsBackToVertexMean(): void
    {
        // Collinear "polygon": zero area -> arithmetic mean of vertices.
        $line = Polygon::fromArray([[0, 0], [1, 0], [2, 0]]);
        $c = Centroid::of($line);
        self::assertEqualsWithDelta(1.0, $c->x, 1e-9);
        self::assertEqualsWithDelta(0.0, $c->y, 1e-9);
    }
}
