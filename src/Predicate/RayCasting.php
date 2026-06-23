<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;

/**
 * Even-odd ray-casting point-in-polygon test.
 *
 * Boundary points are handled by an explicit on-edge pre-check (returns true).
 * The half-open edge rule `(yi > y) != (yj > y)` avoids double-counting a ray
 * that passes exactly through a vertex.
 */
final class RayCasting implements PointInPolygon
{
    public function contains(Polygon $polygon, Point $point): bool
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);

        // Boundary is treated as inside.
        for ($i = 0; $i < $n; $i++) {
            if ((new Segment($vertices[$i], $vertices[($i + 1) % $n]))->contains($point)) {
                return true;
            }
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $vi = $vertices[$i];
            $vj = $vertices[$j];
            $intersects = ($vi->y > $point->y) !== ($vj->y > $point->y);
            if ($intersects) {
                $xCross = ($vj->x - $vi->x) * ($point->y - $vi->y) / ($vj->y - $vi->y) + $vi->x;
                if ($point->x < $xCross) {
                    $inside = ! $inside;
                }
            }
        }

        return $inside;
    }
}
