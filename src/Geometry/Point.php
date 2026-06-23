<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Math\FloatMath;

/**
 * An immutable 2D point. Rejects NaN/INF coordinates at construction.
 */
final readonly class Point
{
    public function __construct(
        public float $x,
        public float $y,
    ) {
        if (is_nan($x) || is_infinite($x) || is_nan($y) || is_infinite($y)) {
            throw new InvalidPolygonException('Point coordinates must be finite numbers.');
        }
    }

    public function withTranslation(float $dx, float $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy);
    }

    /**
     * Rotate this point by $radians around $about (counter-clockwise).
     */
    public function withRotation(float $radians, self $about): self
    {
        $cos = cos($radians);
        $sin = sin($radians);
        $dx = $this->x - $about->x;
        $dy = $this->y - $about->y;

        return new self(
            $about->x + $dx * $cos - $dy * $sin,
            $about->y + $dx * $sin + $dy * $cos,
        );
    }

    public function distanceTo(self $other): float
    {
        return hypot($this->x - $other->x, $this->y - $other->y);
    }

    public function equals(self $other, float $eps = FloatMath::EPSILON): bool
    {
        return FloatMath::eq($this->x, $other->x, $eps)
            && FloatMath::eq($this->y, $other->y, $eps);
    }

    /**
     * @return array{float, float}
     */
    public function toArray(): array
    {
        return [$this->x, $this->y];
    }
}
