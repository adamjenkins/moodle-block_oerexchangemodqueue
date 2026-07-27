# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.1] - 2026-07-27

### Fixed

- `$plugin->requires` corrected from Moodle 4.5 to 5.0 (2025041400),
  matching `$plugin->supported`, composer.json and the CI matrix.

### Changed

- The recent-reports and recent-failed-parses lists use one LEFT JOIN
  query each instead of a lookup per row, preserving the deleted-resource
  -> placeholder-label behaviour the tests pin down.
- `content_builder::get_summary()` takes the two capability flags so
  sections the viewer cannot see cost no queries.
- Deprecated doc-comment `@covers` migrated to CoversClass attributes;
  stale docblocks (single-capability gate, README's never-true claim that
  queue items link to the reported resource) refreshed.

## [0.1.0] - 2026-07-19

### Added

- Dashboard block showing open report count, failed backup parse count, and
  pending site registration count, each with a short list of recent items.
- Capability gating restricted to the `manager` archetype:
  `block/oerexchangemodqueue:addinstance` and `:myaddinstance` in
  `db/access.php`, plus an explicit `local/oerexchange:moderate` check in
  `get_content()` as a defense-in-depth safety net.
- `$plugin->dependencies` on `local_oerexchange` in `version.php`.
