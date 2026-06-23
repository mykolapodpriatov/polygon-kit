<?php

declare(strict_types=1);

namespace PolygonKit\Math;

use PolygonKit\Geometry\Point;

/**
 * 2D cross-product primitives.
 *
 * `orient2d` is the orientation predicate: the signed area of the parallelogram
 * spanned by (b - a) and (c - a). It is exact for integer-valued coordinates up
 * to 2^53; for general floats it is exact only within float precision.
 */
final class Cross
{
    /**
     * Signed twice-area of triangle (a, b, c).
     *  > 0  -> c is left of a->b (counter-clockwise turn)
     *  < 0  -> c is right of a->b (clockwise turn)
     *  == 0 -> collinear
     */
    public static function orient2d(Point $a, Point $b, Point $c): float
    {
        return ($b->x - $a->x) * ($c->y - $a->y)
            - ($b->y - $a->y) * ($c->x - $a->x);
    }

    /**
     * Orientation sign of triangle (a, b, c): -1, 0 or 1 (tolerance-aware).
     */
    public static function orientation(Point $a, Point $b, Point $c): int
    {
        return FloatMath::sign(self::orient2d($a, $b, $c));
    }
}
