<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_oerexchangemodqueue\local;

/**
 * Builds the moderation-queue summary shown by the block: pending reports
 * (about resources and, separately, about collections), failed backup parses,
 * and pending site registrations, each with a count and a handful of the most
 * recent items, plus the standing counts of what moderators are holding.
 *
 * Reports about resources and reports about collections are counted and
 * listed separately, never together. {local_oerexchange_reports} is
 * polymorphic from local_oerexchange build 2026101900 — resourceid nullable,
 * collectionid added nullable, exactly one of the two set — so every query
 * here names which kind it wants. A query that does not is not merely
 * incomplete: it reports collection reports as deleted resources.
 *
 * Pure data layer, deliberately kept free of capability checks and output
 * markup so it can be unit-tested directly — the capability gate lives in
 * block_oerexchangemodqueue::get_content(), and rendering lives there too.
 *
 * @package    block_oerexchangemodqueue
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_builder {
    /** @var int how many recent items to return per category */
    const RECENT_LIMIT = 5;

    /**
     * Build the moderation-queue summary — only the sections the caller
     * will actually render. The block passes its two capability results
     * here so a viewer holding only one capability doesn't cost the
     * queries (and discarded results) of the sections they can't see.
     *
     * @param bool $includemoderation reports + failed parses (viewer holds local/oerexchange:moderate)
     * @param bool $includesites pending sites (viewer holds local/oerexchange:managesites)
     * @return array{
     *     reportcount: int, reports: \stdClass[],
     *     collectionreportcount: int, collectionreports: \stdClass[],
     *     failedparsecount: int, failedparses: \stdClass[],
     *     hiddencount: int, hiddencollectioncount: int,
     *     sitecount: int, sites: \stdClass[]
     * }
     */
    public static function get_summary(bool $includemoderation = true, bool $includesites = true): array {
        return [
            'reportcount' => $includemoderation ? self::get_open_report_count() : 0,
            'reports' => $includemoderation ? self::get_recent_open_reports() : [],
            'collectionreportcount' => $includemoderation ? self::get_open_collection_report_count() : 0,
            'collectionreports' => $includemoderation ? self::get_recent_open_collection_reports() : [],
            'failedparsecount' => $includemoderation ? self::get_failed_parse_count() : 0,
            'failedparses' => $includemoderation ? self::get_recent_failed_parses() : [],
            'hiddencount' => $includemoderation ? self::get_modhidden_count() : 0,
            'hiddencollectioncount' => $includemoderation ? self::get_modhidden_collection_count() : 0,
            'sitecount' => $includesites ? self::get_pending_site_count() : 0,
            'sites' => $includesites ? self::get_recent_pending_sites() : [],
        ];
    }

    /**
     * Count of resources a moderator is currently holding.
     *
     * 'modhidden' only, matching the report this count labels. 'removed' is
     * excluded because the Exchange's stale-courseware janitor writes that
     * status automatically, so counting it would attribute automatic removals
     * to a moderator.
     *
     * Queried directly, like every other count in this class — the block reads
     * local_oerexchange's tables rather than calling into it.
     *
     * @return int
     */
    public static function get_modhidden_count(): int {
        global $DB;
        return $DB->count_records('local_oerexchange_resources', ['status' => 'modhidden']);
    }

    /**
     * Count of collections a moderator is currently holding.
     *
     * Active collections only: a tombstoned one keeps its modhidden flag, but
     * it is already gone as far as the public is concerned and
     * moderate_hidden.php offers no Restore for it — so counting it here would
     * promise a row that report does not show.
     *
     * @return int
     */
    public static function get_modhidden_collection_count(): int {
        global $DB;
        if (!self::collections_ready()) {
            return 0;
        }
        return $DB->count_records('local_oerexchange_collections', [
            'modhidden' => 1,
            'status' => 'active',
        ]);
    }

    /**
     * Whether the Exchange's collections schema is in the database yet.
     *
     * The dependency pin stops this block installing against an older
     * Exchange, but not the window between deploying both plugins' files and
     * an admin running the upgrade: in that window the code is new and the
     * schema is old, and a moderator's Dashboard would otherwise fatal on the
     * missing table or column. Both are checked because the upgrade step adds
     * them together but they are separate DDL statements. Cached per request.
     *
     * @return bool
     */
    protected static function collections_ready(): bool {
        global $DB;

        static $ready = null;
        if ($ready === null) {
            $dbman = $DB->get_manager();
            $ready = $dbman->table_exists('local_oerexchange_collections')
                && $dbman->field_exists('local_oerexchange_reports', 'collectionid');
        }

        return $ready;
    }

    /**
     * Count of open reports about a RESOURCE.
     *
     * The explicit `resourceid IS NOT NULL` is the point of this method:
     * {local_oerexchange_reports} is polymorphic from local_oerexchange build
     * 2026101900 (resourceid became nullable, a nullable collectionid was
     * added, exactly one of the two is set on a row). Counting the whole table
     * under a heading that links to the resource queue and sits above a list
     * of resource titles would have quietly inflated this figure by every
     * collection report on the site.
     *
     * @return int
     */
    public static function get_open_report_count(): int {
        global $DB;
        return $DB->count_records_select(
            'local_oerexchange_reports',
            'status = :status AND resourceid IS NOT NULL',
            ['status' => 'open']
        );
    }

    /**
     * Most recent open reports about a resource, oldest-created queue order
     * matching moderate.php, each enriched with the reported resource's title.
     *
     * @return \stdClass[] each with ->id, ->resourceid, ->type, ->timecreated, ->resourcetitle
     */
    public static function get_recent_open_reports(): array {
        global $DB;

        // LEFT JOIN so a report whose resource row is gone still lists, with
        // resourcetitle null (rendered as the "deleted resource" label) —
        // and one query instead of one per row.
        //
        // `r.resourceid IS NOT NULL` for the reason given on
        // get_open_report_count(): without it a collection report arrives here
        // as a row the join cannot match, and renders as a resource that has
        // been deleted — a wrong answer rather than a missing one.
        return array_values($DB->get_records_sql(
            "SELECT r.*, res.title AS resourcetitle
               FROM {local_oerexchange_reports} r
          LEFT JOIN {local_oerexchange_resources} res ON res.id = r.resourceid
              WHERE r.status = :status
                    AND r.resourceid IS NOT NULL
           ORDER BY r.timecreated ASC",
            ['status' => 'open'],
            0,
            self::RECENT_LIMIT
        ));
    }

    /**
     * Count of open reports about a COLLECTION.
     *
     * @return int
     */
    public static function get_open_collection_report_count(): int {
        global $DB;
        if (!self::collections_ready()) {
            return 0;
        }
        return $DB->count_records_select(
            'local_oerexchange_reports',
            'status = :status AND collectionid IS NOT NULL',
            ['status' => 'open']
        );
    }

    /**
     * Most recent open reports about a collection, in the same oldest-first
     * queue order moderate.php's collections section uses, each enriched with
     * the reported collection's title.
     *
     * The collection's title is aliased rather than selected as `c.*`: reports
     * and collections share id, status and timecreated column names, so a
     * wildcard would have overwritten half of each report row with the
     * collection's values.
     *
     * @return \stdClass[] each with ->id, ->collectionid, ->type, ->timecreated, ->collectiontitle
     */
    public static function get_recent_open_collection_reports(): array {
        global $DB;
        if (!self::collections_ready()) {
            return [];
        }

        // Same LEFT JOIN rationale as get_recent_open_reports(): a report
        // whose collection row has gone (a privacy erasure — the owner's own
        // delete tombstones the row instead) still lists, with a null title.
        return array_values($DB->get_records_sql(
            "SELECT r.*, c.title AS collectiontitle
               FROM {local_oerexchange_reports} r
          LEFT JOIN {local_oerexchange_collections} c ON c.id = r.collectionid
              WHERE r.status = :status
                    AND r.collectionid IS NOT NULL
           ORDER BY r.timecreated ASC",
            ['status' => 'open'],
            0,
            self::RECENT_LIMIT
        ));
    }

    /**
     * Count of failed backup parses. Same status/table moderate.php's own
     * "Failed parses" section queries.
     *
     * @return int
     */
    public static function get_failed_parse_count(): int {
        global $DB;
        return $DB->count_records('local_oerexchange_versions', ['status' => 'failed']);
    }

    /**
     * Most recent failed parses, newest first (matching moderate.php's own
     * query for this section), each enriched with the resource's title.
     *
     * @return \stdClass[] each with ->id, ->resourceid, ->parseerror, ->timecreated, ->resourcetitle
     */
    public static function get_recent_failed_parses(): array {
        global $DB;

        // Same LEFT JOIN rationale as get_recent_open_reports().
        return array_values($DB->get_records_sql(
            "SELECT v.*, res.title AS resourcetitle
               FROM {local_oerexchange_versions} v
          LEFT JOIN {local_oerexchange_resources} res ON res.id = v.resourceid
              WHERE v.status = :status
           ORDER BY v.timecreated DESC",
            ['status' => 'failed'],
            0,
            self::RECENT_LIMIT
        ));
    }

    /**
     * Count of pending site registrations.
     *
     * @return int
     */
    public static function get_pending_site_count(): int {
        global $DB;
        return $DB->count_records('local_oerexchange_sites', ['status' => 'pending']);
    }

    /**
     * Most recent pending site registrations, oldest first (matching
     * manage_sites.php's own "Pending sites" ordering).
     *
     * @return \stdClass[] each with ->id, ->name, ->contact, ->timecreated
     */
    public static function get_recent_pending_sites(): array {
        global $DB;

        return array_values($DB->get_records(
            'local_oerexchange_sites',
            ['status' => 'pending'],
            'timecreated ASC',
            '*',
            0,
            self::RECENT_LIMIT
        ));
    }
}
