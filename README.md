# polygon-kit

[![CI](https://github.com/mykolapodpriatov/polygon-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/mykolapodpriatov/polygon-kit/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/mykolapodpriatov/polygon-kit.svg)](https://packagist.org/packages/mykolapodpriatov/polygon-kit)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Pure-PHP planar computational geometry. No `ext-geos`, no native extensions, runs on any host.**

PHP has no good *pure-PHP* computational-geometry library: you either install the
`ext-geos` C extension (needs PECL/root, impossible on most shared hosting) or
reach for geo-spatial packages built around a database. `polygon-kit` fills that
gap with immutable, strictly-typed value objects and the core polygon algorithms:
`composer require` and it just works.

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
| **Value objects** | `Point`, `Segment`, `Polygon`, `BoundingBox`, `Circle`, all `final readonly` and validated at construction |
| **Construction** | `Polygon::fromArray()`, `fromPoints()`, `regular($n, $radius, ?$center, $startAngle)` |
| **Measures** | `Polygon::area()` / `signedArea()` (shoelace), `perimeter()`, `centroid()` (area-weighted), `boundingBox()` |
| **Predicates** | `Polygon::isConvex()`, `isSimple()`, `orientation()` returning `Orientation::{Clockwise,CounterClockwise,Degenerate}` |
| **Transforms** | `Polygon::withTranslation($dx, $dy)`, `withRotation($radians, ?$about)`, `withScale($factor, ?$about)`, `reversed()`, all immutable |
| **Point location** | `Polygon::containsPoint($p)`, with two independent implementations, `RayCasting` (default) and `WindingNumber`, tested to agree |
| **Point queries** | `Polygon::distanceToPoint($p)` (`Measure\PointToPolygonDistance`), `closestPoint($p)` (`Measure\ClosestPoint`) |
| **Overlap** | `Polygon::intersects($other)` (`Predicate\PolygonOverlap`), a boolean test valid for any two simple polygons |
| **Containment** | `Polygon::contains($other)` (`Predicate\PolygonContainment`), a directional boolean test: is `$other` fully inside `$this`, boundary touch allowed |
| **Convex boolean ops** | `ConvexIntersection::of()` (Sutherland-Hodgman), `ConvexUnion::of()` (hull of union), `ConvexHull::of()` (monotone chain) |
| **Triangulation** | `EarClipping::triangulate()`, a simple polygon into exactly `n - 2` CCW triangles |
| **Convex decomposition** | `ConvexDecomposition::of()` (Hertel-Mehlhorn), a simple polygon into convex pieces |
| **Enclosing circle** | `MinimumBoundingCircle::of()` (Welzl), returning a `Circle` |
| **Simplification** | `Simplify::douglasPeucker($polygon, $epsilon)` |
| **Segments** | `Segment::length()`, `closestPoint()`, `distanceToPoint()`, `intersectionWith()`, `contains()` |
| **Tolerance** | `Math\FloatMath::{eq,gt,lt,gte,lte,sign,isZero}`, `Math\Cross::{orient2d,orientation}` |

## Design notes & honest scope

- **Convex-only boolean ops (v1).** `ConvexIntersection` is correct only when both
  polygons are convex (asserted at the boundary). `ConvexUnion` returns the convex
  **hull of the union**: exact when the true union is convex, otherwise a superset.
  General non-convex clipping (Weiler-Atherton) is future work.
- **Overlap and containment are the exception.** `Polygon::intersects()` and
  `Polygon::contains()` are boolean tests, not a clip, and both are valid for
  **any two simple polygons**, convex or not. They are the operations the
  convex-only restriction does not apply to. Both reject a self-intersecting
  ring rather than answering for it, the same way `EarClipping` does.
- **Triangulation ships.** `EarClipping::triangulate()` turns a simple polygon
  into exactly `n - 2` counter-clockwise triangles in O(n^2), guarded by
  `SimplicityTest`: a self-intersecting ring has no meaningful triangulation and
  throws a `GeometryException` instead of returning nonsense.
- **Convex-only does not mean convex-input-only.** `ConvexDecomposition::of()`
  turns any simple polygon into convex pieces that tile it exactly, so the
  convex operations can be applied piecewise: decompose, operate, combine. It
  builds on the ear-clipping triangulation and then deletes diagonals whose
  removal leaves a convex piece (Hertel-Mehlhorn), which is why an L-shape
  comes back as two pieces rather than four triangles. The bound is at most
  four times the minimum number of convex pieces.
- **Float robustness, not exact predicates.** All comparisons route through a
  centralised tolerance (`Math\FloatMath`, `EPSILON = 1e-9`) so near-degenerate
  inputs classify deterministically. There is no Shewchuk adaptive-precision /
  BCMath path: robust *within float precision*, and the library says so plainly.
- **Immutable everything.** Every type is `final readonly`; transforms return new
  instances. An invalid polygon (<3 vertices, NaN/INF coords, consecutive
  duplicates) is unrepresentable and throws at construction.
- **Simplicity is a predicate, not a constructor check.** Construction guarantees
  a well-formed *ring* (>= 3 finite vertices, no consecutive duplicates) but not
  a *simple* one: a self-intersecting bowtie still constructs, and then
  area/centroid/orientation/`containsPoint` return silent nonsense. Enforcing
  non-self-intersection in the constructor would be a breaking change and cost
  O(n^2) on every construction (most call sites already hold trusted rings), so
  it lives as the opt-in `Polygon::isSimple()` (`Predicate\SimplicityTest`)
  instead. Validate untrusted input there before trusting the measures.
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
diploma archive (2005-2008):

| Source | Algorithm | → here |
|--------|-----------|--------|
| `algolist/area.htm`, `A1.h::ario::area` | shoelace area | `Measure\ShoelaceArea`, `Polygon::area()` |
| `algolist/orient.htm` | orientation | `Predicate\Orientation` |
| "Центр тяжести" | centroid | `Measure\Centroid` |
| "Определение выпуклый…", `A1.h::ario::Convex` | convexity | `Measure\ConvexityTest`, `Polygon::isConvex()` |
| `algolist/convex_intersect.htm` | convex intersection | `Operation\ConvexIntersection` |
| `algolist/convex_or.htm` | convex union | `Operation\ConvexUnion` |

The rest of the library has no archive ancestor and was written directly against
the published descriptions of each algorithm: ear clipping (two-ears theorem),
Welzl's minimum enclosing circle, monotone-chain hull, Douglas-Peucker,
ray-casting and winding-number point location, and segment-intersection overlap.

No third-party code is vendored; only the published algorithms are referenced.

## License

[MIT](LICENSE) © Mykola Podpriatov
