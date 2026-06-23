<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;
use PolygonKit\Measure\ShoelaceArea;

/**
 * Winding orientation of a polygon, derived from the sign of its signed area.
 *
 * Ported from the KhPI archive: `algolist/orient.htm` and
 * "Нахождение ориентации простого многоугольника.htm".
 */
enum Orientation
{
    case Clockwise;
    case CounterClockwise;
    case Degenerate;

    public static function of(Polygon $polygon): self
    {
        return self::fromSignedArea(ShoelaceArea::signed($polygon));
    }

    public static function fromSignedArea(float $signedArea): self
    {
        return match (FloatMath::sign($signedArea)) {
            1 => self::CounterClockwise,
            -1 => self::Clockwise,
            default => self::Degenerate,
        };
    }
}
