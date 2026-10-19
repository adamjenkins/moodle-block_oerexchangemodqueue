# Release notes — 1.0.8

- Collections are now covered. The block shows an "Open collection reports" count with the
  titles of the most recently reported collections, and a "Collections taken down" count
  linking to the hidden-content report, alongside the figures it already showed for resources.
- "Open reports" is now labelled "Open resource reports", and counts resource reports only.
  OER Exchange's reports table became polymorphic in build 2026101900 — a report is about a
  resource *or* about a collection — so the old figure would have counted collection reports
  under a heading that lists resource titles, and listed each of them as a deleted resource.
- OER Exchange 1.1.0 (build 2026101900) or later is now required (was build 2026080400). This
  release reads the collections tables and the reports table's new `collectionid` column, so
  against an older OER Exchange it would query columns that do not exist. Moodle's installer
  refuses the pairing rather than letting it happen.

No database or capability changes of this block's own. Upgrade `local_oerexchange` first; if it
is older than build 2026101900 Moodle will ask you to.
