# Release notes — 1.0.3

A registering site's name in the "Sites awaiting approval" section showed
multilang markup as visible literal text — `<span lang="en" class="multilang">…</span>`
— instead of the language the moderator is reading in. Resource titles in this
block were fixed in 1.0.2; site names were missed. They now use
`format_string()` too.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.
