<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\Cross;
use PolygonKit\Predicate\Orientation;
use PolygonKit\Predicate\SimplicityTest;

/**
 * Ear-clipping triangulation of a SIMPLE polygon into exactly n - 2 triangles,
 * O(n^2).
 *
 * By the two-ears theorem every simple polygon with more than three vertices
 * has at least two "ears" — a vertex whose triangle with its two neighbours
 * lies wholly inside the polygon and contains no other vertex. Repeatedly
 * clipping an ear removes one vertex until a single triangle remains.
 *
 * The input is guarded with {@see SimplicityTest}: a self-intersecting ring has
 * no meaningful triangulation, so it throws a {@see GeometryException}. The work
 * is done on a counter-clockwise copy of the ring, so a convex ("ear-tip")
 * vertex is simply a left turn, and the emitted triangles are all CCW.
 *
 * @see Predicate\SimplicityTest
 */
final class EarClipping
{
    /**
     * @return list<Polygon> the n - 2 triangles, each a CCW {@see Polygon}
     */
    public static function triangulate(Polygon $polygon): array
    {
        if (! SimplicityTest::isSimple($polygon)) {
            throw new GeometryException('EarClipping requires a simple (non-self-intersecting) polygon.');
        }

        // Work counter-clockwise so a convex vertex is a left turn (orient > 0).
        $vertices = $polygon->orientation() === Orientation::Clockwise
            ? array_reverse($polygon->vertices)
            : $polygon->vertices;

        $n = count($vertices);
        // Live vertex indices into $vertices, kept in ring order as ears clip.
        $indices = range(0, $n - 1);

        $triangles = [];
        $guard = $n; // At most n - 3 clips; a comfortable upper bound.

        while (count($indices) > 3 && $guard-- > 0) {
            $m = count($indices);
            $clipped = false;

            for ($i = 0; $i < $m; $i++) {
                $prevIdx = $indices[($i - 1 + $m) % $m];
                $curIdx = $indices[$i];
                $nextIdx = $indices[($i + 1) % $m];

                if (! self::isEar($prevIdx, $curIdx, $nextIdx, $vertices, $indices)) {
                    continue;
                }

                $triangles[] = new Polygon([$vertices[$prevIdx], $vertices[$curIdx], $vertices[$nextIdx]]);
                array_splice($indices, $i, 1);
                $clipped = true;
                break;
            }

            if (! $clipped) {
                // A simple polygon always has an ear; failing to find one means
                // numerically degenerate input we cannot triangulate cleanly.
                throw new GeometryException('EarClipping could not find an ear (degenerate polygon).');
            }
        }

        // The three remaining vertices form the final triangle.
        $triangles[] = new Polygon([
            $vertices[$indices[0]],
            $vertices[$indices[1]],
            $vertices[$indices[2]],
        ]);

        return $triangles;
    }

    /**
     * Is the vertex $curIdx an ear tip? It must be convex (a left turn in CCW
     * winding) and its triangle with the two neighbours must contain no other
     * live vertex.
     *
     * @param list<Point> $vertices
     * @param list<int>   $indices
     */
    private static function isEar(int $prevIdx, int $curIdx, int $nextIdx, array $vertices, array $indices): bool
    {
        $prev = $vertices[$prevIdx];
        $cur = $vertices[$curIdx];
        $next = $vertices[$nextIdx];

        // Reflex or collinear vertices cannot be ear tips.
        if (Cross::orientation($prev, $cur, $next) <= 0) {
            return false;
        }

        foreach ($indices as $index) {
            if ($index === $prevIdx || $index === $curIdx || $index === $nextIdx) {
                continue;
            }
            if (self::pointInTriangle($vertices[$index], $prev, $cur, $next)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Does $p lie inside or on triangle (a, b, c)? Uses the sign of the three
     * edge cross-products: a point strictly inside sees the same side of every
     * edge, so it never yields both a strictly-negative and a strictly-positive
     * orientation.
     */
    private static function pointInTriangle(Point $p, Point $a, Point $b, Point $c): bool
    {
        $d1 = Cross::orientation($a, $b, $p);
        $d2 = Cross::orientation($b, $c, $p);
        $d3 = Cross::orientation($c, $a, $p);

        $hasNegative = $d1 < 0 || $d2 < 0 || $d3 < 0;
        $hasPositive = $d1 > 0 || $d2 > 0 || $d3 > 0;

        return ! ($hasNegative && $hasPositive);
    }
}
