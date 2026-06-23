<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\Cross;
use PolygonKit\Math\FloatMath;
use PolygonKit\Predicate\Orientation;

/**
 * Intersection of two CONVEX polygons via the Sutherland–Hodgman algorithm:
 * clip the subject successively against each (half-plane) edge of the convex
 * clip polygon. Returns null when the result has fewer than 3 vertices
 * (disjoint or edge-touching).
 *
 * Ported from the KhPI archive: `algolist/convex_intersect.htm`.
 */
final class ConvexIntersection
{
    public static function of(Polygon $subject, Polygon $clip): ?Polygon
    {
        if (! $subject->isConvex() || ! $clip->isConvex()) {
            throw new GeometryException('ConvexIntersection requires both polygons to be convex.');
        }

        $output = self::toCounterClockwise($subject);
        $clipVerts = self::toCounterClockwise($clip);
        $clipCount = count($clipVerts);

        for ($e = 0; $e < $clipCount; $e++) {
            $a = $clipVerts[$e];
            $b = $clipVerts[($e + 1) % $clipCount];
            $input = $output;
            $output = [];
            $m = count($input);
            if ($m === 0) {
                return null;
            }

            for ($i = 0; $i < $m; $i++) {
                $cur = $input[$i];
                $prev = $input[($i - 1 + $m) % $m];
                $curIn = FloatMath::sign(Cross::orient2d($a, $b, $cur)) >= 0;
                $prevIn = FloatMath::sign(Cross::orient2d($a, $b, $prev)) >= 0;

                if ($curIn) {
                    if (! $prevIn) {
                        $output[] = self::lineIntersect($prev, $cur, $a, $b);
                    }
                    $output[] = $cur;
                } elseif ($prevIn) {
                    $output[] = self::lineIntersect($prev, $cur, $a, $b);
                }
            }
        }

        $cleaned = self::dropDuplicates($output);

        return count($cleaned) >= 3 ? new Polygon($cleaned) : null;
    }

    /**
     * Intersection of segment (p1->p2) with the infinite line (a->b).
     */
    private static function lineIntersect(Point $p1, Point $p2, Point $a, Point $b): Point
    {
        $a1 = $b->y - $a->y;
        $b1 = $a->x - $b->x;
        $c1 = $a1 * $a->x + $b1 * $a->y;

        $a2 = $p2->y - $p1->y;
        $b2 = $p1->x - $p2->x;
        $c2 = $a2 * $p1->x + $b2 * $p1->y;

        $det = $a1 * $b2 - $a2 * $b1;
        if (FloatMath::isZero($det)) {
            return $p2; // (near-)parallel; fall back to the segment endpoint
        }

        return new Point(
            ($b2 * $c1 - $b1 * $c2) / $det,
            ($a1 * $c2 - $a2 * $c1) / $det,
        );
    }

    /**
     * @return list<Point>
     */
    private static function toCounterClockwise(Polygon $polygon): array
    {
        return $polygon->orientation() === Orientation::Clockwise
            ? array_reverse($polygon->vertices)
            : $polygon->vertices;
    }

    /**
     * @param list<Point> $points
     * @return list<Point>
     */
    private static function dropDuplicates(array $points): array
    {
        $result = [];
        $count = count($points);
        for ($i = 0; $i < $count; $i++) {
            $next = $points[($i + 1) % $count];
            if (! $points[$i]->equals($next)) {
                $result[] = $points[$i];
            }
        }

        return $result;
    }
}
