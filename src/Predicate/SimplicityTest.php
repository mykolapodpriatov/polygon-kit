<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;

/**
 * Simplicity predicate: does a polygon's closed ring self-intersect?
 *
 * The {@see Polygon} constructor only guarantees >= 3 finite vertices with no
 * consecutive duplicates, so a self-intersecting ring (a bowtie, a T-junction,
 * a collinear doubling-back) still constructs — and then area/centroid/
 * orientation/containsPoint silently return nonsense. This predicate is the
 * (deliberately opt-in) O(n^2) test that flags such rings.
 *
 * Rules over the n closing edges:
 *  - Ring-adjacent edges legitimately share exactly one endpoint (a vertex);
 *    that shared endpoint is NOT an intersection. They are non-simple only when
 *    they overlap collinearly (the ring doubles back along itself).
 *  - Non-adjacent edges must not touch AT ALL — a proper crossing, a T-junction
 *    (a vertex landing mid-edge), or a shared endpoint all make the ring
 *    non-simple.
 *
 * {@see Segment::intersectionWith()} covers proper crossings and endpoint
 * touches but returns null for collinear pairs, so collinear overlap is caught
 * separately via {@see Segment::contains()}.
 */
final class SimplicityTest
{
    public static function isSimple(Polygon $polygon): bool
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);

        // The n closing edges of the ring (edge i joins vertex i to i+1 mod n).
        $edges = [];
        for ($i = 0; $i < $n; $i++) {
            $edges[$i] = new Segment($vertices[$i], $vertices[($i + 1) % $n]);
        }

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $adjacent = ($j === $i + 1) || ($i === 0 && $j === $n - 1);

                if ($adjacent) {
                    if (self::adjacentEdgesOverlap($edges[$i], $edges[$j])) {
                        return false;
                    }
                    continue;
                }

                if (self::edgesTouch($edges[$i], $edges[$j])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Do two non-adjacent edges touch anywhere? A single shared point is enough
     * to make the ring non-simple.
     */
    private static function edgesTouch(Segment $a, Segment $b): bool
    {
        if ($a->intersectionWith($b) !== null) {
            return true;
        }

        // intersectionWith() bails on collinear pairs; catch collinear overlap
        // (and any point-touch on a shared supporting line) with contains().
        return $a->contains($b->a) || $a->contains($b->b)
            || $b->contains($a->a) || $b->contains($a->b);
    }

    /**
     * Do two ring-adjacent edges overlap beyond their shared endpoint? They are
     * allowed to meet at that one vertex; a collinear doubling-back — where the
     * far endpoint of one edge lies along the other — is not.
     */
    private static function adjacentEdgesOverlap(Segment $a, Segment $b): bool
    {
        if ($a->b->equals($b->a)) {
            $oppositeA = $a->a;
            $oppositeB = $b->b;
        } elseif ($a->a->equals($b->b)) {
            $oppositeA = $a->b;
            $oppositeB = $b->a;
        } elseif ($a->a->equals($b->a)) {
            $oppositeA = $a->b;
            $oppositeB = $b->b;
        } else { // $a->b equals $b->b
            $oppositeA = $a->a;
            $oppositeB = $b->a;
        }

        return $a->contains($oppositeB) || $b->contains($oppositeA);
    }
}
