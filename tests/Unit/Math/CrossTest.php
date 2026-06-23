<?php

declare(strict_types=1);

namespace PolygonKit\Tests\Unit\Math;

use PHPUnit\Framework\TestCase;
use PolygonKit\Geometry\Point;
use PolygonKit\Math\Cross;

final class CrossTest extends TestCase
{
    public function testOrientationSigns(): void
    {
        $a = new Point(0, 0);
        $b = new Point(1, 0);

        self::assertSame(1, Cross::orientation($a, $b, new Point(0, 1)));   // left
        self::assertSame(-1, Cross::orientation($a, $b, new Point(0, -1))); // right
        self::assertSame(0, Cross::orientation($a, $b, new Point(2, 0)));   // collinear
    }

    public function testOrient2dIsTwiceSignedArea(): void
    {
        self::assertSame(
            1.0,
            Cross::orient2d(new Point(0, 0), new Point(1, 0), new Point(0, 1)),
        );
    }
}
