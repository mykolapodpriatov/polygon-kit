<?php

declare(strict_types=1);

namespace PolygonKit\Operation;

use PolygonKit\Geometry\Circle;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;

/**
 * Smallest enclosing circle of a polygon's vertices via Welzl's algorithm, in
 * its iterative three-nested-loop form (expected O(n)).
 *
 * The vertices are first passed through a deterministic, self-contained shuffle
 * so the expected-linear running time holds regardless of the input's spatial
 * ordering, while the result stays fully reproducible and independent of PHP's
 * global RNG state. The returned circle satisfies the invariant that every
 * vertex lies within `radius + EPSILON` of its center.
 *
 * Collinear / degenerate vertex sets are handled by the two-point fallback in
 * the circumcircle step, so a flat polygon yields the circle spanning its two
 * extreme vertices rather than diverging.
 */
final class MinimumBoundingCircle
{
    public static function of(Polygon $polygon): Circle
    {
        $points = self::shuffle($polygon->vertices);
        $n = count($points);

        // A Polygon always has >= 3 vertices, so a two-point seed is safe.
        $circle = self::circleFrom2($points[0], $points[1]);

        for ($i = 2; $i < $n; $i++) {
            if ($circle->contains($points[$i])) {
                continue;
            }

            // points[i] must lie on the boundary of the enclosing circle.
            $circle = self::circleFrom2($points[$i], $points[0]);
            for ($j = 1; $j < $i; $j++) {
                if ($circle->contains($points[$j])) {
                    continue;
                }

                // points[i] and points[j] both on the boundary.
                $circle = self::circleFrom2($points[$i], $points[$j]);
                for ($k = 0; $k < $j; $k++) {
                    if ($circle->contains($points[$k])) {
                        continue;
                    }

                    // Three boundary points determine the circle uniquely.
                    $circle = self::circleFrom3($points[$i], $points[$j], $points[$k]);
                }
            }
        }

        return $circle;
    }

    /**
     * Circle having segment (a, b) as a diameter.
     */
    private static function circleFrom2(Point $a, Point $b): Circle
    {
        $center = new Point(($a->x + $b->x) / 2.0, ($a->y + $b->y) / 2.0);

        return new Circle($center, $a->distanceTo($b) / 2.0);
    }

    /**
     * Circumscribed circle of triangle (a, b, c). Collinear triples have no
     * finite circumcenter, so they fall back to the widest two-point circle,
     * which encloses the middle point.
     */
    private static function circleFrom3(Point $a, Point $b, Point $c): Circle
    {
        $d = 2.0 * ($a->x * ($b->y - $c->y) + $b->x * ($c->y - $a->y) + $c->x * ($a->y - $b->y));

        if (FloatMath::isZero($d)) {
            return self::widestPair($a, $b, $c);
        }

        $a2 = $a->x * $a->x + $a->y * $a->y;
        $b2 = $b->x * $b->x + $b->y * $b->y;
        $c2 = $c->x * $c->x + $c->y * $c->y;

        $ux = ($a2 * ($b->y - $c->y) + $b2 * ($c->y - $a->y) + $c2 * ($a->y - $b->y)) / $d;
        $uy = ($a2 * ($c->x - $b->x) + $b2 * ($a->x - $c->x) + $c2 * ($b->x - $a->x)) / $d;

        $center = new Point($ux, $uy);

        return new Circle($center, $center->distanceTo($a));
    }

    /**
     * Of the three two-point circles over (a, b, c), the one with the largest
     * radius — for a collinear triple this spans the two extreme points and so
     * encloses the third.
     */
    private static function widestPair(Point $a, Point $b, Point $c): Circle
    {
        $best = self::circleFrom2($a, $b);
        foreach ([self::circleFrom2($b, $c), self::circleFrom2($c, $a)] as $candidate) {
            if ($candidate->radius > $best->radius) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Deterministic Fisher–Yates shuffle driven by a self-contained linear
     * congruential generator, so ordering is reproducible run to run and never
     * touches PHP's global RNG state.
     *
     * @param list<Point> $points
     * @return list<Point>
     */
    private static function shuffle(array $points): array
    {
        $seed = 0x9E3779B1;
        for ($i = count($points) - 1; $i > 0; $i--) {
            $seed = (($seed * 1103515245) + 12345) & 0x7FFFFFFF;
            $j = $seed % ($i + 1);
            [$points[$i], $points[$j]] = [$points[$j], $points[$i]];
        }

        return array_values($points);
    }
}
