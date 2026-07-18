<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Math\FloatMath;

/**
 * An immutable circle: a center point and a non-negative radius. A zero radius
 * degenerates to a single point (a valid enclosing circle of one vertex).
 */
final readonly class Circle
{
    public function __construct(
        public Point $center,
        public float $radius,
    ) {
        if (is_nan($radius) || is_infinite($radius) || $radius < 0.0) {
            throw new GeometryException('Circle radius must be a finite, non-negative number.');
        }
    }

    /**
     * Does $point lie inside or on this circle, within tolerance?
     */
    public function contains(Point $point, float $eps = FloatMath::EPSILON): bool
    {
        return FloatMath::lte($this->center->distanceTo($point), $this->radius, $eps);
    }

    public function diameter(): float
    {
        return $this->radius * 2.0;
    }

    public function area(): float
    {
        return M_PI * $this->radius * $this->radius;
    }

    public function circumference(): float
    {
        return 2.0 * M_PI * $this->radius;
    }
}
