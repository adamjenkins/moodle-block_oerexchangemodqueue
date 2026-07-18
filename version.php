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
$plugin->version   = 2026071900;
$plugin->requires  = 2024100700;
$plugin->supported = [500, 502];
$plugin->release   = '0.1.0';
$plugin->maturity  = MATURITY_ALPHA;

// This block is presentation-layer only: it reads local_oerexchange's own
// tables directly and has no data or logic of its own. Moodle has no
// subplugin relationship for block types, so this dependency declaration
// is the real enforcement mechanism — the installer refuses to install
// this block unless local_oerexchange is already present.
$plugin->dependencies = [
    'local_oerexchange' => ANY_VERSION,
];
