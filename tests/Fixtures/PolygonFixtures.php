<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Fixtures;

use PolygonKit\Geometry\Polygon;

/**
 * Reusable test polygons (CCW unless noted).
 */
final class PolygonFixtures
{
    /** Unit square, CCW, area 1. */
    public static function unitSquare(): Polygon
    {
        return Polygon::fromArray([[0, 0], [1, 0], [1, 1], [0, 1]]);
    }

    /** 10x10 square, CCW, area 100. */
    public static function bigSquare(): Polygon
    {
        return Polygon::fromArray([[0, 0], [10, 0], [10, 10], [0, 10]]);
    }

    /** Unit square wound clockwise. */
    public static function clockwiseSquare(): Polygon
    {
        return Polygon::fromArray([[0, 0], [0, 1], [1, 1], [1, 0]]);
    }

    /** Right triangle, area 0.5. */
    public static function triangle(): Polygon
    {
        return Polygon::fromArray([[0, 0], [1, 0], [0, 1]]);
    }

    /** Concave L-shape, area 3. */
    public static function lShape(): Polygon
    {
        return Polygon::fromArray([[0, 0], [2, 0], [2, 1], [1, 1], [1, 2], [0, 2]]);
    }

    /** Convex pentagon. */
    public static function pentagon(): Polygon
    {
        return Polygon::fromArray([[0, 0], [2, 0], [3, 2], [1, 3], [-1, 2]]);
    }
}
