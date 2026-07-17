<?php

declare(strict_types=1);

namespace PolygonKit\Geometry;

use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Math\FloatMath;
use PolygonKit\Measure\Centroid;
use PolygonKit\Measure\ConvexityTest;
use PolygonKit\Measure\Perimeter;
use PolygonKit\Measure\ShoelaceArea;
use PolygonKit\Predicate\Orientation;
use PolygonKit\Predicate\PointInPolygon;
use PolygonKit\Predicate\RayCasting;
use PolygonKit\Predicate\SimplicityTest;

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

    /**
     * Does this ring avoid self-intersection? The constructor does not enforce
     * this (it would be a breaking, O(n^2)-per-construction check), so callers
     * that accept untrusted input can opt in here before relying on the
     * area/centroid/orientation/containsPoint results.
     */
    public function isSimple(): bool
    {
        return SimplicityTest::isSimple($this);
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

    /**
     * Translate every vertex by ($dx, $dy) (a new instance).
     */
    public function withTranslation(float $dx, float $dy): self
    {
        return new self(array_map(
            static fn (Point $p): Point => $p->withTranslation($dx, $dy),
            $this->vertices,
        ));
    }

    /**
     * Rotate every vertex by $radians (counter-clockwise) around $about,
     * defaulting to the origin (a new instance).
     */
    public function withRotation(float $radians, ?Point $about = null): self
    {
        $about ??= new Point(0.0, 0.0);

        return new self(array_map(
            static fn (Point $p): Point => $p->withRotation($radians, $about),
            $this->vertices,
        ));
    }

    /**
     * Scale every vertex by $factor about $about (default: the origin), a new
     * instance. Negative factors are allowed: they point-reflect the ring
     * through $about (equivalently, a 180-degree rotation, so the winding is
     * preserved and the signed area scales by $factor^2). A zero or non-finite
     * factor is rejected because scaling by 0 collapses the ring onto a point.
     */
    public function withScale(float $factor, ?Point $about = null): self
    {
        if (! is_finite($factor) || FloatMath::isZero($factor)) {
            throw new InvalidPolygonException(sprintf(
                'Scale factor must be a finite non-zero number, got %s.',
                var_export($factor, true),
            ));
        }

        $about ??= new Point(0.0, 0.0);

        return new self(array_map(
            static fn (Point $p): Point => new Point(
                $about->x + ($p->x - $about->x) * $factor,
                $about->y + ($p->y - $about->y) * $factor,
            ),
            $this->vertices,
        ));
    }
}
