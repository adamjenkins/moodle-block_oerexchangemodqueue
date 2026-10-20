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
// 2026101900 adds the collection sections. It deliberately reads the same
// serial as the local_oerexchange build it now depends on, because that build
// is what it is reporting on: the two numbers moving together is the fact to
// record. This block has no schema and no upgrade.php of its own — the serial
// moves so the new lang strings are re-read and the raised dependency pin
// below is re-evaluated.
// 2026102001 is the 1.0.9 release: camp listing screenshots only, no code
// change. Its dependency pin below stays on 2026101900.
$plugin->version   = 2026102001;
// 2025041400 = the Moodle 5.0 branching version — matches $supported's floor
// (and composer.json's ">=5.0 <5.4"); was 2024100700 (Moodle 4.5), which let
// a site below the tested range install the plugin.
$plugin->requires  = 2025041400;
$plugin->supported = [500, 503];
$plugin->release   = '1.0.9';
$plugin->maturity  = MATURITY_STABLE;

// This block is presentation-layer only: it reads local_oerexchange's own
// tables directly and has no data or logic of its own. Moodle has no
// subplugin relationship for block types, so this dependency declaration
// is the real enforcement mechanism — the installer refuses to install
// this block unless local_oerexchange is already present.
//
// Pinned to 2026101900, the build that adds collections: the three collection
// tables, the nullable {local_oerexchange_reports}.collectionid this block now
// queries, and moderate.php's collections sections its headings link to.
// Against an older Exchange the collection queries here hit a column and a
// table that do not exist, so this pin is load-bearing rather than advisory —
// stricter than the 2026080400 pin it replaces, which guarded only a page
// whose link would have 404ed.
//
// Was 2026080400, the build that added moderate_hidden.php, where the block
// called no new parent symbol and the pin existed only so a heading could not
// link to a page which did not exist.
//
// KEEP IN STEP WITH composer.json's `adamjenkins/moodle-local_oerexchange`
// constraint, which must express the same floor: build 2026101900 is
// local_oerexchange release 1.1.0, hence "^1.1.0". The two declarations were allowed to disagree once before: 1.0.4 pinned the
// build here while composer.json still said "*", so Composer would resolve
// this block against an older Exchange and leave Moodle's installer to refuse
// the pairing afterwards.
$plugin->dependencies = [
    'local_oerexchange' => 2026101900,
];
