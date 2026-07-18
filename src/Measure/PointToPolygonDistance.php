<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;

/**
 * Shortest distance from a point to a polygon.
 *
 * Returns `0.0` when the point is inside the polygon or on its boundary
 * (including at a vertex); otherwise the distance to the nearest edge, i.e. the
 * minimum over every edge of {@see Segment::distanceToPoint()}.
 */
final class PointToPolygonDistance
{
    public static function of(Polygon $polygon, Point $point): float
    {
        // Inside or on the boundary (the point-in-polygon test treats an on-edge
        // point as contained) means zero distance.
        if ($polygon->containsPoint($point)) {
            return 0.0;
        }

        $vertices = $polygon->vertices;
        $n = count($vertices);
        $min = INF;
        for ($i = 0; $i < $n; $i++) {
            $edge = new Segment($vertices[$i], $vertices[($i + 1) % $n]);
            $min = min($min, $edge->distanceToPoint($point));
        }

        return $min;
    }
}
