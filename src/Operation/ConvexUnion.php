<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Geometry\Polygon;

/**
 * Convex "union" of two polygons: the convex hull of the combined vertex set.
 *
 * DOCUMENTED SIMPLIFICATION: this is exact only when the true union is convex
 * (e.g. two overlapping/adjacent convex polygons whose union is convex).
 * Otherwise it returns the convex HULL of the union — a superset of the real
 * union. The README states this loudly.
 *
 * Ported from the KhPI archive: `algolist/convex_or.htm`.
 */
final class ConvexUnion
{
    public static function of(Polygon $a, Polygon $b): Polygon
    {
        return ConvexHull::of(array_merge($a->vertices, $b->vertices));
    }
}
