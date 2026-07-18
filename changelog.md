# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.0] - 2026-07-19

### Added

- Dashboard block showing open report count, failed backup parse count, and
  pending site registration count, each with a short list of recent items.
- Capability gating restricted to the `manager` archetype:
  `block/oerexchangemodqueue:addinstance` and `:myaddinstance` in
  `db/access.php`, plus an explicit `local/oerexchange:moderate` check in
  `get_content()` as a defense-in-depth safety net.
- `$plugin->dependencies` on `local_oerexchange` in `version.php`.
