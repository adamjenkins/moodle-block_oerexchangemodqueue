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

/**
 * Version information for block_oerexchangemodqueue.
 *
 * @package    block_oerexchangemodqueue
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_oerexchangemodqueue';
// Bumped so composer.json's corrected dependency constraint ships as its own
// release: composer metadata is fixed at a tag, so it cannot be repaired in
// place once Packagist has ingested one.
// Serial reads 20260804 rather than today's 20260803 because it must be
// strictly greater than the previous one and the sequence was already a day
// ahead of the calendar; strictly-increasing is what Moodle's upgrade check
// uses, and lowering it would break upgrades on sites already carrying it.
$plugin->version   = 2026100400;
// 2025041400 = the Moodle 5.0 branching version — matches $supported's floor
// (and composer.json's ">=5.0 <5.4"); was 2024100700 (Moodle 4.5), which let
// a site below the tested range install the plugin.
$plugin->requires  = 2025041400;
$plugin->supported = [500, 503];
$plugin->release   = '1.0.6';
$plugin->maturity  = MATURITY_STABLE;

// This block is presentation-layer only: it reads local_oerexchange's own
// tables directly and has no data or logic of its own. Moodle has no
// subplugin relationship for block types, so this dependency declaration
// is the real enforcement mechanism — the installer refuses to install
// this block unless local_oerexchange is already present.
//
// Pinned to 2026080400, the build that adds moderate_hidden.php. The block
// calls no new class from the parent — it counts 'modhidden' rows with its
// own query, as it does for every other figure it shows — so the letter of
// the "raise ANY_VERSION when you call a new symbol" rule does not apply.
// The spirit does: against an older parent this block renders a heading that
// links to a page which does not exist. The pin is what the rule is actually
// protecting against.
//
// KEEP IN STEP WITH composer.json's `adamjenkins/moodle-local_oerexchange`
// constraint, which must express the same floor (build 2026080400 = release
// 1.0.7, hence "^1.0.7"). The dependency lives in two files that nothing
// cross-checks, and they were allowed to disagree: 1.0.4 pinned the build
// here while composer.json still said "*", so composer would happily resolve
// this block against an older Exchange and leave Moodle's own installer to
// refuse the pairing afterwards.
$plugin->dependencies = [
    'local_oerexchange' => 2026080400,
];
