<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Math\FloatMath;

/**
 * An immutable axis-aligned bounding box. Used as a fast reject before O(n)
 * point-in-polygon queries.
 */
final readonly class BoundingBox
{
    public function __construct(
        public float $minX,
        public float $minY,
        public float $maxX,
        public float $maxY,
    ) {
        if ($minX > $maxX || $minY > $maxY) {
            throw new GeometryException('BoundingBox min must not exceed max.');
        }
    }

    public static function fromPoints(Point ...$points): self
    {
        if ($points === []) {
            throw new GeometryException('Cannot build a bounding box from zero points.');
        }

        $minX = $maxX = $points[0]->x;
        $minY = $maxY = $points[0]->y;
        foreach ($points as $p) {
            $minX = min($minX, $p->x);
            $minY = min($minY, $p->y);
            $maxX = max($maxX, $p->x);
            $maxY = max($maxY, $p->y);
        }

        return new self($minX, $minY, $maxX, $maxY);
    }

    public function contains(Point $point): bool
    {
        return FloatMath::gte($point->x, $this->minX)
            && FloatMath::lte($point->x, $this->maxX)
            && FloatMath::gte($point->y, $this->minY)
            && FloatMath::lte($point->y, $this->maxY);
    }

    public function intersects(self $other): bool
    {
        return ! (
            FloatMath::lt($this->maxX, $other->minX)
            || FloatMath::gt($this->minX, $other->maxX)
            || FloatMath::lt($this->maxY, $other->minY)
            || FloatMath::gt($this->minY, $other->maxY)
        );
    }

    public function width(): float
    {
        return $this->maxX - $this->minX;
    }

    public function height(): float
    {
        return $this->maxY - $this->minY;
    }

    public function area(): float
    {
        return $this->width() * $this->height();
    }
}
