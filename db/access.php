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
 * Capability definitions for block_oerexchangemodqueue.
 *
 * Deliberately narrower than the other Exchange blocks: this block shows
 * moderation-sensitive data (open reports, failed parses, pending site
 * registrations), so only the 'manager' archetype can add it — NOT
 * 'editingteacher', unlike block_oerexchangebrowse/shares/quicklinks. The
 * real content-visibility gate is local/oerexchange:moderate, checked
 * explicitly in get_content(); these capabilities only control who can add
 * the block instance in the first place.
 *
 * @package    block_oerexchangemodqueue
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Deliberately no 'clonepermissionsfrom': that key makes the installer
    // copy role_capabilities rows verbatim from the referenced capability
    // and ignore 'archetypes' entirely (see lib/accesslib.php, "we ignore
    // archetype key if we have cloned permissions"). The idiom other
    // blocks use (e.g. block_admin_bookmarks, cloning from
    // moodle/site:manageblocks / moodle/my:manageblocks) would silently
    // grant this to 'editingteacher' and every authenticated 'user'
    // archetype — exactly what this block must NOT do. 'archetypes' alone
    // is the correct, narrower mechanism here.
    'block/oerexchangemodqueue:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    'block/oerexchangemodqueue:myaddinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
