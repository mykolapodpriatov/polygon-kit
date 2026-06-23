<?php

declare(strict_types=1);

namespace PolygonKit\Math;

/**
 * Centralised floating-point tolerance helpers.
 *
 * The library never compares floats with `==`; every comparison routes through
 * here so that near-degenerate inputs (collinear edges, points exactly on a
 * boundary) classify deterministically rather than flapping.
 *
 * Note: named `FloatMath` because `Float` is a reserved word in PHP and cannot
 * be used as a class name.
 */
final class FloatMath
{
    public const EPSILON = 1e-9;

    public static function eq(float $a, float $b, float $eps = self::EPSILON): bool
    {
        return abs($a - $b) <= $eps;
    }

    public static function gt(float $a, float $b, float $eps = self::EPSILON): bool
    {
        return $a - $b > $eps;
    }

    public static function lt(float $a, float $b, float $eps = self::EPSILON): bool
    {
        return $b - $a > $eps;
    }

    public static function gte(float $a, float $b, float $eps = self::EPSILON): bool
    {
        return $a - $b >= -$eps;
    }

    public static function lte(float $a, float $b, float $eps = self::EPSILON): bool
    {
        return $b - $a >= -$eps;
    }

    /**
     * Sign of a value within tolerance: -1, 0 or 1.
     */
    public static function sign(float $x, float $eps = self::EPSILON): int
    {
        if ($x > $eps) {
            return 1;
        }
        if ($x < -$eps) {
            return -1;
        }

        return 0;
    }

    public static function isZero(float $x, float $eps = self::EPSILON): bool
    {
        return abs($x) <= $eps;
    }
}
