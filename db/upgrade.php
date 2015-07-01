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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_kent_upgrade($oldversion) {
    global $CFG, $DB, $SHAREDB;

    $dbman = $DB->get_manager();

    if (\local_kent\util\sharedb::available()) {
        $sharedbman = $SHAREDB->get_manager();
    }

    $taskman = new \local_kent\manager\task();
    $configman = new \local_kent\manager\config();
    $roleman = new \local_kent\manager\role();

    if ($oldversion < 2014080100) {
        // Define table to be dropped.
        $table = new xmldb_table('kent_trackers');

        // Conditionally launch drop table for.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table to be dropped.
        $table = new xmldb_table('kent_log_transfer');

        // Conditionally launch drop table for.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table to be dropped.
        $table = new xmldb_table('kent_log_buffer');

        // Conditionally launch drop table for.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
    }

    if ($oldversion < 2014100202) {
        // Create any missing groups.
        $rs = $DB->get_recordset('course');
        foreach ($rs as $course) {
            \local_kent\manager\group::course_created($course);
        }
        $rs->close();
        unset($rs);

        // Now go through every course and add
        // everyone in that course to the group.
        $rs = $DB->get_recordset_sql("
            SELECT g.id, c.id as courseid, GROUP_CONCAT(ue.userid) as userids

            FROM {groups} g
            INNER JOIN {course} c
                ON c.shortname = g.name

            INNER JOIN {enrol} e
                ON e.courseid = c.id
            INNER JOIN {user_enrolments} ue
                ON ue.enrolid=e.id

            INNER JOIN {context} ctx
                ON ctx.instanceid=e.courseid
                AND ctx.contextlevel=:ctxlevel

            INNER JOIN {role_assignments} ra
                ON ra.userid = ue.userid
                AND ra.contextid = ctx.id
            INNER JOIN {role} r
                ON r.id=ra.roleid
                AND r.shortname LIKE :match

            LEFT OUTER JOIN {groups_members} gm
                ON gm.userid = ue.userid AND gm.groupid=g.id

            WHERE gm.id IS NULL
            GROUP BY g.id
        ", array(
            "ctxlevel" => CONTEXT_COURSE,
            "match" => "%student%"
        ));

        foreach ($rs as $group) {
            // These enrolments are missing.
            $userids = explode(',', $group->userids);
            foreach ($userids as $userid) {
                \local_kent\manager\group::enrolment_created($group->courseid, $userid);
            }
        }
        $rs->close();
        unset($rs);

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014100202, 'local', 'kent');
    }

    /*
     * Add an index on some log table columns.
     */
    if ($oldversion < 2014120600) {
        // Define index ip (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('ip', XMLDB_INDEX_NOTUNIQUE, array('ip'));

        // Conditionally launch add index ip.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index eventname (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('eventname', XMLDB_INDEX_NOTUNIQUE, array('eventname'));

        // Conditionally launch add index eventname.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index contextinstanceid (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('contextinstanceid', XMLDB_INDEX_NOTUNIQUE, array('contextinstanceid'));

        // Conditionally launch add index contextinstanceid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index userid (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index userid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index relateduserid (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('relateduserid', XMLDB_INDEX_NOTUNIQUE, array('relateduserid'));

        // Conditionally launch add index relateduserid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014120600, 'local', 'kent');
    }

    /*
     * Add an index on some log table columns.
     */
    if ($oldversion < 2014121000) {

        // Define index contextinstanceid (not unique) to be dropped from logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('contextinstanceid', XMLDB_INDEX_NOTUNIQUE, array('contextinstanceid'));

        // Conditionally launch drop index contextinstanceid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index context (not unique) to be added to logstore_standard_log.
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('context', XMLDB_INDEX_NOTUNIQUE, array('contextlevel', 'contextinstanceid'));

        // Conditionally launch add index context.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014121000, 'local', 'kent');
    }

    /*
     * Add an index on some log table columns.
     */
    if ($oldversion < 2014121200) {
        // Define index enablecompletion (not unique) to be dropped from course.
        $table = new xmldb_table('course');
        $index = new xmldb_index('enablecompletion', XMLDB_INDEX_NOTUNIQUE, array('enablecompletion'));

        // Conditionally launch add index enablecompletion.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014121200, 'local', 'kent');
    }

    // SSES have asked us to change their category name and category.
    if ($oldversion < 2015010502 && $CFG->kent->distribution !== 'archive') {
        $oldname = 'Centre for Sports Studies';
        $newname = 'School of Sport and Exercise Sciences';

        $newparent = $DB->get_record('course_categories', array(
            'name' => 'Faculty of Sciences'
        ), 'id');

        $category = $DB->get_record('course_categories', array(
            'name' => $oldname
        ));

        if ($category) {
            $category->parent = $newparent->id;
            $category->name = $newname;

            require_once($CFG->libdir . '/coursecatlib.php');
            $coursecat = \coursecat::get($category->id);
            $coursecat->update($category);
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015010502, 'local', 'kent');
    }

    if ($oldversion < 2015010600) {
        $taskman->yearly_rollover();
        $taskman->configure_2015010600();

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015010600, 'local', 'kent');
    }

    if ($oldversion < 2015020900) {
        // Find all questions with no stamp.
        $rs = $DB->get_recordset_sql('SELECT * FROM {question} WHERE stamp="" OR version=""');
        foreach ($rs as $question) {
            if (empty($question->stamp)) {
                $question->stamp = make_unique_id_code();
            }

            if (empty($question->version)) {
                $question->version = make_unique_id_code();
            }

            $DB->update_record('question', $question);
        }
        $rs->close();

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015020900, 'local', 'kent');
    }

    // SHAREDB upgrade step.
    if ($oldversion < 2015021600 && isset($sharedbman)) {
        // Rename notifications.uid -> notifications.username.
        $table = new xmldb_table("notifications");
        if ($sharedbman->table_exists($table)) {
            $field = new xmldb_field('uid', XMLDB_TYPE_CHAR, '255', null, null, null, '', 'id');
            if ($sharedbman->field_exists($table, $field)) {
                $sharedbman->rename_field($table, $field, 'username');
            }

            // Rename notifications -> shared_notifications.
            $sharedbman->rename_table($table, 'shared_notifications');
        }

        // Rename rollovers -> shared_rollovers.
        $table = new xmldb_table("rollovers");
        if ($sharedbman->table_exists($table)) {
            $sharedbman->rename_table($table, 'shared_rollovers');
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015021600, 'local', 'kent');
    }

    if ($oldversion < 2015030500) {
        $configman->configure_20150305();

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015030500, 'local', 'kent');
    }

    if ($oldversion < 2015031000 && isset($sharedbman)) {
        // Define table shared_users to be created.
        $table = new xmldb_table('shared_users');

        // Adding fields to table shared_users.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table shared_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table shared_users.
        $table->add_index('shared_users_on_username', XMLDB_INDEX_NOTUNIQUE, array('username'));

        // Conditionally launch create table for shared_users.
        if (!$sharedbman->table_exists($table)) {
            $sharedbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031000, 'local', 'kent');
    }

    if ($oldversion < 2015031100) {
        // Define table memcached_log to be created.
        $table = new xmldb_table('memcached_log');

        // Adding fields to table memcached_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('definition', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table memcached_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table definition.
        $table->add_index('memcached_log_on_definition', XMLDB_INDEX_NOTUNIQUE, array('definition'));

        // Conditionally launch create table for memcached_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031100, 'local', 'kent');
    }

    if ($oldversion < 2015031200) {
        // Define table memcached_log to be created.
        $table = new xmldb_table('memcached_log');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031200, 'local', 'kent');
    }

    if ($oldversion < 2015031300) {
        $configman->configure_20150313();

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031300, 'local', 'kent');
    }

    if ($oldversion < 2015031600 && isset($sharedbman)) {
        // Define table shared_roles to be dropped.
        $table = new xmldb_table('shared_roles');

        // Conditionally launch drop table for shared_roles.
        if ($sharedbman->table_exists($table)) {
            $sharedbman->drop_table($table);
        }

        // Define table shared_role_assignments to be dropped.
        $table = new xmldb_table('shared_role_assignments');

        // Conditionally launch drop table for shared_role_assignments.
        if ($sharedbman->table_exists($table)) {
            $sharedbman->drop_table($table);
        }

        // Define table shared_roles to be created.
        $table = new xmldb_table('shared_roles');

        // Adding fields to table shared_roles.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextlevel', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextname', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table shared_roles.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for shared_roles.
        if (!$sharedbman->table_exists($table)) {
            $sharedbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031600, 'local', 'kent');
    }

    if ($oldversion < 2015031601 && isset($sharedbman)) {
        // Define table shared_config to be dropped.
        $table = new xmldb_table('shared_config');

        // Conditionally launch drop table for shared_config.
        if ($sharedbman->table_exists($table)) {
            $sharedbman->drop_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015031601, 'local', 'kent');
    }

    if ($oldversion < 2015040100) {
        {
            // Define table course_notifications to be created.
            $table = new xmldb_table('course_notifications');

            // Adding fields to table course_notifications.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('contextid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('extref', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('dismissable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

            // Adding keys to table course_notifications.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('k_cidctxextref', XMLDB_KEY_UNIQUE, array('courseid', 'contextid', 'extref'));

            // Adding indexes to table course_notifications.
            $table->add_index('i_courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

            // Conditionally launch create table for course_notifications.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        {
            // Define table course_notifications_seen to be created.
            $table = new xmldb_table('course_notifications_seen');

            // Adding fields to table course_notifications_seen.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('nid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table course_notifications_seen.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('k_niduserid', XMLDB_KEY_UNIQUE, array('nid', 'userid'));

            // Adding indexes to table course_notifications_seen.
            $table->add_index('i_nid', XMLDB_INDEX_NOTUNIQUE, array('nid'));

            // Conditionally launch create table for course_notifications_seen.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015040100, 'local', 'kent');
    }

    if ($oldversion < 2015041500) {
        // Configure new Kent managed role.
        $roleman->configure('convenor');

        // Configs.
        $configman->configure_20150415();

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015041500, 'local', 'kent');
    }

    if ($oldversion < 2015041600) {
        // Configs.
        $configman->configure_20150416();

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015041600, 'local', 'kent');
    }

    if ($oldversion < 2015042300) {
        // Define field type to be added to course_notifications.
        $table = new xmldb_table('course_notifications');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'message');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field actionable to be added to course_notifications.
        $field = new xmldb_field('actionable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'type');

        // Conditionally launch add field actionable.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index i_course_actionable (not unique) to be added to course_notifications.
        $index = new xmldb_index('i_course_actionable', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'actionable'));

        // Conditionally launch add index i_course_actionable.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015042300, 'local', 'kent');
    }

    if ($oldversion < 2015042301) {
        // Changing type of field type on table course_notifications to char.
        $table = new xmldb_table('course_notifications');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '18', null, XMLDB_NOTNULL, null, 'warning', 'message');

        // Launch change of type for field type.
        $dbman->change_field_type($table, $field);

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015042301, 'local', 'kent');
    }

    if ($oldversion < 2015042800) {
        // Configs.
        $configman->configure_20150428();

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015042800, 'local', 'kent');
    }

    // SHAREDB upgrade step.
    if ($oldversion < 2015042801 && isset($sharedbman)) {
        // Rename notifications.uid -> notifications.username.
        $table = new xmldb_table("shared_notifications");
        if ($sharedbman->table_exists($table)) {
            $sharedbman->drop_table($table);
        }

        // local_kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015042801, 'local', 'kent');
    }

    // SHAREDB upgrade step.
    if ($oldversion < 2015050801 && isset($sharedbman)) {

        // Define table shared_vimeo_quota to be created.
        $table = new xmldb_table('shared_vimeo_quota');

        // Adding fields to table shared_vimeo_quota.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('used', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('free', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table shared_vimeo_quota.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for shared_vimeo_quota.
        if (!$sharedbman->table_exists($table)) {
            $sharedbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015050801, 'local', 'kent');
    }

    if ($oldversion < 2015051401) {
        // Define field type to be added to course_notifications.
        $table = new xmldb_table('course_notifications');
        $field = new xmldb_field('actionable', XMLDB_TYPE_INTEGER, '3', null, true, null, '0');

        // Drop index i_course_actionable (not unique) to be added to course_notifications.
        $index = new xmldb_index('i_course_actionable', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'actionable'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $dbman->change_field_type($table, $field);

        // Define index i_course_actionable (not unique) to be added to course_notifications.
        $index = new xmldb_index('i_course_actionable', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'actionable'));

        // Conditionally launch add index i_course_actionable.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015051401, 'local', 'kent');
    }

    if ($oldversion < 2015051402) {
        // Remove old capabilities.
        $roleman->remove_capability('mod/streamingvideo:addinstance');
        $roleman->remove_capability('mod/hotpot:addinstance');

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015051402, 'local', 'kent');
    }

    if ($oldversion < 2015051403) {
        // Add new capabilities.
        $roleman->add_capability('report/turnitin:view', array(
            'manager', 'teacher', 'editingteacher', 'convenor', 'flt'
        ));

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015051403, 'local', 'kent');
    }

    /**
     * This cleans up from very old installs.
     */
    if ($oldversion < 2015060500) {
        // First, drop old tables.
        $tables = array(
            'block_department_info',
            'kentturnitinview',
            'kentturnitinview_submissions',
            'linktadc',
            'local_dsubscription',
            'local_dsubscription_post',
            'local_dsubscription_q',
            'local_dsubscription_subs',
            'simplefileresource',
            'block_aspire_list',
            'question_ddmatch',
            'question_ddmatch_sub',
            'question_imagetarget',
            'question_order',
            'question_order_sub',
            'unittest_course_modules',
            'unittest_grade_categories',
            'unittest_grade_categories_history',
            'unittest_grade_grades',
            'unittest_grade_grades_history',
            'unittest_grade_items',
            'unittest_grade_items_history',
            'unittest_grade_outcomes',
            'unittest_grade_outcomes_history',
            'unittest_modules',
            'unittest_quiz',
            'unittest_scale',
            'unittest_scale_history'
        );
        foreach ($tables as $table) {
            $table = new xmldb_table($table);
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015060500, 'local', 'kent');
    }

    if ($oldversion < 2015061100) {
        // Deprecate hotpot.
        $activityman = new \local_kent\manager\activity('hotpot');
        $activityman->deprecate();

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015061100, 'local', 'kent');
    }

    if ($oldversion < 2015061102) {
        // Define index i_courseid_type (not unique) to be added to course_notifications.
        $table = new xmldb_table('course_notifications');
        $index = new xmldb_index('i_courseid_type', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'type'));

        // Conditionally launch add index i_courseid_type.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015061102, 'local', 'kent');
    }

    if ($oldversion < 2015061601) {
        set_config('enable_role_sync', 0, 'local_kent');

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015061601, 'local', 'kent');
    }

    if ($oldversion < 2015062202) {
        // Add new capabilities.
        $roleman->add_capability('moodle/my:manageblocks', array('user'));

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015062202, 'local', 'kent');
    }

    if ($oldversion < 2015062900) {
        // Remove manageblocks cap.
        $roleman->remove_capability('moodle/my:manageblocks');

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015062900, 'local', 'kent');
    }

    // Reset every user's home page for 2.9 deploy.
    if ($oldversion < 2015070101) {
        require_once($CFG->dirroot . '/my/lib.php');

        $userids = $DB->get_fieldset_select('user', 'id', null);
        foreach ($userids as $userid) {
            my_reset_page($userid, MY_PAGE_PRIVATE);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015070101, 'local', 'kent');
    }

    return true;
}
