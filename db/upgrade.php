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

    if ($oldversion < 2014081500) {
        /**
         * Core settings.
         */
        set_config('loglifetime', 365, 'logstore_standard');
        set_config('enablecourserequests', true);
        set_config('country', 'GB');

        /**
         * Theme settings.
         */
        set_config('enabledevicedetection', true);
        set_config('frontpagecourselimit', 20);
        set_config('newsitems', 2);
        set_config('calendar_adminseesall', true);
        set_config('calendar_startwday', 1);
        set_config('enablegravatar', true);

        /**
         * Experimental settings.
         */
        set_config('enablegroupmembersonly', true);
        set_config('dndallowtextandlinks', true);
        set_config('enablecssoptimiser', true);

        /**
         * Security policy.
         */
        set_config('protectusernames', true);
        set_config('forcelogin', false);
        set_config('forceloginforprofiles', true);
        set_config('runclamonupload', true);
        set_config('clamfailureonupload', 'donothing');
        set_config('sessioncookie', 'km' . $CFG->kent->distribution);
        set_config('sessioncookiepath', '/' . $CFG->kent->distribution . '/');

        /**
         * Navigation.
         */
        set_config('navshowmycoursecategories', true);
        set_config('navshowallcourses', true);
        set_config('navsortmycoursessort', 'shortname');
        set_config('navcourselimit', 20);
        set_config('defaulthomepage', 1);
        set_config('courselistshortnames', true);

        /**
         * Support.
         */
        set_config('supportname', 'IT Helpdesk');
        set_config('supportemail', 'helpdesk@kent.ac.uk');
        set_config('supportpage', 'http://www.kent.ac.uk/itservices');

        /**
         * Hipchat.
         */
        set_config('default_name', 'Moodle ' . ucwords($CFG->kent->distribution), 'local_hipchat');

        /**
         * Aspire Lists.
         */
        set_config('targetAspire', 'http://resourcelists.kent.ac.uk', 'mod_aspirelists');
        set_config('altTargetAspire', 'http://medwaylists.kent.ac.uk', 'mod_aspirelists');

        /**
         * OnlineSurvey.
         */
        set_config('block_onlinesurvey_survey_server', 'http://evasys-dmz.kent.ac.uk/evasys/services/soapserver-v60.wsdl');
        set_config('block_onlinesurvey_survey_login', 'https://moduleeval.kent.ac.uk/evasys/');
        set_config('block_onlinesurvey_survey_user', 'soap');

        /**
         * Panopto.
         */
        set_config('block_panopto_instance_name', 'Moodle');
        set_config('block_panopto_server_name', 'player.kent.ac.uk');

        /**
         * Streaming server.
         */
        set_config('strserver', 'cow.kent.ac.uk', 'mod_streamingvideo');

        /**
         * Turnitin.
         */
        set_config('turnitin_apiurl', 'https://submit.ac.uk/api.asp');
        set_config('turnitin_account_id', 2642);
        set_config('turnitin_useanon', true);

        /**
         * Default module settings.
         */
        set_config('visible', false, 'moodlecourse');
        set_config('format', 'topics', 'moodlecourse');
        set_config('numsections', 12, 'moodlecourse');

        /**
         * Misc.
         */
        set_config('filter_tex_convertformat', 'png');
        set_config('syncall', false, 'enrol_meta');
        set_config('doctonewwindow', true);
        set_config('hiddenuserfields', 'city,country,icqnumber,skypeid,yahooid,aimid,msnid,firstaccess,lastaccess,mycourses,groups,suspended');
    }

    if ($oldversion < 2014100202) {
        // Create any missing groups.
        $rs = $DB->get_recordset('course');
        foreach ($rs as $course) {
            \local_kent\group\manager::course_created($course);
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
                \local_kent\group\manager::enrolment_created($group->courseid, $userid);
            }
        }
        $rs->close();
        unset($rs);

        // Connect savepoint reached.
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

        // Connect savepoint reached.
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

        // Connect savepoint reached.
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

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014121200, 'local', 'kent');
    }

    return true;
}