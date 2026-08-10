<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Property;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\ConvexHull;
use PolygonKit\Operation\ConvexIntersection;

/**
 * For convex-only pairs, `Polygon::intersects()` must agree with
 * `ConvexIntersection::of() !== null` - the general-purpose boolean overlap
 * test should never disagree with the convex-only intersection-geometry
 * builder about whether an overlap exists at all. In the spirit of the
 * existing ray-casting/winding-number cross-check.
 */
final class PolygonOverlapConsistencyTest extends TestCase
{
    public function testIntersectsAgreesWithConvexIntersectionOverRandomPairs(): void
    {
        mt_srand(20260810);

        $checked = 0;
        $overlapping = 0;
        $disjoint = 0;

        for ($trial = 0; $trial < 200; $trial++) {
            $a = $this->randomConvexPolygon();
            $b = $this->randomConvexPolygon();

            $expected = ConvexIntersection::of($a, $b) !== null;
            $actual = $a->intersects($b);

            self::assertSame(
                $expected,
                $actual,
                sprintf('Disagreement on trial %d.', $trial),
            );

            $expected ? $overlapping++ : $disjoint++;
            $checked++;
        }

        self::assertSame(200, $checked);
        // Sanity check that the random pairs actually exercise both branches.
        self::assertGreaterThan(0, $overlapping);
        self::assertGreaterThan(0, $disjoint);
    }

    private function randomConvexPolygon(): Polygon
    {
        $points = [];
        $count = mt_rand(4, 10);
        // Small polygons in a shared [0, 10] x [0, 10] neighbourhood so that,
        // across trials, some pairs overlap and others are disjoint.
        $centerX = (mt_rand() / mt_getrandmax()) * 10.0;
        $centerY = (mt_rand() / mt_getrandmax()) * 10.0;
        for ($i = 0; $i < $count; $i++) {
            $points[] = new Point(
                $centerX + ((mt_rand() / mt_getrandmax()) - 0.5) * 4.0,
                $centerY + ((mt_rand() / mt_getrandmax()) - 0.5) * 4.0,
            );
        }

        return ConvexHull::of($points);
    }
}
