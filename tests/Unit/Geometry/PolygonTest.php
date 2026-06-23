<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Geometry;

use PHPUnit\Framework\TestCase;
use PolygonKit\Exception\InvalidPolygonException;
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
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
}
