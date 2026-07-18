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
 * Builds the moderation-queue summary shown by the block: pending reports,
 * failed backup parses, and pending site registrations, each with a count
 * and a handful of the most recent items.
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
     * Build the full moderation-queue summary.
     *
     * @return array{
     *     reportcount: int, reports: \stdClass[],
     *     failedparsecount: int, failedparses: \stdClass[],
     *     sitecount: int, sites: \stdClass[]
     * }
     */
    public static function get_summary(): array {
        return [
            'reportcount' => self::get_open_report_count(),
            'reports' => self::get_recent_open_reports(),
            'failedparsecount' => self::get_failed_parse_count(),
            'failedparses' => self::get_recent_failed_parses(),
            'sitecount' => self::get_pending_site_count(),
            'sites' => self::get_recent_pending_sites(),
        ];
    }

    /**
     * Count of open reports.
     *
     * @return int
     */
    public static function get_open_report_count(): int {
        global $DB;
        return $DB->count_records('local_oerexchange_reports', ['status' => 'open']);
    }

    /**
     * Most recent open reports, oldest-created queue order matching
     * moderate.php, each enriched with the reported resource's title.
     *
     * @return \stdClass[] each with ->id, ->resourceid, ->type, ->timecreated, ->resourcetitle
     */
    public static function get_recent_open_reports(): array {
        global $DB;

        $reports = $DB->get_records(
            'local_oerexchange_reports',
            ['status' => 'open'],
            'timecreated ASC',
            '*',
            0,
            self::RECENT_LIMIT
        );

        $items = [];
        foreach ($reports as $report) {
            $resource = $DB->get_record('local_oerexchange_resources', ['id' => $report->resourceid]);
            $report->resourcetitle = $resource ? $resource->title : null;
            $items[] = $report;
        }
        return $items;
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

        $versions = $DB->get_records(
            'local_oerexchange_versions',
            ['status' => 'failed'],
            'timecreated DESC',
            '*',
            0,
            self::RECENT_LIMIT
        );

        $items = [];
        foreach ($versions as $version) {
            $resource = $DB->get_record('local_oerexchange_resources', ['id' => $version->resourceid]);
            $version->resourcetitle = $resource ? $resource->title : null;
            $items[] = $version;
        }
        return $items;
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
