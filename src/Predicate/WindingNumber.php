<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;
use PolygonKit\Math\Cross;

/**
 * Non-zero winding-number point-in-polygon test (Dan Sunday's algorithm),
 * robust for self-touching boundaries. Boundary points return true.
 */
final class WindingNumber implements PointInPolygon
{
    public function contains(Polygon $polygon, Point $point): bool
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);

        for ($i = 0; $i < $n; $i++) {
            if ((new Segment($vertices[$i], $vertices[($i + 1) % $n]))->contains($point)) {
                return true;
            }
        }

        $winding = 0;
        for ($i = 0; $i < $n; $i++) {
            $vi = $vertices[$i];
            $vj = $vertices[($i + 1) % $n];
            if ($vi->y <= $point->y) {
                if ($vj->y > $point->y && Cross::orientation($vi, $vj, $point) > 0) {
                    $winding++;
                }
            } else {
                if ($vj->y <= $point->y && Cross::orientation($vi, $vj, $point) < 0) {
                    $winding--;
                }
            }
        }

        return $winding !== 0;
    }
}
