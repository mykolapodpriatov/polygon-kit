<?php

declare(strict_types=1);

namespace PolygonKit\Exception;

/**
 * Thrown when a polygon/point cannot be constructed: fewer than 3 vertices,
 * NaN/INF coordinates, or consecutive duplicate vertices.
 */
final class InvalidPolygonException extends GeometryException
{
}
