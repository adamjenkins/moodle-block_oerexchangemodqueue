# Release notes — 1.0.5

A metadata-only release. No code, no strings, no behaviour change: the plugin
does exactly what 1.0.4 does.

## The Composer dependency now matches the Moodle one

1.0.4 raised this block's dependency on `local_oerexchange` to the build that
adds the hidden-resources report — but only in `version.php`. `composer.json`
still asked for `"*"`, any version at all.

The two are read by different installers, so them disagreeing had a practical
cost: Composer would resolve this block happily alongside an older Exchange,
install both, and leave Moodle's own plugin installer to refuse the pairing
afterwards with a dependency error. Composer had what it needed to prevent
that and did not use it.

`composer.json` now requires `^1.0.7`, the same floor `version.php` expresses
as build 2026080400.

If you install by unzipping or with Git rather than through Composer, this
release changes nothing for you — `version.php` was already correct.

## Why this needs a release at all

Composer metadata is fixed at a tag. Once Packagist has ingested `v1.0.4`
there is no way to correct that tag's `composer.json` in place, so the fix has
to ship as a new version.

## Checks run for this release

`moodle-plugin-ci` phplint, phpmd, phpcs (`--max-warnings 0`), phpdoc
(`--max-warnings 0`), validate, savepoints and mustache — the same commands
this project's GitHub workflow runs — all exit 0. `composer validate` now
passes with **no warnings**; against 1.0.4 it reported the unbound-constraint
warning this release fixes. PHPUnit: 14 tests, 37 assertions, all passing.
