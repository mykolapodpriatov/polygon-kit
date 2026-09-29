<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Predicate;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\GeometryException;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Predicate\PolygonContainment;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class PolygonContainmentTest extends TestCase
{
    public function testDisjointSquaresDoNotContain(): void
    {
        $a = PolygonFixtures::unitSquare();
        $b = Polygon::fromArray([[5, 5], [6, 5], [6, 6], [5, 6]]);

        self::assertFalse(PolygonContainment::contains($a, $b));
        self::assertFalse(PolygonContainment::contains($b, $a));
    }

    public function testOuterContainsInnerButNotReverse(): void
    {
        $outer = PolygonFixtures::bigSquare();
        $inner = Polygon::fromArray([[2, 2], [4, 2], [4, 4], [2, 4]]);

        self::assertTrue(PolygonContainment::contains($outer, $inner));
        self::assertFalse(PolygonContainment::contains($inner, $outer));
    }

    public function testOverlappingButNeitherFullyInsideDoesNotContain(): void
    {
        $a = Polygon::fromArray([[0, 0], [4, 0], [4, 4], [0, 4]]);
        $b = Polygon::fromArray([[2, 2], [6, 2], [6, 6], [2, 6]]);

        self::assertFalse(PolygonContainment::contains($a, $b));
        self::assertFalse(PolygonContainment::contains($b, $a));
    }

    public function testTouchingAtASharedVertexOnlyStillContains(): void
    {
        $container = PolygonFixtures::unitSquare();
        // Shares only the corner (0, 0) with $container, otherwise strictly interior.
        $subject = Polygon::fromArray([[0, 0], [0.5, 0.1], [0.1, 0.5]]);

        self::assertTrue(PolygonContainment::contains($container, $subject));
    }

    public function testTouchingAtASharedEdgeOnlyStillContains(): void
    {
        $container = PolygonFixtures::unitSquare();
        // Shares the full bottom edge (0, 0)->(1, 0) with $container, otherwise interior.
        $subject = Polygon::fromArray([[0, 0], [1, 0], [0.5, 0.5]]);

        self::assertTrue(PolygonContainment::contains($container, $subject));
    }

    public function testPolygonContainsItself(): void
    {
        $polygon = PolygonFixtures::lShape();

        self::assertTrue(PolygonContainment::contains($polygon, $polygon));
    }

    public function testNonConvexContainerAcceptsSubjectFullyInsideOneArm(): void
    {
        // Square entirely inside the L-shape's horizontal arm ([0,2] x [0,1]).
        $lShape = PolygonFixtures::lShape();
        $square = Polygon::fromArray([[0.2, 0.2], [0.8, 0.2], [0.8, 0.8], [0.2, 0.8]]);

        self::assertFalse($lShape->isConvex());
        self::assertTrue(PolygonContainment::contains($lShape, $square));
    }

    public function testNonConvexContainerRejectsSubjectWhoseVerticesPassButEdgeCrossesTheNotch(): void
    {
        // Every vertex individually satisfies containsPoint() against the
        // L-shape (two in the horizontal arm, one in the vertical arm), but
        // the straight edge between the two far vertices cuts through the
        // L-shape's missing 1x1 notch at [1,1]-[2,2]. Vertex-only containment
        // would wrongly say "contained"; the edge-crossing check must catch it.
        $lShape = PolygonFixtures::lShape();
        $triangle = Polygon::fromArray([[1.8, 0.5], [0.5, 1.8], [0.3, 0.3]]);

        self::assertTrue($lShape->containsPoint($triangle->vertices[0]));
        self::assertTrue($lShape->containsPoint($triangle->vertices[1]));
        self::assertTrue($lShape->containsPoint($triangle->vertices[2]));
        self::assertFalse(PolygonContainment::contains($lShape, $triangle));
    }

    public function testExposedViaPolygonContains(): void
    {
        $outer = PolygonFixtures::bigSquare();
        $inner = Polygon::fromArray([[2, 2], [4, 2], [4, 4], [2, 4]]);
        $far = Polygon::fromArray([[20, 20], [21, 20], [21, 21], [20, 21]]);

        self::assertTrue($outer->contains($inner));
        self::assertFalse($outer->contains($far));
        self::assertFalse($inner->contains($outer));
    }

    public function testSelfIntersectingContainerThrows(): void
    {
        $this->expectException(GeometryException::class);

        PolygonContainment::contains(PolygonFixtures::bowtie(), PolygonFixtures::unitSquare());
    }

    public function testSelfIntersectingSubjectThrows(): void
    {
        $this->expectException(GeometryException::class);

        PolygonContainment::contains(PolygonFixtures::unitSquare(), PolygonFixtures::bowtie());
    }

    public function testPolygonContainsThrowsWhenSelfIsNonSimple(): void
    {
        $this->expectException(GeometryException::class);

        PolygonFixtures::bowtie()->contains(PolygonFixtures::unitSquare());
    }

    public function testPolygonContainsThrowsWhenOtherIsNonSimple(): void
    {
        $this->expectException(GeometryException::class);

        PolygonFixtures::unitSquare()->contains(PolygonFixtures::bowtie());
    }
}
