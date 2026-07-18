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

use block_oerexchangemodqueue\local\content_builder;

/**
 * OER Exchange: moderation queue block.
 *
 * A Dashboard block for moderators only: at-a-glance counts (and a few
 * recent items) of open reports, failed backup parses, and pending site
 * registrations, linking to local_oerexchange's own moderate.php and
 * manage_sites.php pages to act on them.
 *
 * Visibility is gated twice, deliberately (this is the one Exchange block
 * NOT meant for every logged-in account): db/access.php restricts who can
 * add the block instance to the 'manager' archetype, and get_content()
 * below independently re-checks local/oerexchange:moderate before showing
 * anything, as a defense-in-depth safety net rather than a substitute.
 *
 * @package    block_oerexchangemodqueue
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oerexchangemodqueue extends block_base {
    /**
     * Set the initial properties for the block.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_oerexchangemodqueue');
    }

    /**
     * Only one instance of this block makes sense per Dashboard.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * This is a Dashboard ("My Moodle") block only.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return ['my' => true];
    }

    /**
     * Build the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Defense-in-depth: db/access.php should already stop non-managers
        // from adding this block, but re-check the real controlling
        // capabilities here so content is never shown to a user who
        // shouldn't see it, regardless of how the instance got added.
        //
        // Two separate capabilities, checked separately (found in a
        // 2026-07-19 code review): reports/failed-parses link to
        // moderate.php, gated on local/oerexchange:moderate; pending sites
        // link to manage_sites.php, gated on the DIFFERENT capability
        // local/oerexchange:managesites. Both default to the manager
        // archetype, so this split is invisible in the default install,
        // but a custom role holding only one of the two would previously
        // either see a dead link to a page they can't reach, or have the
        // whole block hidden despite holding the capability for one of its
        // three sections. Show only the sections the viewer can act on.
        $context = context_system::instance();
        $canmoderate = has_capability('local/oerexchange:moderate', $context);
        $canmanagesites = has_capability('local/oerexchange:managesites', $context);
        if (!$canmoderate && !$canmanagesites) {
            return $this->content;
        }

        $summary = content_builder::get_summary();
        $this->content->text = $this->render_summary($summary, $canmoderate, $canmanagesites);

        return $this->content;
    }

    /**
     * Render the moderation-queue summary as block content markup.
     *
     * @param array $summary as returned by content_builder::get_summary()
     * @param bool $canmoderate viewer holds local/oerexchange:moderate — show reports/failed-parses
     * @param bool $canmanagesites viewer holds local/oerexchange:managesites — show pending sites
     * @return string
     */
    protected function render_summary(array $summary, bool $canmoderate, bool $canmanagesites): string {
        $moderateurl = new moodle_url('/local/oerexchange/moderate.php');
        $sitesurl = new moodle_url('/local/oerexchange/manage_sites.php');

        $sections = [];
        if ($canmoderate) {
            $sections[] = $this->render_section(
                get_string('modqueue_openreports', 'block_oerexchangemodqueue', $summary['reportcount']),
                $moderateurl,
                $summary['reports'],
                function (stdClass $report): string {
                    $title = $report->resourcetitle !== null
                        ? $report->resourcetitle
                        : get_string('modqueue_deletedresource', 'block_oerexchangemodqueue');
                    return s($title);
                }
            );
            $sections[] = $this->render_section(
                get_string('modqueue_failedparses', 'block_oerexchangemodqueue', $summary['failedparsecount']),
                $moderateurl,
                $summary['failedparses'],
                function (stdClass $version): string {
                    $title = $version->resourcetitle !== null
                        ? $version->resourcetitle
                        : get_string('modqueue_deletedresource', 'block_oerexchangemodqueue');
                    return s($title);
                }
            );
        }
        if ($canmanagesites) {
            $sections[] = $this->render_section(
                get_string('modqueue_pendingsites', 'block_oerexchangemodqueue', $summary['sitecount']),
                $sitesurl,
                $summary['sites'],
                function (stdClass $site): string {
                    return s($site->name);
                }
            );
        }

        return implode('', $sections);
    }

    /**
     * Render one summary section: a heading (with count) linking to the
     * relevant local_oerexchange management page, and a short list of the
     * most recent items.
     *
     * @param string $heading already-formatted heading text (count included)
     * @param moodle_url $url page to link the heading to
     * @param stdClass[] $items recent items for this section
     * @param callable $itemlabel (stdClass $item): string, already s()-escaped
     * @return string
     */
    protected function render_section(string $heading, moodle_url $url, array $items, callable $itemlabel): string {
        $out = html_writer::tag('h6', html_writer::link($url, $heading));

        if (empty($items)) {
            return $out;
        }

        $listitems = '';
        foreach ($items as $item) {
            $listitems .= html_writer::tag('li', $itemlabel($item));
        }
        $out .= html_writer::tag('ul', $listitems, ['class' => 'oerexchangemodqueue-list']);

        return $out;
    }
}
