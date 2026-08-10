<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;

/**
 * Boolean overlap test between two arbitrary simple polygons (convex or not).
 *
 * Unlike {@see \PolygonKit\Operation\ConvexIntersection}, this does not build
 * the intersection geometry and does not require either input to be convex -
 * it only answers yes/no, which is a much smaller lift than full non-convex
 * clipping (Weiler-Atherton, still future work per the README).
 *
 * Three-stage test:
 *  1. {@see \PolygonKit\Geometry\BoundingBox::intersects()} fast reject.
 *  2. Pairwise edge-crossing test over both closed rings via
 *     {@see Segment::intersectionWith()} - catches any boundary crossing,
 *     including boundary touches (a shared vertex or a vertex landing on the
 *     other ring's edge), since a shared endpoint is a valid `t`/`u` in `[0, 1]`.
 *  3. Containment fallback: does either polygon contain a vertex of the
 *     other? Catches the case where one polygon sits entirely inside the
 *     other with no boundary crossing at all. `Polygon::containsPoint()`
 *     treats boundary points as inside, so this also catches collinear
 *     edge-on-edge touches that {@see Segment::intersectionWith()} misses
 *     (it returns null for collinear pairs).
 *
 * Self-intersecting (non-simple) input is undefined behavior, the same
 * posture {@see \PolygonKit\Operation\EarClipping} and
 * {@see \PolygonKit\Operation\ConvexIntersection} take toward non-simple rings.
 */
final class PolygonOverlap
{
    public static function intersects(Polygon $a, Polygon $b): bool
    {
        if (! $a->boundingBox()->intersects($b->boundingBox())) {
            return false;
        }

        if (self::boundariesCross($a, $b)) {
            return true;
        }

        return $a->containsPoint($b->vertices[0]) || $b->containsPoint($a->vertices[0]);
    }

    private static function boundariesCross(Polygon $a, Polygon $b): bool
    {
        $edgesA = self::edges($a);
        $edgesB = self::edges($b);

        foreach ($edgesA as $edgeA) {
            foreach ($edgesB as $edgeB) {
                if ($edgeA->intersectionWith($edgeB) !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<Segment>
     */
    private static function edges(Polygon $polygon): array
    {
        $vertices = $polygon->vertices;
        $n = count($vertices);

        $edges = [];
        for ($i = 0; $i < $n; $i++) {
            $edges[] = new Segment($vertices[$i], $vertices[($i + 1) % $n]);
        }

        return $edges;
    }
}
