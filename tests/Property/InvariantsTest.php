<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Property;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\ConvexHull;

/**
 * Geometric invariants checked over many seeded random convex polygons —
 * the headline quality signal for a port like this.
 */
final class InvariantsTest extends TestCase
{
    public function testAreaIsInvariantUnderTranslationAndRotation(): void
    {
        mt_srand(20260624);

        for ($trial = 0; $trial < 50; $trial++) {
            $polygon = $this->randomConvexPolygon();
            $area = $polygon->area();

            $angle = (mt_rand() / mt_getrandmax()) * 2 * M_PI;
            $about = new Point(3.0, -2.0);
            $moved = new Polygon(array_map(
                static fn (Point $p): Point => $p
                    ->withRotation($angle, $about)
                    ->withTranslation(7.5, -4.25),
                $polygon->vertices,
            ));

            self::assertEqualsWithDelta($area, $moved->area(), 1e-6);
        }
    }

    public function testReversingFlipsSignedAreaSign(): void
    {
        mt_srand(777);

        for ($trial = 0; $trial < 25; $trial++) {
            $polygon = $this->randomConvexPolygon();
            self::assertEqualsWithDelta(
                $polygon->signedArea(),
                -$polygon->reversed()->signedArea(),
                1e-9,
            );
        }
    }

    private function randomConvexPolygon(): Polygon
    {
        $points = [];
        $count = mt_rand(8, 20);
        for ($i = 0; $i < $count; $i++) {
            $points[] = new Point(
                (mt_rand() / mt_getrandmax()) * 100.0,
                (mt_rand() / mt_getrandmax()) * 100.0,
            );
        }

        return ConvexHull::of($points);
    }
}
