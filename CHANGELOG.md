# Changelog

All notable changes to `polygon-kit` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project aims
to adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Immutable value objects: `Point`, `Segment`, `Polygon`, `BoundingBox`.
- Measures: shoelace area (signed/abs), perimeter, area-weighted centroid,
  convexity test.
- Predicates: polygon `Orientation`, and two point-in-polygon strategies
  (`RayCasting`, `WindingNumber`) that are cross-checked against each other.
- `Polygon::isSimple()` / `Predicate\SimplicityTest` — an opt-in O(n^2)
  self-intersection test that flags bowties, T-junctions and collinear
  doubling-back (rings the constructor accepts but downstream algorithms
  cannot reason about).
- Operations: `ConvexHull` (monotone chain), `ConvexIntersection`
  (Sutherland–Hodgman), `ConvexUnion` (hull of union), `Simplify`
  (Ramer–Douglas–Peucker).
- Centralised float tolerance (`Math\FloatMath`) and orientation primitive
  (`Math\Cross`).
- PHPUnit suite (unit + property/invariant tests), PHPStan level max, CI on
  PHP 8.2 / 8.3 / 8.4.

This is the initial port of computational-geometry routines re-implemented from
the author's NTU "KhPI" archive (see README provenance).
