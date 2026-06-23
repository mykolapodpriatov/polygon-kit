<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;

/**
 * Area-weighted polygon centroid.
 *
 * Falls back to the arithmetic mean of vertices when the polygon is degenerate
 * (near-zero area / all-collinear), which the README documents.
 *
 * Ported from the KhPI archive: "Центр тяжести.htm".
 */
final class Centroid
{
    public static function of(Polygon $polygon): Point
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);

        $signedArea = 0.0;
        $cx = 0.0;
        $cy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $current = $vertices[$i];
            $next = $vertices[($i + 1) % $n];
            $cross = $current->x * $next->y - $next->x * $current->y;
            $signedArea += $cross;
            $cx += ($current->x + $next->x) * $cross;
            $cy += ($current->y + $next->y) * $cross;
        }
        $signedArea /= 2.0;

        if (FloatMath::isZero($signedArea)) {
            return self::vertexMean($vertices);
        }

        return new Point(
            $cx / (6.0 * $signedArea),
            $cy / (6.0 * $signedArea),
        );
    }

    /**
     * @param list<Point> $vertices
     */
    private static function vertexMean(array $vertices): Point
    {
        $n = count($vertices);
        $sx = 0.0;
        $sy = 0.0;
        foreach ($vertices as $v) {
            $sx += $v->x;
            $sy += $v->y;
        }

        return new Point($sx / $n, $sy / $n);
    }
}
