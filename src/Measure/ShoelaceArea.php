<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Polygon;

/**
 * Polygon area via the shoelace (Gauss) formula.
 *
 * Ported from the KhPI archive: `algolist/area.htm`, "Площадь.htm", and
 * `ario::area(mypoints&, int n)` in `Diplom 7.0/A1.h`.
 */
final class ShoelaceArea
{
    /**
     * Signed area: positive for a counter-clockwise ring, negative for clockwise.
     */
    public static function signed(Polygon $polygon): float
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $current = $vertices[$i];
            $next = $vertices[($i + 1) % $n];
            $sum += $current->x * $next->y - $next->x * $current->y;
        }

        return $sum / 2.0;
    }

    public static function abs(Polygon $polygon): float
    {
        return abs(self::signed($polygon));
    }
}
