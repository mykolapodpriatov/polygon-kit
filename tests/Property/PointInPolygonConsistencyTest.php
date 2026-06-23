<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Property;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Predicate\RayCasting;
use PolygonKit\Predicate\WindingNumber;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

/**
 * The two independent point-in-polygon implementations must agree on every
 * point, so each validates the other.
 */
final class PointInPolygonConsistencyTest extends TestCase
{
    public function testRayCastingAndWindingAgreeOverGrid(): void
    {
        $polygons = [
            PolygonFixtures::bigSquare(),
            PolygonFixtures::pentagon(),
            PolygonFixtures::lShape(),
        ];
        $ray = new RayCasting();
        $wind = new WindingNumber();

        $checked = 0;
        foreach ($polygons as $polygon) {
            foreach ($this->gridPoints($polygon) as $point) {
                self::assertSame(
                    $ray->contains($polygon, $point),
                    $wind->contains($polygon, $point),
                    sprintf('Disagreement at (%g, %g)', $point->x, $point->y),
                );
                $checked++;
            }
        }

        self::assertGreaterThan(100, $checked);
    }

    /**
     * @return iterable<Point>
     */
    private function gridPoints(Polygon $polygon): iterable
    {
        $box = $polygon->boundingBox();
        // Irrational-ish step to mostly avoid landing exactly on edges/vertices.
        for ($i = -2; $i <= 12; $i++) {
            for ($j = -2; $j <= 12; $j++) {
                yield new Point(
                    $box->minX + $i * 0.737,
                    $box->minY + $j * 0.613,
                );
            }
        }
    }
}
