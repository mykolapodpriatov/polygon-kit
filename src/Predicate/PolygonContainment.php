<?php

declare(strict_types=1);

namespace PolygonKit\Predicate;

use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Geometry\Segment;
use PolygonKit\Math\Cross;

/**
 * Directional full-containment test between two arbitrary simple polygons
 * (convex or not): does every point of $subject (interior and boundary) lie
 * inside or on the boundary of $container?
 *
 * Unlike {@see PolygonOverlap}, which answers a symmetric "do these overlap at
 * all" question, this is directional: $container->contains($subject) and
 * $subject->contains($container) generally differ.
 *
 * Four-stage test:
 *  1. {@see \PolygonKit\Geometry\BoundingBox::contains()} fast reject: if
 *     $subject's bounding box does not fit inside $container's, containment
 *     is impossible.
 *  2. Every vertex of $subject must satisfy
 *     {@see Polygon::containsPoint()} against $container. A single vertex
 *     strictly outside is enough to reject.
 *  3. No edge of $container may PROPERLY cross an edge of $subject. This is
 *     the check that vertex containment alone cannot replace: for a
 *     non-convex $container, two vertices of $subject can each individually
 *     pass containsPoint() while the straight edge between them cuts outside
 *     through a concave notch of $container - that only shows up as an edge
 *     crossing, not a vertex failure.
 *  4. Otherwise, $subject is fully contained.
 *
 * "Properly cross" means a transversal intersection: the segments cross from
 * one side to the other, not merely touch at a shared endpoint or lie
 * collinear along a shared stretch. {@see Segment::intersectionWith()} is
 * deliberately NOT used here, because it treats any endpoint touch as an
 * intersection (t/u in the closed [0, 1] range) - that is correct for
 * {@see PolygonOverlap}, which wants touching to count, but would wrongly
 * reject a $subject that legitimately touches $container's boundary (a
 * shared vertex or a shared edge) while otherwise sitting entirely inside.
 * Instead, the classic orientation-sign test decides a proper crossing:
 * segments (a, b) and (c, d) properly cross iff c and d fall strictly on
 * opposite sides of line a-b, AND a and b fall strictly on opposite sides of
 * line c-d. Any zero (collinear point) rules out a proper crossing.
 *
 * Self-intersecting (non-simple) input throws {@see GeometryException}, the
 * same posture {@see PolygonOverlap}, {@see \PolygonKit\Operation\EarClipping}
 * and {@see \PolygonKit\Operation\ConvexIntersection} take toward rings they
 * cannot reason about.
 */
final class PolygonContainment
{
    public static function contains(Polygon $container, Polygon $subject): bool
    {
        if (! SimplicityTest::isSimple($container) || ! SimplicityTest::isSimple($subject)) {
            throw new GeometryException(
                'PolygonContainment requires both polygons to be simple (non-self-intersecting).',
            );
        }

        $containerBox = $container->boundingBox();
        $subjectBox = $subject->boundingBox();
        if (! $containerBox->contains(new Point($subjectBox->minX, $subjectBox->minY))
            || ! $containerBox->contains(new Point($subjectBox->maxX, $subjectBox->maxY))
        ) {
            return false;
        }

        foreach ($subject->vertices as $vertex) {
            if (! $container->containsPoint($vertex)) {
                return false;
            }
        }

        return ! self::boundariesProperlyCross($container, $subject);
    }

    private static function boundariesProperlyCross(Polygon $container, Polygon $subject): bool
    {
        $containerEdges = self::edges($container);
        $subjectEdges = self::edges($subject);

        foreach ($containerEdges as $containerEdge) {
            foreach ($subjectEdges as $subjectEdge) {
                if (self::properlyCross($containerEdge, $subjectEdge)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Do segments $a and $b cross transversally (not at a shared endpoint,
     * not collinear)? Classic orientation-sign proper-intersection test.
     */
    private static function properlyCross(Segment $a, Segment $b): bool
    {
        $o1 = Cross::orientation($a->a, $a->b, $b->a);
        $o2 = Cross::orientation($a->a, $a->b, $b->b);
        $o3 = Cross::orientation($b->a, $b->b, $a->a);
        $o4 = Cross::orientation($b->a, $b->b, $a->b);

        return $o1 !== 0 && $o2 !== 0 && $o1 !== $o2
            && $o3 !== 0 && $o4 !== 0 && $o3 !== $o4;
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
