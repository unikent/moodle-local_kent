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

namespace local_kent\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Config Manager.
 */
class config
{
    /**
     * Run all upgrade steps.
     */
    public function configure() {
        $this->configure_initial();
        $this->configure_20150305();
        $this->configure_20150313();
        $this->configure_20150415();
        $this->configure_20150416();
        $this->configure_20150826();
        $this->configure_20151103();
        $this->configure_20151106();
        $this->configure_20151210();
    }

    /**
     * Upgrade step for 2015010600.
     */
    public function configure_initial() {
        global $CFG;

        /**
         * Core settings.
         */
        set_config('defaulthomepage', \HOMEPAGE_MY);
        set_config('enablecourserequests', true);
        set_config('country', 'GB');
        set_config('loglifetime', 365, 'logstore_standard');
        set_config('texteditors', 'atto,tinymce,textarea');
        set_config('enablemobilewebservice', true);
        set_config('enableblogs', '0');
        set_config('enableportfolios', '1');
        set_config('auth', 'email,kentsaml');

        /**
         * Theme settings.
         */
        set_config('theme', 'kent');
        set_config('enabledevicedetection', true);
        set_config('frontpagecourselimit', 20);
        set_config('newsitems', 2);
        set_config('calendar_adminseesall', true);
        set_config('calendar_startwday', 1);
        set_config('enablegravatar', true);

        /**
         * Experimental settings.
         */
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
        set_config('courselistshortnames', true);

        /**
         * Support.
         */
        set_config('supportname', 'IT Helpdesk');
        set_config('supportemail', 'helpdesk@kent.ac.uk');
        set_config('supportpage', 'http://www.kent.ac.uk/itservices');

        /**
         * Aspire Lists.
         */
        set_config('baseurl', 'http://resourcelists.kent.ac.uk', 'aspirelists');
        set_config('altBaseurl', 'http://medwaylists.kent.ac.uk', 'aspirelists');

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
         * Quiz.
         */
        set_config('overduehandling', 'autosubmit', 'quiz');
        set_config('maximumgrade', 100, 'quiz');
        set_config('attempts', 1, 'quiz');
        set_config('questionsperpage', 5, 'quiz');
        set_config('decimalpoints', 0, 'quiz');

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
        set_config('hotpot_enablemymoodle', 0);
        set_config('hotpot_enablemymoodle', '0');
        set_config('glossary_defaultapproval', '0');
        set_config('forum_maxattachments', '2');
        set_config('grade_report_showquickfeedback', '1');
        set_config('grade_report_enableajax', '1');
        set_config('grade_report_showcalculations', '1');
        set_config('grade_report_showeyecons', '1');
        set_config('grade_report_showlocks', '1');
        set_config('grade_report_showuserimage', '0');
        set_config('messaginghidereadnotifications', '1');
    }

    /**
     * Upgrade step for 20150305.
     */
    public function configure_20150305() {
        set_config('enablecompletion', '1');
        set_config('enableoutcomes', '1');
        set_config('enableavailability', '1');
        set_config('enableplagiarism', '1');
    }

    /**
     * Upgrade step for 20150313.
     */
    public function configure_20150313() {
        set_config('core_media_enable_youtube', 1);
        set_config('core_media_enable_vimeo', 1);
        set_config('core_media_enable_mp3', 1);
        set_config('core_media_enable_flv', 1);
        set_config('core_media_enable_swf', 1);
        set_config('core_media_enable_html5audio', 1);
        set_config('core_media_enable_html5video', 1);

        set_config('core_media_enable_qt', 0);
        set_config('core_media_enable_wmp', 0);
        set_config('core_media_enable_rm', 0);
    }

    /**
     * Upgrade step for 20150415.
     */
    public function configure_20150415() {
        set_config('enablewebservices', 1);
        set_config('enablerssfeeds', 1);
        set_config('cookiesecure', 1);
        set_config('forum_enablerssfeeds', 1);
        set_config('forum_enabletimedposts', 1);
        set_config('requestcategoryselection', 1);
        set_config('grade_hiddenasdate', 1);
        set_config('grade_navmethod', 1);
        set_config('gradeexport', 'txt,xls');
        set_config('autolang', 0);
        set_config('langmenu', 0);
        set_config('debugdisplay', 0);
        set_config('facetoface_fromaddress', 'noreply@kent.ac.uk');
        set_config('facetoface_hidecost', 0);
        set_config('facetoface_hidediscount', 0);
    }

    /**
     * Upgrade step for 20150416.
     */
    public function configure_20150416() {
        set_config('hiddensections', 1, 'moodlecourse');
        set_config('enablecompletion', 1, 'moodlecourse');
        set_config('marks', 1, 'question_preview');
        set_config('maxbytes', 0, 'assignsubmission_file');
        set_config('requiremodintro', 0, 'book');
        set_config('allow_submissions', 1, 'cla');
        set_config('requiremodintro', 0, 'lesson');
        set_config('requiremodintro', 0, 'page');
        set_config('displayoptions', '5,6', 'page');
        set_config('autosaveperiod', 120, 'quiz');
        set_config('strserver', 'media.kent.ac.uk', 'streamingvideo');
        set_config('enablepeermark', 0, 'turnitintooltwo');
        set_config('useanon', 1, 'turnitintooltwo');
        set_config('default_type', 1, 'turnitintooltwo');
        set_config('default_allowlate', 1, 'turnitintooltwo');
        set_config('default_erater_dictionary', 'en_GB', 'turnitintooltwo');
        set_config('requiremodintro', 0, 'url');
        set_config('printintro', 0, 'url');
        set_config('convertformat', 'png', 'filter_tex');
    }

    /**
     * Upgrade step for 20150428.
     */
    public function configure_20150428() {
        set_config('format', 'standardweeks', 'moodlecourse');
        set_config('disabled', 0, 'format_standardweeks');
    }

    /**
     * Set the creatornewroleid.
     */
    public function configure_20150826() {
        global $DB;

        $id = $DB->get_field('role', 'id', array(
            'shortname' => 'convenor'
        ));

        set_config('creatornewroleid', $id);
    }

    /**
     * Support new enrol connect config.
     */
    public function configure_20151103() {
        if (KENT_MOODLE == LIVE_MOODLE) {
            set_config('defaultstatuses', 'A,J,P,R,T,W,Y,H,?', 'enrol_connect');
        } else {
            set_config('defaultstatuses', 'A,J,P,R,T,W,Y,H,X,?', 'enrol_connect');
        }
    }

    /**
     * Support Moodle 3.0.
     */
    public function configure_20151106() {
        set_config('showdate', 1, 'resource');
        set_config('allowborders', 1, 'atto_table');
        set_config('allowborderstyles', 1, 'atto_table');
        set_config('allowwidth', 1, 'atto_table');
    }

    /**
     * Improve topcoll.
     */
    public function configure_20151210() {
        set_config('defaultdisplayinstructions', 1, 'format_topcoll');
    }
}
