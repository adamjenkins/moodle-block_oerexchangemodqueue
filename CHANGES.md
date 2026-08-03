# Release notes — 1.0.4

The block now links to the OER Exchange's new report of resources moderators
are holding, showing how many there are: **Hidden by moderators (3)**.

The moderation queue is about incoming work — open reports and failed parses —
and nothing led to the standing list of what had already been taken down,
because until this release the Exchange had no such page.

The count is moderator takedowns only. Resources the Exchange removes
automatically as abandoned courseware are excluded: no moderator hid them, and
counting them under this heading would say otherwise.

The section is shown only to users holding `local/oerexchange:moderate`, like
the rest of the moderation content in this block.

## Requires local_oerexchange 1.0.7

This release's dependency on `local_oerexchange` moves from `ANY_VERSION` to
the build that adds the report page. The block calls no new code from the
Exchange — it counts the rows itself, as it does for every other figure it
shows — but against an older Exchange the new heading would link to a page that
does not exist.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.

## Checks run for this release

`moodle-plugin-ci` phplint, phpmd, phpcs (`--max-warnings 0`), phpdoc
(`--max-warnings 0`), validate, savepoints and mustache — the same commands
this project's GitHub workflow runs — all exit 0. PHPUnit: **14 tests, 37
assertions**, all passing. The link and its count were verified in a browser on
a live site, including that a user holding `managesites` but not `moderate` is
not shown it.
