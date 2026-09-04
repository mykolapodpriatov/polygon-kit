<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Measure\ConvexityTest;
use PolygonKit\Predicate\Orientation;

/**
 * Hertel-Mehlhorn convex decomposition: a SIMPLE polygon into convex pieces.
 *
 * Every convex-only operation in this library (ConvexIntersection, ConvexUnion)
 * needs convex input, and most real geometry is not convex. Triangulation
 * already solves that in principle, since a triangle is convex, but a
 * triangulation of an L-shape is a pile of skinny triangles and anything built
 * on it inherits both the piece count and the floating-point error.
 *
 * Hertel-Mehlhorn starts from the {@see EarClipping} triangulation and deletes
 * a diagonal whenever removing it leaves the merged piece convex. It never
 * produces more than four times the minimum number of convex pieces, and the
 * result is a partition: the pieces tile the original polygon with no gaps and
 * no overlaps.
 *
 * A diagonal is any piece edge that is not an edge of the original ring, so
 * original boundary is never dissolved. Convexity is decided by
 * {@see ConvexityTest}, the same test {@see Polygon::isConvex()} uses, so
 * "convex" means one thing across the library.
 *
 * The input is validated by {@see EarClipping::triangulate()}: a
 * self-intersecting ring has no meaningful decomposition and throws a
 * {@see \PolygonKit\Exception\GeometryException}, exactly as it does for
 * triangulation.
 *
 * @see EarClipping
 */
final class ConvexDecomposition
{
    /**
     * Decompose $polygon into convex pieces.
     *
     * A polygon that is already convex comes back as a single piece with the
     * same vertices, counter-clockwise (the winding every piece is emitted in).
     *
     * @return list<Polygon> convex pieces, each CCW, whose areas sum to the
     *                       original area
     */
    public static function of(Polygon $polygon): array
    {
        // Triangulate first: this also enforces simplicity.
        $triangles = EarClipping::triangulate($polygon);

        // EarClipping works on a CCW copy of the ring and emits CCW triangles
        // built from those exact Point instances, so the same normalisation
        // here gives a vertex list every triangle indexes into.
        $vertices = $polygon->orientation() === Orientation::Clockwise
            ? array_reverse($polygon->vertices)
            : $polygon->vertices;

        $indexOf = [];
        foreach ($vertices as $i => $vertex) {
            $indexOf[self::pointKey($vertex)] = $i;
        }

        /** @var list<list<int>> $pieces */
        $pieces = [];
        foreach ($triangles as $triangle) {
            $ring = [];
            foreach ($triangle->vertices as $vertex) {
                $ring[] = $indexOf[self::pointKey($vertex)];
            }
            $pieces[] = $ring;
        }

        // Edges of the original ring may never be dissolved.
        $boundary = [];
        $n = count($vertices);
        for ($i = 0; $i < $n; $i++) {
            $boundary[self::edgeKey($i, ($i + 1) % $n)] = true;
        }

        $pieces = self::dissolveDiagonals($pieces, $boundary, $vertices);

        return array_map(
            static fn (array $ring): Polygon => self::toPolygon($ring, $vertices),
            $pieces,
        );
    }

    /**
     * Repeatedly remove a diagonal whose two pieces merge into a convex piece,
     * until no such diagonal is left.
     *
     * @param list<list<int>>     $pieces
     * @param array<string, true> $boundary
     * @param list<Point>         $vertices
     *
     * @return list<list<int>>
     */
    private static function dissolveDiagonals(array $pieces, array $boundary, array $vertices): array
    {
        while (true) {
            $merged = self::mergeOnce($pieces, $boundary, $vertices);
            if ($merged === null) {
                return $pieces;
            }
            $pieces = $merged;
        }
    }

    /**
     * Find one removable diagonal and return the piece list with it removed,
     * or null when none is left.
     *
     * @param list<list<int>>     $pieces
     * @param array<string, true> $boundary
     * @param list<Point>         $vertices
     *
     * @return list<list<int>>|null
     */
    private static function mergeOnce(array $pieces, array $boundary, array $vertices): ?array
    {
        // Diagonal edge -> the pieces on either side of it. A diagonal of a
        // partition always has exactly two; anything else is not removable.
        /** @var array<string, list<int>> $owners */
        $owners = [];
        foreach ($pieces as $pieceIndex => $ring) {
            $m = count($ring);
            for ($i = 0; $i < $m; $i++) {
                $key = self::edgeKey($ring[$i], $ring[($i + 1) % $m]);
                if (isset($boundary[$key])) {
                    continue;
                }
                $owners[$key][] = $pieceIndex;
            }
        }

        foreach ($owners as $key => $sides) {
            if (count($sides) !== 2) {
                continue;
            }
            [$left, $right] = $sides;
            [$a, $b] = self::edgeEndpoints($key);
            $candidate = self::mergeAcross($pieces[$left], $pieces[$right], $a, $b);
            if ($candidate === null) {
                continue;
            }
            if (! ConvexityTest::isConvex(self::toPolygon($candidate, $vertices))) {
                continue;
            }

            $pieces[$left] = $candidate;
            unset($pieces[$right]);

            return array_values($pieces);
        }

        return null;
    }

    /**
     * Merge two rings that share the edge (a, b) into one ring, dropping that
     * edge. Both rings wind the same way, so the shared edge runs a -> b in one
     * and b -> a in the other. Returns null if they do not actually share it,
     * which would mean the piece list is not a partition.
     *
     * @param list<int> $left
     * @param list<int> $right
     *
     * @return list<int>|null
     */
    private static function mergeAcross(array $left, array $right, int $a, int $b): ?array
    {
        $forward = self::directedEdgeIndex($left, $a, $b);
        $backward = self::directedEdgeIndex($right, $b, $a);
        if ($forward === null || $backward === null) {
            // The edge runs the other way round; swap and retry once.
            $forward = self::directedEdgeIndex($left, $b, $a);
            $backward = self::directedEdgeIndex($right, $a, $b);
            if ($forward === null || $backward === null) {
                return null;
            }
            [$a, $b] = [$b, $a];
        }

        // Rotate so $leftRing runs b ... a and $rightRing runs a ... b: the two
        // halves of the merged boundary, with the shared edge at neither end.
        $leftRing = self::rotate($left, ($forward + 1) % count($left));
        $rightRing = self::rotate($right, ($backward + 1) % count($right));

        // $rightRing's first vertex repeats $leftRing's last, and its last
        // repeats $leftRing's first, so both are dropped.
        return array_merge($leftRing, array_slice($rightRing, 1, count($rightRing) - 2));
    }

    /**
     * Position of the directed edge $from -> $to in $ring, or null.
     *
     * @param list<int> $ring
     */
    private static function directedEdgeIndex(array $ring, int $from, int $to): ?int
    {
        $m = count($ring);
        for ($i = 0; $i < $m; $i++) {
            if ($ring[$i] === $from && $ring[($i + 1) % $m] === $to) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<int> $ring
     *
     * @return list<int>
     */
    private static function rotate(array $ring, int $start): array
    {
        return array_merge(array_slice($ring, $start), array_slice($ring, 0, $start));
    }

    /**
     * @param list<int>   $ring
     * @param list<Point> $vertices
     */
    private static function toPolygon(array $ring, array $vertices): Polygon
    {
        return new Polygon(array_map(static fn (int $i): Point => $vertices[$i], $ring));
    }

    /**
     * Order-independent key for the edge between two vertex indices.
     */
    private static function edgeKey(int $a, int $b): string
    {
        return $a < $b ? $a.':'.$b : $b.':'.$a;
    }

    /**
     * @return array{int, int}
     */
    private static function edgeEndpoints(string $key): array
    {
        [$a, $b] = explode(':', $key, 2);

        return [(int) $a, (int) $b];
    }

    /**
     * Exact key for a vertex. The triangulation reuses the very Point instances
     * from the normalised ring, so the coordinates match bit for bit and no
     * tolerance is involved (or wanted: two distinct vertices must never
     * collapse onto one index).
     */
    private static function pointKey(Point $point): string
    {
        return sprintf('%.17g,%.17g', $point->x, $point->y);
    }
}
