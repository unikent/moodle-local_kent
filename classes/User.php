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

namespace local_kent;

defined('MOODLE_INTERNAL') || die();

/**
 * User utility class.
 */
class User
{
    /**
     * Is the user a Departmental Admin?
     */
    public static function is_dep_admin($userid) {
        global $DB;

        $roleid = $DB->get_field('role', 'id', array(
            'shortname' => 'dep_admin'
        ));

        return user_has_role_assignment($userid, $roleid);
    }

    /**
     * Returns true if a user has any access to edit any course.
     */
    public static function has_course_update_role($userid) {
        global $DB;

        $contextpreload = \context_helper::get_preload_record_columns_sql('x');

        $courses = array();
        $rs = $DB->get_recordset_sql("SELECT c.id, $contextpreload
                                        FROM {course} c
                                        JOIN {context} x ON (c.id=x.instanceid AND x.contextlevel=".CONTEXT_COURSE.")");

        // Check capability for each course in turn
        foreach ($rs as $course) {
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);
            if (has_capability('moodle/course:update', $context, $userid)) {
                $rs->close();
                return true;
            }
        }
        $rs->close();

        return false;
    }

    /**
     * Returns a user preference.
     */
    public static function get_all_infodata() {
        global $DB, $USER;

        if (!isloggedin()) {
            return null;
        }

        $cache = \cache::make('local_kent', 'userprefs');
        $content = $cache->get($USER->id . "_prefs");

        if (!$content) {
            $content = array();

            $sql = <<<SQL
                SELECT uif.shortname as name, uid.data as value
                FROM {user_info_data} uid
                INNER JOIN {user_info_field} uif
                    ON uif.id = uid.fieldid
                WHERE
                    uid.userid = :userid
SQL;
            $prefs = $DB->get_records_sql($sql, array(
                'userid' => $USER->id
            ));

            foreach ($prefs as $pref) {
                $content[$pref->name] = $pref->value;
            }

            $cache->set($USER->id . "_prefs", $content);
        }

        return $content;
    }

    /**
     * Returns a user preference.
     */
    public static function get_infodata($name, $default = null) {
        $content = static::get_all_infodata();
        return isset($content[$name]) ? $content[$name] : $default;
    }

    /**
     * Returns a user preference.
     */
    public static function get_preferences() {
        global $USER;

        if (!isloggedin() || !isset($USER->preference)) {
            return array();
        }

        check_user_preferences_loaded($USER);

        return $USER->preference;
    }

    /**
     * Returns a user preference.
     */
    public static function get_preference($name, $default = null) {
        if (strpos($name, 'kent_') !== 0) {
            $name = "kent_{$name}";
        }

        $prefs = static::get_preferences();

        return isset($prefs[$name]) ? $prefs[$name] : $default;
    }

    /**
     * Return beta preferences.
     */
    public static function get_beta_preferences() {
        $prefs = static::get_preference('betaprefs', array());
        if (empty($prefs)) {
            return array();
        }

        $data = array();

        $prefs = explode(',', $prefs);
        foreach ($prefs as $pref) {
            list($k, $v) = explode('=', $pref);
            $data[$k] = $v == '1';
        }

        return $data;
    }

    /**
     * Returns a beta preference.
     */
    public static function get_beta_preference($name, $default = null) {
        static $content = null;
        if (!isset($content)) {
            $content = static::get_beta_preferences();
        }

        return isset($content[$name]) ? $content[$name] : $default;
    }
}
