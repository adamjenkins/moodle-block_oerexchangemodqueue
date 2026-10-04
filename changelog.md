# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [1.0.7] - 2026-10-04

### Changed

- The Japanese language pack (lang/ja) is no longer included: releases ship the English strings
  only, as the Moodle Plugins directory expects. Japanese is provided through Moodle's language
  packs.
- `composer.json`: the `moodle/moodle` constraint is now `^5.0` (was `>=5.0 <5.4`), so newer
  Moodle 5.x releases are not excluded.
- CI: the moodle.git `main` rows became blocking `MOODLE_503_STABLE` rows now that Moodle 5.3
  is released.

## [1.0.6] - 2026-10-04

### Changed

- Declare Moodle 5.3 support: `$plugin->supported` is now `[500, 503]`, and
  `composer.json`'s `moodle/moodle` constraint is widened to match
  (`>=5.0 <5.4`). No code change was needed: the Moodle 5.3 upgrade notes
  were checked against this block and nothing it uses was removed or
  changed.
- The distribution ZIP now includes `tests/` (no longer `export-ignore`d).

## [1.0.5] - 2026-08-03

### Fixed

- `composer.json` required `adamjenkins/moodle-local_oerexchange` at `"*"`
  while `version.php` pinned build 2026080400, so the two declarations of the
  same dependency disagreed. Composer would resolve this block against an
  older Exchange and install it, leaving Moodle's plugin installer to refuse
  the pairing afterwards. Now `^1.0.7`, matching the build pin.
  `composer validate` had been reporting this as an unbound-constraint warning
  since the file was written; it now passes clean. The `version.php` comment
  names composer.json as the file to keep in step, since nothing cross-checks
  them.

No code, string or behaviour change — Composer metadata is fixed at a tag, so
correcting it requires a new version.

## [1.0.4] - 2026-08-03

### Added

- A "Hidden by moderators (N)" section linking to `local_oerexchange`'s new
  `moderate_hidden.php` report, shown to viewers holding
  `local/oerexchange:moderate`. Counted by a new
  `content_builder::get_modhidden_count()` querying the Exchange's tables
  directly, as this block already does for open reports, failed parses and
  pending sites. Counts `modhidden` only — the Exchange's stale-courseware
  janitor writes `removed` automatically, so counting it would attribute
  automatic removals to a moderator.

### Changed

- `$plugin->dependencies` pins `local_oerexchange` to 2026080400 (1.0.7)
  instead of `ANY_VERSION`. The block references no new class from the parent,
  so the rule about raising a version for a new symbol does not strictly apply
  — but against an older parent the new heading links to a page that does not
  exist, which is what that rule protects against.

## [1.0.3] - 2026-08-01

### Fixed

- Registering-site names in the pending-sites section are passed through
  `format_string()` instead of bare `s()`, so multilang markup is filtered
  rather than shown as literal `<span>` text. Resource titles in this block
  were already fixed in 1.0.2; site names were missed.

## [1.0.2] - 2026-07-31

### Fixed

- Reported and failed-parse resource titles in the moderation queue rendered
  multilang markup as literal text even with the site's multilang filter
  enabled. Both callbacks now use `format_string()` with a system context,
  matching `block_oerexchangeshares`'s already-correct pattern.

## [1.0.1] - 2026-07-29

### Changed

- The camp release-publishing workflow now uses the registry's current
  tokenless template (OIDC trusted publishing, camp-tools v0.2.35). The
  previous template pinned camp-tools v0.2.25, whose index-entry schema
  predates the registry's `source-repo-id` field, so publication of v1.0.0
  could not succeed. No change to the plugin itself.

## [1.0.0] - 2026-07-29

First stable release. `$plugin->maturity` is now `MATURITY_STABLE`.

No functional change since 0.1.1 — the whole OER Exchange suite moves to 1.0.0
together, so a site never has a stable plugin depending on an alpha one.

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
