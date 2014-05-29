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
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014043001) {
        // Define table local_kent_log_buffer to be created.
        $table = new xmldb_table('kent_log_buffer');

        // Adding fields to table local_kent_log_buffer.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('module', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('info', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_kent_log_buffer.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_kent_log_buffer.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014043001, 'local', 'kent');
    }

    if ($oldversion < 2014043002) {
        // Define table local_kent_trackers to be renamed to kent_trackers.
        $table = new xmldb_table('local_kent_trackers');

        // Launch rename table for local_kent_trackers.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'kent_trackers');
        }

        // Define table local_kent_log_buffer to be renamed to kent_log_buffer.
        $table = new xmldb_table('local_kent_log_buffer');

        // Launch rename table for local_kent_log_buffer.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'kent_log_buffer');
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014043002, 'local', 'kent');
    }

    if ($oldversion < 2014050100) {
        // Define table local_kent_log_buffer to be created.
        $table = new xmldb_table('kent_log_transfer');

        // Adding fields to table local_kent_log_buffer.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('module', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('info', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_kent_log_buffer.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_kent_log_buffer.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014050100, 'local', 'kent');
    }

    if ($oldversion < 2014050200) {
        $DB->delete_records('kent_trackers', array(
            'name' => 'kent_sess_memc_cron'
        ));

        $DB->insert_record('kent_trackers', array(
            'name' => 'memcached_tracker',
            'value' => 0
        ));

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014050200, 'local', 'kent');
    }

    if ($oldversion < 2014052900) {
        $DB->insert_record('kent_trackers', array(
            'name' => 'sharedb_tracker',
            'value' => 0
        ));

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2014052900, 'local', 'kent');
    }

    return true;
}