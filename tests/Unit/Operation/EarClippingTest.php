<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\EarClipping;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class EarClippingTest extends TestCase
{
    /**
     * @return array<string, array{Polygon}>
     */
    public static function simplePolygons(): array
    {
        return [
            'triangle' => [PolygonFixtures::triangle()],
            'unit square (convex)' => [PolygonFixtures::unitSquare()],
            'clockwise square (convex)' => [PolygonFixtures::clockwiseSquare()],
            'pentagon (convex)' => [PolygonFixtures::pentagon()],
            'L-shape (non-convex)' => [PolygonFixtures::lShape()],
            'concave dart' => [Polygon::fromArray([[0, 0], [2, 1], [4, 0], [2, 4]])],
            'concave arrowhead' => [Polygon::fromArray([[0, 0], [4, 0], [4, 4], [2, 1], [0, 4]])],
        ];
    }

    /**
     * Core property: a triangulation of an n-gon yields exactly n - 2 triangles
     * whose areas sum to the polygon's area (a tiling — no gaps, no overlaps).
     *
     */
    #[DataProvider('simplePolygons')]
    public function testTriangleCountAndAreaAreConserved(Polygon $polygon): void
    {
        $triangles = EarClipping::triangulate($polygon);

        self::assertCount($polygon->vertexCount() - 2, $triangles);

        $summedArea = 0.0;
        foreach ($triangles as $triangle) {
            self::assertSame(3, $triangle->vertexCount());
            $summedArea += $triangle->area();
        }

        self::assertEqualsWithDelta($polygon->area(), $summedArea, 1e-9);
    }

    public function testTriangleReturnsItselfAsSingleTriangle(): void
    {
        $triangles = EarClipping::triangulate(PolygonFixtures::triangle());

        self::assertCount(1, $triangles);
        self::assertEqualsWithDelta(0.5, $triangles[0]->area(), 1e-9);
    }

    public function testNonSimplePolygonThrows(): void
    {
        $this->expectException(GeometryException::class);

        EarClipping::triangulate(PolygonFixtures::bowtie());
    }
}
