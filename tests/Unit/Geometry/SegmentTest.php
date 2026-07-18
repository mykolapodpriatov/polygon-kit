<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Geometry;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Segment;

final class SegmentTest extends TestCase
{
    public function testDistanceToPointPerpendicularWithinSpan(): void
    {
        $segment = new Segment(new Point(0, 0), new Point(10, 0));

        self::assertEqualsWithDelta(4.0, $segment->distanceToPoint(new Point(5, 4)), 1e-9);
    }

    public function testDistanceToPointOnSegmentIsZero(): void
    {
        $segment = new Segment(new Point(0, 0), new Point(10, 0));

        self::assertSame(0.0, $segment->distanceToPoint(new Point(3, 0)));
    }

    public function testDistanceToPointClampsBeyondEndpoint(): void
    {
        $segment = new Segment(new Point(0, 0), new Point(10, 0));

        // Projection parameter > 1, so the nearest point is the endpoint (10, 0).
        self::assertEqualsWithDelta(5.0, $segment->distanceToPoint(new Point(13, 4)), 1e-9);
    }

    public function testDistanceToPointClampsBeforeStart(): void
    {
        $segment = new Segment(new Point(0, 0), new Point(10, 0));

        // Projection parameter < 0, so the nearest point is the start (0, 0).
        self::assertEqualsWithDelta(5.0, $segment->distanceToPoint(new Point(-3, 4)), 1e-9);
    }

    public function testDistanceToPointDegenerateZeroLengthSegment(): void
    {
        $segment = new Segment(new Point(2, 2), new Point(2, 2));

        self::assertEqualsWithDelta(hypot(3.0, 4.0), $segment->distanceToPoint(new Point(5, 6)), 1e-9);
    }
}
