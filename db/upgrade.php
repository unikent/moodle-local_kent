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

    $taskman = new \local_kent\TaskManager();
    $configman = new \local_kent\ConfigManager();
    $roleman = new \local_kent\RoleManager();

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
            \local_kent\GroupManager::course_created($course);
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
                \local_kent\GroupManager::enrolment_created($group->courseid, $userid);
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

    return true;
}
