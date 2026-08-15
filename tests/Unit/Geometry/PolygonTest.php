<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Geometry;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Math\FloatMath;
use PolygonKit\Tests\Fixtures\PolygonFixtures;

final class PolygonTest extends TestCase
{
    public function testRejectsFewerThanThreeVertices(): void
    {
        $this->expectException(InvalidPolygonException::class);
        Polygon::fromArray([[0, 0], [1, 1]]);
    }

    public function testRejectsConsecutiveDuplicateVertices(): void
    {
        $this->expectException(InvalidPolygonException::class);
        Polygon::fromArray([[0, 0], [1, 0], [1, 0], [0, 1]]);
    }

    public function testFromPointsAndCount(): void
    {
        $polygon = Polygon::fromPoints(new Point(0, 0), new Point(1, 0), new Point(0, 1));
        self::assertSame(3, $polygon->vertexCount());
    }

    public function testRegularSquareIsConvexSimpleAndCentered(): void
    {
        $square = Polygon::regular(4, 1.0);

        self::assertSame(4, $square->vertexCount());
        self::assertTrue($square->isConvex());
        self::assertTrue($square->isSimple());

        $centroid = $square->centroid();
        self::assertEqualsWithDelta(0.0, $centroid->x, FloatMath::EPSILON);
        self::assertEqualsWithDelta(0.0, $centroid->y, FloatMath::EPSILON);

        foreach ($square->vertices as $vertex) {
            self::assertEqualsWithDelta(1.0, hypot($vertex->x, $vertex->y), FloatMath::EPSILON);
        }

        // startAngle 0 = +X, then CCW.
        self::assertEqualsWithDelta(1.0, $square->vertices[0]->x, FloatMath::EPSILON);
        self::assertEqualsWithDelta(0.0, $square->vertices[0]->y, FloatMath::EPSILON);
    }

    public function testRegularRejectsFewerThanThreeSides(): void
    {
        $this->expectException(InvalidPolygonException::class);
        Polygon::regular(2, 1.0);
    }

    public function testRegularRejectsNonPositiveRadius(): void
    {
        $this->expectException(InvalidPolygonException::class);
        Polygon::regular(4, 0.0);
    }

    public function testReversedFlipsSignedArea(): void
    {
        $square = PolygonFixtures::unitSquare();
        self::assertEqualsWithDelta(-$square->signedArea(), $square->reversed()->signedArea(), 1e-9);
    }

    public function testReversedTwiceIsIdentity(): void
    {
        $square = PolygonFixtures::unitSquare();
        self::assertEqualsWithDelta(
            $square->signedArea(),
            $square->reversed()->reversed()->signedArea(),
            1e-9,
        );
    }

    public function testWithTranslationMovesEveryVertexAndPreservesArea(): void
    {
        $square = PolygonFixtures::unitSquare();
        $moved = $square->withTranslation(3.0, -2.0);

        self::assertEqualsWithDelta(3.0, $moved->vertices[0]->x, 1e-9);
        self::assertEqualsWithDelta(-2.0, $moved->vertices[0]->y, 1e-9);
        self::assertEqualsWithDelta(4.0, $moved->vertices[2]->x, 1e-9);
        self::assertEqualsWithDelta(-1.0, $moved->vertices[2]->y, 1e-9);
        self::assertEqualsWithDelta($square->area(), $moved->area(), 1e-9);
        // Immutability: the original is untouched.
        self::assertEqualsWithDelta(0.0, $square->vertices[0]->x, 1e-9);
    }

    public function testWithRotationDefaultsToOriginAndPreservesArea(): void
    {
        $square = PolygonFixtures::unitSquare();
        $rotated = $square->withRotation(M_PI / 2);

        // 90 deg CCW about the origin sends (1, 0) -> (0, 1).
        self::assertEqualsWithDelta(0.0, $rotated->vertices[1]->x, 1e-9);
        self::assertEqualsWithDelta(1.0, $rotated->vertices[1]->y, 1e-9);
        self::assertEqualsWithDelta($square->area(), $rotated->area(), 1e-9);
    }

    public function testWithRotationAboutAPoint(): void
    {
        $square = PolygonFixtures::unitSquare();
        $about = new Point(0.5, 0.5);

        // A full turn about the centre is the identity.
        $round = $square->withRotation(2 * M_PI, $about);
        self::assertEqualsWithDelta($square->vertices[0]->x, $round->vertices[0]->x, 1e-9);
        self::assertEqualsWithDelta($square->vertices[0]->y, $round->vertices[0]->y, 1e-9);
    }

    public function testWithScaleAboutOriginScalesAreaByFactorSquared(): void
    {
        $square = PolygonFixtures::unitSquare();
        $scaled = $square->withScale(3.0);

        self::assertEqualsWithDelta(9.0 * $square->area(), $scaled->area(), 1e-9);
        self::assertEqualsWithDelta(3.0, $scaled->vertices[2]->x, 1e-9);
        self::assertEqualsWithDelta(3.0, $scaled->vertices[2]->y, 1e-9);
    }

    public function testWithScaleAboutAPointKeepsThatPointFixed(): void
    {
        $square = PolygonFixtures::unitSquare();
        $about = new Point(1.0, 1.0);
        $scaled = $square->withScale(2.0, $about);

        // The vertex at the centre of scaling stays put; the far corner doubles
        // its distance from it: (0, 0) -> (-1, -1).
        self::assertEqualsWithDelta(-1.0, $scaled->vertices[0]->x, 1e-9);
        self::assertEqualsWithDelta(-1.0, $scaled->vertices[0]->y, 1e-9);
    }

    public function testNegativeScaleIsAllowedAndPointReflects(): void
    {
        $square = PolygonFixtures::unitSquare();
        $flipped = $square->withScale(-2.0);

        // Point-reflection through the origin: the corner (1, 1) -> (-2, -2).
        self::assertEqualsWithDelta(-2.0, $flipped->vertices[2]->x, 1e-9);
        self::assertEqualsWithDelta(-2.0, $flipped->vertices[2]->y, 1e-9);
        // |area| grows by factor^2 = 4; a point-reflection in the plane is a
        // 180 deg rotation, so the winding (signed-area sign) is preserved.
        self::assertEqualsWithDelta(4.0 * $square->area(), $flipped->area(), 1e-9);
        self::assertEqualsWithDelta(4.0 * $square->signedArea(), $flipped->signedArea(), 1e-9);
    }

    public function testWithScaleRejectsZeroFactor(): void
    {
        $this->expectException(InvalidPolygonException::class);
        PolygonFixtures::unitSquare()->withScale(0.0);
    }

    public function testWithScaleRejectsNonFiniteFactor(): void
    {
        $this->expectException(InvalidPolygonException::class);
        PolygonFixtures::unitSquare()->withScale(INF);
    }
}
