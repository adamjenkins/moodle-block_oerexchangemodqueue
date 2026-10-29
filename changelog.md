# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [1.0.10] - 2026-10-29

### Changed

- CI now also tests Moodle 5.1 (`MOODLE_501_STABLE`), which the plugin has
  always declared supported.

## [1.0.9] - 2026-10-20

### Added

- The camp listing (`.camp/listing.yml`) now carries screenshots with captions.
  They are excluded from the plugin ZIP.

## [1.0.8] - 2026-10-19

### Added

- An "Open collection reports (N)" section, listing the titles of the most
  recently reported collections and linking to `moderate.php`, which now has a
  collections section of its own. Counted and listed by two new
  `content_builder` methods, `get_open_collection_report_count()` and
  `get_recent_open_collection_reports()`, querying the Exchange's tables
  directly as every other figure in this block does.
- A "Collections taken down (N)" heading linking to `moderate_hidden.php`,
  counted by `content_builder::get_modhidden_collection_count()`. Active
  collections only: a tombstoned collection keeps its `modhidden` flag but the
  report offers no restore for it, so counting it would promise a row that is
  not there. A second heading rather than a figure folded into "Hidden by
  moderators", because that report shows held resources and held collections
  as two separate lists.

### Fixed

- `content_builder::get_open_report_count()` and
  `get_recent_open_reports()` now say `resourceid IS NOT NULL`.
  `{local_oerexchange_reports}` is polymorphic from `local_oerexchange` build
  2026101900 — `resourceid` is nullable, a nullable `collectionid` was added,
  and exactly one of the two is set on any row — so the unqualified queries
  would have counted every collection report in the open-reports figure and
  rendered each one in the recent list as a resource that had been deleted
  (the `LEFT JOIN` on `resourceid` cannot match a collection report). A wrong
  answer rather than a missing one, which is why it is listed here rather than
  under "Changed".

### Changed

- `$plugin->dependencies` on `local_oerexchange` raised from build 2026080400
  to 2026101900, the build that adds the collections tables and the reports
  table's `collectionid`. The previous pin guarded only a link to a page that
  would otherwise 404; this one guards columns this block reads, so Moodle's
  installer refusing the pairing is the intended behaviour.
  `composer.json`'s `adamjenkins/moodle-local_oerexchange` constraint is
  raised to match, from `^1.0.7` to `^1.1.0` (build 2026101900 is the
  Exchange's 1.1.0 release).
- The three collection queries return nothing, rather than failing, while the
  Exchange's collections schema is not in the database yet. The dependency
  pin stops this block installing against an older Exchange, but not the
  window between deploying the new files and an admin running the upgrade, in
  which a moderator's Dashboard would otherwise have failed on a missing
  table.
- The "Open reports (N)" heading is now "Open resource reports (N)", so it
  cannot be read as a total now that collection reports are counted beside it.

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
