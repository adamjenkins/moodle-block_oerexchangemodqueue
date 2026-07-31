# Release notes — 1.0.2

Reported and failed-parse resource titles in the moderation queue previously
rendered any multilang markup as literal text, even with the site's multilang
filter enabled — a live, user-reported bug on the Exchange. Both title sinks
now render through the site's filters (matching
`block_oerexchangeshares`'s already-correct pattern), so a bilingual
resource's title shows in whichever language the moderator has selected.

No database or capability changes. No action is required after upgrading.
