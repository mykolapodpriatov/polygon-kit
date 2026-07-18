<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Operation;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Circle;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;
use PolygonKit\Operation\MinimumBoundingCircle;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class MinimumBoundingCircleTest extends TestCase
{
    /**
     * The defining invariant: every vertex is enclosed (within radius + EPSILON).
     */
    private static function assertEnclosesAllVertices(Circle $circle, Polygon $polygon): void
    {
        foreach ($polygon->vertices as $vertex) {
            self::assertTrue(
                FloatMath::lte($circle->center->distanceTo($vertex), $circle->radius),
                sprintf('Vertex (%g, %g) is not enclosed.', $vertex->x, $vertex->y),
            );
        }
    }

    public function testTriangleUsesHypotenuseAsDiameter(): void
    {
        $triangle = PolygonFixtures::triangle(); // right triangle [0,0],[1,0],[0,1]

        $circle = MinimumBoundingCircle::of($triangle);

        self::assertEnclosesAllVertices($circle, $triangle);
        self::assertEqualsWithDelta(0.5, $circle->center->x, 1e-9);
        self::assertEqualsWithDelta(0.5, $circle->center->y, 1e-9);
        self::assertEqualsWithDelta(sqrt(0.5), $circle->radius, 1e-9);
    }

    public function testSquareIsCenteredWithDiagonalRadius(): void
    {
        $square = PolygonFixtures::unitSquare();

        $circle = MinimumBoundingCircle::of($square);

        self::assertEnclosesAllVertices($circle, $square);
        self::assertEqualsWithDelta(0.5, $circle->center->x, 1e-9);
        self::assertEqualsWithDelta(0.5, $circle->center->y, 1e-9);
        self::assertEqualsWithDelta(sqrt(0.5), $circle->radius, 1e-9);
    }

    public function testCollinearDegenerateSpansExtremes(): void
    {
        // Valid Polygon (3 distinct vertices) but zero-area / all-collinear.
        $collinear = Polygon::fromArray([[0, 0], [2, 0], [4, 0]]);

        $circle = MinimumBoundingCircle::of($collinear);

        self::assertEnclosesAllVertices($circle, $collinear);
        self::assertEqualsWithDelta(2.0, $circle->center->x, 1e-9);
        self::assertEqualsWithDelta(0.0, $circle->center->y, 1e-9);
        self::assertEqualsWithDelta(2.0, $circle->radius, 1e-9);
    }

    public function testEnclosesConvexAndConcaveFixtures(): void
    {
        foreach ([PolygonFixtures::pentagon(), PolygonFixtures::lShape(), PolygonFixtures::bigSquare()] as $polygon) {
            self::assertEnclosesAllVertices(MinimumBoundingCircle::of($polygon), $polygon);
        }
    }

    public function testResultIsDeterministic(): void
    {
        $polygon = PolygonFixtures::pentagon();

        $first = MinimumBoundingCircle::of($polygon);
        $second = MinimumBoundingCircle::of($polygon);

        self::assertSame($first->radius, $second->radius);
        self::assertSame($first->center->x, $second->center->x);
        self::assertSame($first->center->y, $second->center->y);
    }
}
