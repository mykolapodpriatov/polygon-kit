<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Math\Cross;
use PolygonKit\Math\FloatMath;

/**
 * An immutable line segment between two points.
 */
final readonly class Segment
{
    public function __construct(
        public Point $a,
        public Point $b,
    ) {
    }

    public function length(): float
    {
        return $this->a->distanceTo($this->b);
    }

    /**
     * Shortest distance from $point to this (closed) segment.
     *
     * Projects $point onto the segment's supporting line, clamps the projection
     * parameter to `[0, 1]` so the nearest point stays on the segment, then
     * returns the distance to it. A zero-length segment degenerates to the
     * distance to its (shared) endpoint.
     */
    public function distanceToPoint(Point $point): float
    {
        $dx = $this->b->x - $this->a->x;
        $dy = $this->b->y - $this->a->y;
        $lengthSquared = $dx * $dx + $dy * $dy;

        if (FloatMath::isZero($lengthSquared)) {
            return $point->distanceTo($this->a);
        }

        $t = (($point->x - $this->a->x) * $dx + ($point->y - $this->a->y) * $dy) / $lengthSquared;
        $t = max(0.0, min(1.0, $t));

        $closest = new Point($this->a->x + $t * $dx, $this->a->y + $t * $dy);

        return $point->distanceTo($closest);
    }

    /**
     * Proper intersection point of two segments, or null when they do not
     * cross at a single point (parallel, collinear, or disjoint).
     */
    public function intersectionWith(self $other): ?Point
    {
        $p = $this->a;
        $r = new Point($this->b->x - $this->a->x, $this->b->y - $this->a->y);
        $q = $other->a;
        $s = new Point($other->b->x - $other->a->x, $other->b->y - $other->a->y);

        $rxs = $r->x * $s->y - $r->y * $s->x;
        if (FloatMath::isZero($rxs)) {
            return null; // parallel or collinear
        }

        $qp = new Point($q->x - $p->x, $q->y - $p->y);
        $t = ($qp->x * $s->y - $qp->y * $s->x) / $rxs;
        $u = ($qp->x * $r->y - $qp->y * $r->x) / $rxs;

        if (FloatMath::gte($t, 0.0) && FloatMath::lte($t, 1.0)
            && FloatMath::gte($u, 0.0) && FloatMath::lte($u, 1.0)
        ) {
            return new Point($p->x + $t * $r->x, $p->y + $t * $r->y);
        }

        return null;
    }

    /**
     * Is $point on this (closed) segment, within tolerance?
     */
    public function contains(Point $point): bool
    {
        if (FloatMath::sign(Cross::orient2d($this->a, $this->b, $point)) !== 0) {
            return false;
        }

        return FloatMath::gte($point->x, min($this->a->x, $this->b->x))
            && FloatMath::lte($point->x, max($this->a->x, $this->b->x))
            && FloatMath::gte($point->y, min($this->a->y, $this->b->y))
            && FloatMath::lte($point->y, max($this->a->y, $this->b->y));
    }
}
