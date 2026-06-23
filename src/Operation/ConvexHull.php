<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\Cross;

/**
 * Andrew's monotone-chain convex hull, O(n log n). Returns a CCW polygon.
 * Degenerate (all-collinear / fewer than 3 distinct hull points) input throws.
 */
final class ConvexHull
{
    /**
     * @param list<Point> $points
     */
    public static function of(array $points): Polygon
    {
        $unique = self::dedupe($points);
        if (count($unique) < 3) {
            throw new GeometryException('Convex hull needs at least 3 non-collinear points.');
        }

        usort(
            $unique,
            static fn (Point $a, Point $b): int => $a->x <=> $b->x ?: $a->y <=> $b->y,
        );

        $lower = self::halfHull($unique);
        $upper = self::halfHull(array_reverse($unique));

        // Drop each half's last point (it is the first of the other half).
        array_pop($lower);
        array_pop($upper);
        $hull = array_merge($lower, $upper);

        if (count($hull) < 3) {
            throw new GeometryException('Convex hull is degenerate (all points collinear).');
        }

        return new Polygon($hull);
    }

    /**
     * @param list<Point> $sorted
     * @return list<Point>
     */
    private static function halfHull(array $sorted): array
    {
        $chain = [];
        foreach ($sorted as $p) {
            while (
                count($chain) >= 2
                && Cross::orientation($chain[count($chain) - 2], $chain[count($chain) - 1], $p) <= 0
            ) {
                array_pop($chain);
            }
            $chain[] = $p;
        }

        return $chain;
    }

    /**
     * @param list<Point> $points
     * @return list<Point>
     */
    private static function dedupe(array $points): array
    {
        $unique = [];
        foreach ($points as $p) {
            foreach ($unique as $seen) {
                if ($seen->equals($p)) {
                    continue 2;
                }
            }
            $unique[] = $p;
        }

        return $unique;
    }
}
