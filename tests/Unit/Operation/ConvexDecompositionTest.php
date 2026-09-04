<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;
use PolygonKit\Operation\ConvexDecomposition;
use PolygonKit\Operation\EarClipping;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class ConvexDecompositionTest extends TestCase
{
    /**
     * A comb: three teeth, so a triangulation is much finer than a convex
     * decomposition has to be.
     */
    private static function comb(): Polygon
    {
        return Polygon::fromArray([
            [0, 0], [6, 0], [6, 3],
            [5, 3], [5, 1], [4, 1], [4, 3],
            [3, 3], [3, 1], [2, 1], [2, 3],
            [1, 3], [1, 1], [0, 1],
        ]);
    }

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
            'comb' => [self::comb()],
        ];
    }

    #[DataProvider('simplePolygons')]
    public function testEveryPieceIsConvex(Polygon $polygon): void
    {
        foreach (ConvexDecomposition::of($polygon) as $index => $piece) {
            self::assertTrue($piece->isConvex(), "piece {$index} is not convex");
        }
    }

    /**
     * The pieces tile the polygon: their areas sum to its area, so there are
     * neither gaps nor overlaps.
     */
    #[DataProvider('simplePolygons')]
    public function testAreaIsConserved(Polygon $polygon): void
    {
        $total = 0.0;
        foreach (ConvexDecomposition::of($polygon) as $piece) {
            $total += $piece->area();
        }

        self::assertTrue(
            FloatMath::eq($total, $polygon->area(), 1e-9),
            sprintf('pieces total %.12f, polygon %.12f', $total, $polygon->area()),
        );
    }

    /**
     * Sampled interior points land in exactly one piece. Area conservation
     * alone cannot rule out a pair of overlaps that cancels against a gap.
     */
    #[DataProvider('simplePolygons')]
    public function testPiecesDoNotOverlap(Polygon $polygon): void
    {
        $pieces = ConvexDecomposition::of($polygon);
        $box = $polygon->boundingBox();

        $checked = 0;
        for ($i = 1; $i < 20; $i++) {
            for ($j = 1; $j < 20; $j++) {
                // Irrational-ish offsets keep samples off the shared diagonals,
                // where "inside" is genuinely ambiguous for a partition.
                $x = $box->minX + ($box->maxX - $box->minX) * ($i + 0.317) / 20.0;
                $y = $box->minY + ($box->maxY - $box->minY) * ($j + 0.211) / 20.0;
                $point = new Point($x, $y);
                if (! $polygon->containsPoint($point)) {
                    continue;
                }

                $hits = 0;
                foreach ($pieces as $piece) {
                    if ($piece->containsPoint($point)) {
                        $hits++;
                    }
                }
                self::assertSame(1, $hits, sprintf('point (%.4f, %.4f) is in %d pieces', $x, $y, $hits));
                $checked++;
            }
        }

        self::assertGreaterThan(0, $checked, 'no interior sample was tested');
    }

    /**
     * Merging can only ever reduce the piece count below the triangulation's.
     */
    #[DataProvider('simplePolygons')]
    public function testNeverMorePiecesThanTriangles(Polygon $polygon): void
    {
        self::assertLessThanOrEqual(
            count(EarClipping::triangulate($polygon)),
            count(ConvexDecomposition::of($polygon)),
        );
    }

    /**
     * An already-convex polygon needs no cutting at all: one piece, the same
     * vertices, the same area.
     */
    #[DataProvider('convexPolygons')]
    public function testConvexInputYieldsOnePiece(Polygon $polygon): void
    {
        $pieces = ConvexDecomposition::of($polygon);

        self::assertCount(1, $pieces);
        self::assertCount(count($polygon->vertices), $pieces[0]->vertices);
        self::assertEqualsWithDelta($polygon->area(), $pieces[0]->area(), 1e-9);
    }

    /**
     * @return array<string, array{Polygon}>
     */
    public static function convexPolygons(): array
    {
        return [
            'triangle' => [PolygonFixtures::triangle()],
            'unit square' => [PolygonFixtures::unitSquare()],
            'clockwise square' => [PolygonFixtures::clockwiseSquare()],
            'pentagon' => [PolygonFixtures::pentagon()],
            'regular hexagon' => [Polygon::regular(6, 2.0)],
        ];
    }

    /**
     * The point of doing this instead of stopping at triangles: a shape with
     * one reflex vertex splits in two, not into n - 2 slivers.
     */
    public function testLShapeSplitsIntoTwoPieces(): void
    {
        $lShape = PolygonFixtures::lShape();

        self::assertCount(4, EarClipping::triangulate($lShape));
        self::assertCount(2, ConvexDecomposition::of($lShape));
    }

    /**
     * A comb has one reflex vertex per tooth, and the decomposition should be
     * markedly coarser than the triangulation rather than equal to it.
     */
    public function testCombIsCoarserThanItsTriangulation(): void
    {
        $comb = self::comb();
        $triangles = count(EarClipping::triangulate($comb));
        $pieces = count(ConvexDecomposition::of($comb));

        self::assertLessThan($triangles, $pieces);
        self::assertLessThanOrEqual(7, $pieces);
    }

    /**
     * Winding is normalised, so a clockwise ring decomposes into the same
     * pieces as its counter-clockwise twin.
     */
    public function testWindingDoesNotChangeTheResult(): void
    {
        $ccw = PolygonFixtures::lShape();
        $cw = $ccw->reversed();

        self::assertCount(
            count(ConvexDecomposition::of($ccw)),
            ConvexDecomposition::of($cw),
        );
        self::assertEqualsWithDelta(
            array_sum(array_map(static fn (Polygon $p): float => $p->area(), ConvexDecomposition::of($ccw))),
            array_sum(array_map(static fn (Polygon $p): float => $p->area(), ConvexDecomposition::of($cw))),
            1e-9,
        );
    }

    /**
     * A self-intersecting ring has no meaningful decomposition, and fails the
     * same way triangulation does rather than inventing a second convention.
     */
    public function testSelfIntersectingRingThrows(): void
    {
        $this->expectException(GeometryException::class);
        ConvexDecomposition::of(PolygonFixtures::bowtie());
    }
}
