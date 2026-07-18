# block_oerexchangemodqueue

A Moodle Dashboard block for **OER Exchange** moderators: a single
at-a-glance summary of what needs attention, with links straight to the
pages that let you act on it.

This block requires the `local/oerexchange:moderate` capability — it is
not offered in the block picker to anyone else, and even if an instance
somehow ended up on a page, it renders empty content for a user who lacks
the capability.

## What it shows

- **Open reports** — count, and the oldest few still awaiting review, each
  linking to the reported resource. Links through to `moderate.php`.
- **Failed parses** — count, and the most recent backup uploads whose
  structure parsing failed. Links through to `moderate.php`.
- **Pending site registrations** — count, and the oldest few sites still
  awaiting approval. Links through to `manage_sites.php`.

All data is read directly from
[`local_oerexchange`](https://github.com/adamjenkins/moodle-local_oerexchange)'s
own tables — this block has no database schema, settings, or business logic
of its own. It is presentation-layer only.

## Requirements

- Moodle 5.0–5.2 (`$plugin->supported`).
- [`local_oerexchange`](https://github.com/adamjenkins/moodle-local_oerexchange)
  must already be installed — `version.php` declares it as a hard
  dependency and the Moodle installer will refuse to install this block
  without it.
- The viewing/adding user needs the `local/oerexchange:moderate`
  capability (granted to the `manager` archetype by default).

## Installation

```bash
git clone https://github.com/adamjenkins/moodle-block_oerexchangemodqueue.git blocks/oerexchangemodqueue
php admin/cli/upgrade.php
```

Then add the "OER Exchange: moderation queue" block to the Dashboard from
the block drawer (available only to users with the required capability).

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).
