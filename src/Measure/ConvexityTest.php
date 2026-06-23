<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\Cross;

/**
 * Convexity test via cross-product sign consistency.
 *
 * A simple polygon is convex iff every non-zero turn (cross product of
 * consecutive edges) shares the same sign. Collinear vertices are tolerated.
 *
 * Ported from the KhPI archive: "Определение выпуклый многоугольник или нет.htm"
 * and `ario::Convex(mypoints&, int n)` in `Diplom 7.0/A1.h`.
 */
final class ConvexityTest
{
    public static function isConvex(Polygon $polygon): bool
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);
        if ($n < 3) {
            return false;
        }

        $sign = 0;
        for ($i = 0; $i < $n; $i++) {
            $a = $vertices[$i];
            $b = $vertices[($i + 1) % $n];
            $c = $vertices[($i + 2) % $n];
            $turn = Cross::orientation($a, $b, $c);
            if ($turn === 0) {
                continue;
            }
            if ($sign === 0) {
                $sign = $turn;
            } elseif ($turn !== $sign) {
                return false;
            }
        }

        return true;
    }
}
