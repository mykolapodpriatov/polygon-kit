<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Polygon;

/**
 * Polygon perimeter: sum of edge lengths over the closed ring.
 */
final class Perimeter
{
    public static function of(Polygon $polygon): float
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sum += $vertices[$i]->distanceTo($vertices[($i + 1) % $n]);
        }

        return $sum;
    }
}
