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

namespace block_oerexchangemodqueue;

use block_oerexchangemodqueue\local\content_builder;

/**
 * Tests for content_builder::get_summary() and the block-level capability
 * gate in block_oerexchangemodqueue::get_content().
 *
 * @package    block_oerexchangemodqueue
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content_builder::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\block_oerexchangemodqueue::class)]
final class content_builder_test extends \advanced_testcase {
    /**
     * Construct a fresh block_oerexchangemodqueue instance, ready for
     * get_content() to be called directly (no DB block_instances row
     * needed: init()/get_content() below don't touch $this->page or
     * $this->instance).
     *
     * @return \block_oerexchangemodqueue
     */
    protected function new_block(): \block_oerexchangemodqueue {
        global $CFG;
        require_once($CFG->dirroot . '/lib/blocklib.php');
        block_load_class('oerexchangemodqueue');
        $block = new \block_oerexchangemodqueue();
        $block->init();
        return $block;
    }

    /**
     * Insert a local_oerexchange_sites row and return its id.
     *
     * @param string $status
     * @param int $timecreated
     * @return int
     */
    protected function insert_site(string $status, int $timecreated): int {
        global $DB;
        return (int) $DB->insert_record('local_oerexchange_sites', (object) [
            'name' => 'Site ' . $status . ' ' . $timecreated,
            'url' => 'https://example.test/' . $timecreated,
            'contact' => 'contact@example.test',
            'serviceuserid' => null,
            'status' => $status,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);
    }

    /**
     * Insert a local_oerexchange_resources row and return its id.
     *
     * @param int $siteid
     * @param string $title
     * @return int
     */
    protected function insert_resource(int $siteid, string $title): int {
        global $DB;
        $now = time();
        return (int) $DB->insert_record('local_oerexchange_resources', (object) [
            'type' => 'course',
            'title' => $title,
            'summary' => '',
            'language' => 'en',
            'tags' => '',
            'licenseshortname' => 'cc-by',
            'activitytype' => null,
            'courseformat' => null,
            'creatorid' => 2,
            'siteid' => $siteid,
            'status' => 'published',
            'downloadcount' => 0,
            'importcount' => 0,
            'forkedfromid' => null,
            'timeshared' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Insert a local_oerexchange_reports row.
     *
     * @param int $resourceid
     * @param string $status
     * @param int $timecreated
     */
    protected function insert_report(int $resourceid, string $status, int $timecreated): void {
        global $DB;
        $DB->insert_record('local_oerexchange_reports', (object) [
            'resourceid' => $resourceid,
            'userid' => 2,
            'type' => 'quality',
            'details' => 'details',
            'status' => $status,
            'resolvernote' => null,
            'timecreated' => $timecreated,
            'timeresolved' => null,
        ]);
    }

    /**
     * Insert a local_oerexchange_versions row.
     *
     * @param int $resourceid
     * @param string $status
     * @param int $timecreated
     */
    protected function insert_version(int $resourceid, string $status, int $timecreated): void {
        global $DB;
        $DB->insert_record('local_oerexchange_versions', (object) [
            'resourceid' => $resourceid,
            'versionnumber' => 1,
            'itemid' => 1,
            'filename' => 'backup.mbz',
            'filesize' => 100,
            'moodleversion' => null,
            'backupversion' => null,
            'structurejson' => null,
            'requiredplugins' => null,
            'status' => $status,
            'parseerror' => $status === 'failed' ? 'Parse error' : null,
            'timecreated' => $timecreated,
        ]);
    }

    public function test_get_summary_counts_only_the_relevant_status(): void {
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'Test resource');

        $this->insert_report($resourceid, 'open', time());
        $this->insert_report($resourceid, 'open', time());
        $this->insert_report($resourceid, 'resolved', time());
        $this->insert_report($resourceid, 'dismissed', time());

        $this->insert_version($resourceid, 'failed', time());
        $this->insert_version($resourceid, 'ready', time());
        $this->insert_version($resourceid, 'parsing', time());

        $this->insert_site('pending', time());
        $this->insert_site('pending', time());
        $this->insert_site('revoked', time());

        $summary = content_builder::get_summary();

        $this->assertSame(2, $summary['reportcount'], 'only open reports should be counted');
        $this->assertSame(1, $summary['failedparsecount'], 'only failed versions should be counted');
        // The FK-target site inserted as 'active' must not be counted alongside the two 'pending' ones.
        $this->assertSame(2, $summary['sitecount'], 'only pending sites should be counted');
    }

    public function test_recent_items_are_enriched_with_resource_title(): void {
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'Reported resource');

        $this->insert_report($resourceid, 'open', time());
        $this->insert_version($resourceid, 'failed', time());

        $summary = content_builder::get_summary();

        $this->assertCount(1, $summary['reports']);
        $this->assertSame('Reported resource', $summary['reports'][0]->resourcetitle);

        $this->assertCount(1, $summary['failedparses']);
        $this->assertSame('Reported resource', $summary['failedparses'][0]->resourcetitle);
    }

    public function test_recent_items_are_null_titled_when_resource_no_longer_exists(): void {
        global $DB;
        $this->resetAfterTest();

        // A report/version pointing at a resourceid that was deleted (or never existed).
        $this->insert_report(999999, 'open', time());
        $this->insert_version(999999, 'failed', time());

        $summary = content_builder::get_summary();

        $this->assertCount(1, $summary['reports']);
        $this->assertNull($summary['reports'][0]->resourcetitle);

        $this->assertCount(1, $summary['failedparses']);
        $this->assertNull($summary['failedparses'][0]->resourcetitle);
    }

    public function test_recent_items_are_capped_at_the_recent_limit(): void {
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'Busy resource');

        $extra = content_builder::RECENT_LIMIT + 2;
        for ($i = 0; $i < $extra; $i++) {
            $this->insert_report($resourceid, 'open', time() + $i);
        }

        $summary = content_builder::get_summary();

        $this->assertSame($extra, $summary['reportcount']);
        $this->assertCount(content_builder::RECENT_LIMIT, $summary['reports']);
    }

    public function test_pending_sites_are_returned_oldest_first(): void {
        $this->resetAfterTest();

        $now = time();
        $olderid = $this->insert_site('pending', $now - 100);
        $newerid = $this->insert_site('pending', $now);

        $summary = content_builder::get_summary();

        $this->assertCount(2, $summary['sites']);
        $this->assertSame($olderid, (int) $summary['sites'][0]->id);
        $this->assertSame($newerid, (int) $summary['sites'][1]->id);
    }

    public function test_get_content_is_empty_for_a_user_without_the_moderate_capability(): void {
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'Visible only to moderators');
        $this->insert_report($resourceid, 'open', time());

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertSame('', $content->text, 'a non-moderator must not see any moderation-queue content');
    }

    public function test_get_content_shows_the_summary_for_a_manager(): void {
        global $DB;
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'Visible to moderators');
        $this->insert_report($resourceid, 'open', time());

        $manager = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($managerroleid, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertNotSame('', $content->text);
        $this->assertStringContainsString('Visible to moderators', $content->text);
    }

    /**
     * Found in a 2026-07-19 code review: the block used to gate ALL three
     * sections on local/oerexchange:moderate alone, even though the
     * "pending sites" section links to manage_sites.php, which actually
     * requires the DIFFERENT capability local/oerexchange:managesites. A
     * user holding only local/oerexchange:moderate (a real, if unusual,
     * custom-role configuration - the two capabilities default to the same
     * archetype but are declared separately) used to see a "pending sites"
     * count and a dead link to a page they can't reach. Now they see the
     * reports/failed-parses sections they CAN act on, and the sites
     * section is simply omitted rather than shown-but-broken.
     */
    public function test_get_content_hides_pending_sites_from_a_moderator_who_cannot_manage_sites(): void {
        global $DB;
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'A reported resource');
        $this->insert_report($resourceid, 'open', time());
        $this->insert_site('pending', time());

        $roleid = create_role('Moderate only', 'moderateonly', 'Holds moderate but not managesites');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('local/oerexchange:moderate', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringContainsString('A reported resource', $content->text);
        $this->assertStringNotContainsString('manage_sites.php', $content->text);
    }

    /**
     * The mirror case: a user holding only managesites (not moderate) sees
     * the pending-sites section they can act on, but not reports/failed
     * parses, which link to moderate.php - a page they can't reach.
     */
    public function test_get_content_shows_only_pending_sites_for_a_site_manager_who_cannot_moderate(): void {
        $this->resetAfterTest();

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, 'A reported resource');
        $this->insert_report($resourceid, 'open', time());
        $this->insert_site('pending', time());

        $roleid = create_role('Manage sites only', 'managesitesonly', 'Holds managesites but not moderate');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('local/oerexchange:managesites', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringNotContainsString('A reported resource', $content->text);
        $this->assertStringContainsString('manage_sites.php', $content->text);
    }

    /**
     * Regression test: report/failed-parse titles used to render through
     * s($title), so a multilang-marked-up resource title showed as raw
     * `<span lang="en" class="multilang">...` markup instead of collapsing
     * to one language. Enables the filter trio locally rather than relying
     * on site config, and pins the double-escape guard (a title containing
     * `&` must be escaped exactly once).
     */
    public function test_get_content_renders_reported_and_failed_parse_titles_through_multilang(): void {
        global $DB;
        $this->resetAfterTest();

        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $title = '<span lang="en" class="multilang">Coastal &amp; Marine Studies</span>'
            . '<span lang="ja" class="multilang">沿岸海洋学</span>';

        $siteid = $this->insert_site('active', time());
        $resourceid = $this->insert_resource($siteid, $title);
        $this->insert_report($resourceid, 'open', time());
        $this->insert_version($resourceid, 'failed', time());

        $manager = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($managerroleid, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringNotContainsString('沿岸海洋学', $content->text);
        $this->assertStringNotContainsString('multilang', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
        // The title appears twice (reports section, failed-parses section),
        // each occurrence escaped exactly once.
        $this->assertSame(2, substr_count($content->text, 'Coastal &amp; Marine Studies'));
        $this->assertSame(0, substr_count($content->text, 'Coastal & Marine Studies'));
    }

    /**
     * Regression test for the pending-sites section: the registering site's
     * own name used to render through s($site->name), so a
     * multilang-marked-up site name showed as visible literal
     * `<span lang="en" class="multilang">...` markup instead of collapsing
     * to one language. A site name is human-readable free text entered at
     * registration, exactly like a resource title.
     *
     * Uses the managesites-only role (not manager) so the assertions can
     * only be satisfied by the pending-sites section — no report or
     * failed-parse title is rendered at all in this scenario.
     */
    public function test_get_content_renders_pending_site_names_through_multilang(): void {
        global $DB;
        $this->resetAfterTest();

        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $name = '<span lang="en" class="multilang">Arts &amp; Sciences College</span>'
            . '<span lang="ja" class="multilang">文理大学</span>';

        $DB->insert_record('local_oerexchange_sites', (object) [
            'name' => $name,
            'url' => 'https://pending.example.test/',
            'contact' => 'contact@example.test',
            'serviceuserid' => null,
            'status' => 'pending',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $roleid = create_role('Manage sites only', 'managesitesonly', 'Holds managesites but not moderate');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('local/oerexchange:managesites', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringContainsString('Arts &amp; Sciences College', $content->text);
        $this->assertStringNotContainsString('文理大学', $content->text);
        $this->assertStringNotContainsString('multilang', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
        // Exactly one escaped ampersand: not raw, and not double-escaped.
        $this->assertSame(1, substr_count($content->text, '&amp;'));
    }

    /**
     * get_modhidden_count() counts moderator takedowns only.
     *
     * 'removed' is deliberately excluded: the Exchange's stale-courseware
     * janitor writes that status automatically, so counting it would put
     * automatic removals under a heading that says a moderator hid them.
     *
     * @return void
     */
    public function test_modhidden_count_excludes_janitor_removals(): void {
        $this->resetAfterTest();
        global $DB;

        $siteid = $this->insert_site('active', time());
        $held = $this->insert_resource($siteid, 'Held by a moderator');
        $removed = $this->insert_resource($siteid, 'Removed as abandoned');
        $this->insert_resource($siteid, 'Perfectly fine');

        $DB->set_field('local_oerexchange_resources', 'status', 'modhidden', ['id' => $held]);
        $DB->set_field('local_oerexchange_resources', 'status', 'removed', ['id' => $removed]);

        $this->assertSame(1, content_builder::get_modhidden_count());
    }

    /**
     * The block links moderators to the hidden-resources report with a count.
     *
     * @return void
     */
    public function test_block_shows_hidden_resources_link_for_a_moderator(): void {
        $this->resetAfterTest();
        global $DB;

        $siteid = $this->insert_site('active', time());
        $held = $this->insert_resource($siteid, 'Held by a moderator');
        $DB->set_field('local_oerexchange_resources', 'status', 'modhidden', ['id' => $held]);

        $roleid = create_role('Moderator only', 'moderateonly', 'Holds moderate');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('local/oerexchange:moderate', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $content = $this->new_block()->get_content();

        $this->assertStringContainsString('/local/oerexchange/moderate_hidden.php', $content->text);
        $this->assertStringContainsString(
            get_string('modqueue_hiddenresources', 'block_oerexchangemodqueue', 1),
            $content->text
        );
    }

    /**
     * Someone without the moderate capability is not shown the report link.
     *
     * @return void
     */
    public function test_block_hides_hidden_resources_link_without_the_capability(): void {
        $this->resetAfterTest();
        global $DB;

        $siteid = $this->insert_site('active', time());
        $held = $this->insert_resource($siteid, 'Held by a moderator');
        $DB->set_field('local_oerexchange_resources', 'status', 'modhidden', ['id' => $held]);

        $roleid = create_role('Manage sites only 2', 'managesitesonly2', 'Holds managesites but not moderate');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('local/oerexchange:managesites', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $content = $this->new_block()->get_content();

        $this->assertStringNotContainsString('/local/oerexchange/moderate_hidden.php', $content->text);
    }
}
