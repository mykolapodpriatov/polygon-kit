<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Measure\Centroid;
use PolygonKit\Measure\ConvexityTest;
use PolygonKit\Measure\Perimeter;
use PolygonKit\Measure\ShoelaceArea;
use PolygonKit\Predicate\Orientation;
use PolygonKit\Predicate\PointInPolygon;
use PolygonKit\Predicate\RayCasting;

/**
 * An immutable simple polygon: an implicitly-closed ring of >= 3 vertices
 * (the first vertex is NOT duplicated as the last).
 *
 * Construction is the only validation gate, so every downstream algorithm may
 * assume a well-formed ring.
 */
final readonly class Polygon
{
    /** @var list<Point> */
    public array $vertices;

    /**
     * @param array<Point> $vertices any-keyed array; normalised to a list
     */
    public function __construct(array $vertices)
    {
        $vertices = array_values($vertices);
        $count = count($vertices);
        if ($count < 3) {
            throw new InvalidPolygonException(
                sprintf('A polygon needs at least 3 vertices, got %d.', $count),
            );
        }

        for ($i = 0; $i < $count; $i++) {
            if ($vertices[$i]->equals($vertices[($i + 1) % $count])) {
                throw new InvalidPolygonException(
                    sprintf('Consecutive duplicate vertex at index %d.', $i),
                );
            }
        }

        $this->vertices = $vertices;
    }

    /**
     * @param array<array{float|int, float|int}> $coordinates
     */
    public static function fromArray(array $coordinates): self
    {
        return new self(array_map(
            static fn (array $pair): Point => new Point((float) $pair[0], (float) $pair[1]),
            array_values($coordinates),
        ));
    }

    public static function fromPoints(Point ...$points): self
    {
        return new self(array_values($points));
    }

    public function vertexCount(): int
    {
        return count($this->vertices);
    }

    public function signedArea(): float
    {
        return ShoelaceArea::signed($this);
    }

    public function area(): float
    {
        return ShoelaceArea::abs($this);
    }

    public function perimeter(): float
    {
        return Perimeter::of($this);
    }

    public function centroid(): Point
    {
        return Centroid::of($this);
    }

    public function orientation(): Orientation
    {
        return Orientation::of($this);
    }

    public function isConvex(): bool
    {
        return ConvexityTest::isConvex($this);
    }

    public function boundingBox(): BoundingBox
    {
        return BoundingBox::fromPoints(...$this->vertices);
    }

    public function containsPoint(Point $point, ?PointInPolygon $method = null): bool
    {
        $method ??= new RayCasting();

        // Fast reject via bounding box.
        if (! $this->boundingBox()->contains($point)) {
            return false;
        }

        return $method->contains($this, $point);
    }

    /**
     * Same ring, reversed winding (a new instance).
     */
    public function reversed(): self
    {
        return new self(array_reverse($this->vertices));
    }
}
