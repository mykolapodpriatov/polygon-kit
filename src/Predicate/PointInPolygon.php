<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;

/**
 * A strategy for testing whether a point lies inside (or on the boundary of)
 * a polygon. Two independent implementations exist and must agree (tested),
 * so each validates the other.
 */
interface PointInPolygon
{
    public function contains(Polygon $polygon, Point $point): bool;
}
