<?php

declare(strict_types=1);

namespace PolygonKit\Measure;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;

/**
 * Nearest point on a polygon to a query point.
 *
 * Same inside/boundary contract as {@see PointToPolygonDistance}: a contained
 * point (including on an edge or vertex) is returned unchanged. Outside, the
 * result is the projection onto the nearest closed-ring edge via
 * {@see Segment::closestPoint()}. Equidistant edges keep the lowest edge index
 * so the choice is deterministic.
 */
final class ClosestPoint
{
    public static function of(Polygon $polygon, Point $point): Point
    {
        if ($polygon->containsPoint($point)) {
            return $point;
        }

        $vertices = $polygon->vertices;
        $n = count($vertices);

        $best = $vertices[0];
        $bestDistance = INF;
        for ($i = 0; $i < $n; $i++) {
            $edge = new Segment($vertices[$i], $vertices[($i + 1) % $n]);
            $candidate = $edge->closestPoint($point);
            $distance = $point->distanceTo($candidate);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $best;
    }
}
