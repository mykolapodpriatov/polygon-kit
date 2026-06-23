<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\Cross;

/**
 * Ramer–Douglas–Peucker polygon simplification.
 *
 * Reduces vertex count while keeping the shape within $epsilon. Operates on the
 * open vertex chain (first/last preserved) and guarantees at least 3 output
 * vertices — if simplification would drop below 3, the input is returned.
 */
final class Simplify
{
    public static function douglasPeucker(Polygon $polygon, float $epsilon): Polygon
    {
        if ($epsilon <= 0.0) {
            return $polygon;
        }

        $points = $polygon->vertices;
        $simplified = self::recurse($points, $epsilon);

        // The open-chain pass preserves the first/last vertices, which can leave
        // a redundant collinear vertex at the ring's closing seam — drop it.
        $simplified = self::removeCollinear($simplified);

        if (count($simplified) < 3) {
            return $polygon;
        }

        return new Polygon($simplified);
    }

    /**
     * @param list<Point> $points
     * @return list<Point>
     */
    private static function recurse(array $points, float $epsilon): array
    {
        $count = count($points);
        if ($count < 3) {
            return $points;
        }

        $first = $points[0];
        $last = $points[$count - 1];

        $maxDistance = 0.0;
        $index = 0;
        for ($i = 1; $i < $count - 1; $i++) {
            $distance = self::perpendicularDistance($points[$i], $first, $last);
            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $index = $i;
            }
        }

        if ($maxDistance <= $epsilon) {
            return [$first, $last];
        }

        $left = self::recurse(array_slice($points, 0, $index + 1), $epsilon);
        $right = self::recurse(array_slice($points, $index), $epsilon);

        // Drop the duplicated join vertex.
        array_pop($left);

        return array_merge($left, $right);
    }

    /**
     * Drop vertices that are collinear with their neighbours on the closed ring.
     *
     * @param list<Point> $ring
     * @return list<Point>
     */
    private static function removeCollinear(array $ring): array
    {
        $n = count($ring);
        if ($n < 4) {
            return $ring;
        }

        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $prev = $ring[($i - 1 + $n) % $n];
            $cur = $ring[$i];
            $next = $ring[($i + 1) % $n];
            if (Cross::orientation($prev, $cur, $next) !== 0) {
                $result[] = $cur;
            }
        }

        return count($result) >= 3 ? $result : $ring;
    }

    private static function perpendicularDistance(Point $p, Point $a, Point $b): float
    {
        $dx = $b->x - $a->x;
        $dy = $b->y - $a->y;
        $denom = hypot($dx, $dy);
        if ($denom === 0.0) {
            return $p->distanceTo($a);
        }

        return abs($dy * $p->x - $dx * $p->y + $b->x * $a->y - $b->y * $a->x) / $denom;
    }
}
