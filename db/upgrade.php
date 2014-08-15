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
        set_config('country', 'GB');
        set_config('cachetext', 900);
        set_config('filter_tex_convertformat', 'png');
        set_config('syncall', false, 'enrol_meta');
        set_config('doctonewwindow', true);
        set_config('hiddenuserfields', 'city,country,icqnumber,skypeid,yahooid,aimid,msnid,firstaccess,lastaccess,mycourses,groups,suspended');
    }

    return true;
}