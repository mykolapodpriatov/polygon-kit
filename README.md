# polygon-kit

**Pure-PHP planar computational geometry — no `ext-geos`, no native extensions, runs on any host.**

PHP has no good *pure-PHP* computational-geometry library: you either install the
`ext-geos` C extension (needs PECL/root, impossible on most shared hosting) or
reach for geo-spatial packages built around a database. `polygon-kit` fills that
gap with immutable, strictly-typed value objects and the core polygon algorithms
— `composer require` and it just works.

```php
use PolygonKit\Geometry\Point;
use PolygonKit\Geometry\Polygon;
use PolygonKit\Operation\ConvexIntersection;

$zone = Polygon::fromArray([[0, 0], [10, 0], [10, 10], [0, 10]]); // CCW square
$zone->area();                              // 100.0
$zone->centroid();                          // Point(5, 5)
$zone->isConvex();                          // true
$zone->orientation();                       // Orientation::CounterClockwise
$zone->containsPoint(new Point(3, 4));      // true (ray-cast == winding agree)

$a = Polygon::fromArray([[0, 0], [4, 0], [4, 4], [0, 4]]);
$b = Polygon::fromArray([[2, 2], [6, 2], [6, 6], [2, 6]]);
ConvexIntersection::of($a, $b)?->area();    // 4.0  (<= min(area A, area B))
```

## Install

```bash
composer require mykolapodpriatov/polygon-kit
```

Requires PHP **8.2+**. No native extensions.

## Features

| Area | API |
|------|-----|
| **Value objects** | `Point`, `Segment`, `Polygon`, `BoundingBox` — `final readonly`, validated at construction |
| **Measures** | `Polygon::area()` / `signedArea()` (shoelace), `perimeter()`, `centroid()` (area-weighted), `isConvex()`, `boundingBox()` |
| **Orientation** | `Polygon::orientation()` → `Orientation::{Clockwise,CounterClockwise,Degenerate}` |
| **Point location** | `Polygon::containsPoint($p)` — two independent methods, `RayCasting` (default) and `WindingNumber`, that are tested to agree |
| **Convex boolean ops** | `ConvexIntersection::of()` (Sutherland–Hodgman), `ConvexUnion::of()` (hull of union), `ConvexHull::of()` (monotone chain) |
| **Simplification** | `Simplify::douglasPeucker($polygon, $epsilon)` |

## Design notes & honest scope

- **Convex-only boolean ops (v1).** `ConvexIntersection` is correct only when both
  polygons are convex (asserted at the boundary). `ConvexUnion` returns the convex
  **hull of the union** — exact when the true union is convex, otherwise a superset.
  General non-convex clipping (Weiler–Atherton) and triangulation are future work.
- **Float robustness, not exact predicates.** All comparisons route through a
  centralised tolerance (`Math\FloatMath`, `EPSILON = 1e-9`) so near-degenerate
  inputs classify deterministically. There is no Shewchuk adaptive-precision /
  BCMath path — robust *within float precision*, and the library says so plainly.
- **Immutable everything.** Every type is `final readonly`; transforms return new
  instances. An invalid polygon (<3 vertices, NaN/INF coords, consecutive
  duplicates) is unrepresentable — it throws at construction.
- **Two point-in-polygon methods on purpose.** Ray-casting and winding-number are
  cross-checked over a grid in the test-suite, so each validates the other.

## Quality

- **PHPStan level max**, clean (no baseline).
- **PHPUnit** unit + property/invariant tests (area invariance under
  translation/rotation, intersection-area ≤ min, union-area ≥ max, ray-cast ==
  winding).
- **CI** on PHP 8.2 / 8.3 / 8.4.

```bash
composer install
composer stan   # phpstan analyse
composer test   # phpunit
```

## Provenance

The algorithms are **re-implemented from scratch** in typed, immutable PHP, based
on routines from the author's NTU "KhPI" (Kharkiv Polytechnic) coursework and
diploma archive (2005–2008):

| Source | Algorithm | → here |
|--------|-----------|--------|
| `algolist/area.htm`, `A1.h::ario::area` | shoelace area | `Measure\ShoelaceArea`, `Polygon::area()` |
| `algolist/orient.htm` | orientation | `Predicate\Orientation` |
| "Центр тяжести" | centroid | `Measure\Centroid` |
| "Определение выпуклый…", `A1.h::ario::Convex` | convexity | `Measure\ConvexityTest`, `Polygon::isConvex()` |
| `algolist/convex_intersect.htm` | convex intersection | `Operation\ConvexIntersection` |
| `algolist/convex_or.htm` | convex union | `Operation\ConvexUnion` |

No third-party code is vendored; only the published algorithms are referenced.

## License

[MIT](LICENSE) © Mykola Podpriatov
