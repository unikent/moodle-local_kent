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

        $sql = "SELECT COUNT(ra.id) as count
                FROM {role_assignments} ra
                WHERE userid = :userid AND roleid = (
                    SELECT r.id
                    FROM {role} r
                    WHERE r.shortname = :shortname
                    LIMIT 1
                )";

        return $DB->count_records_sql($sql, array(
            'userid' => $userid,
            'shortname' => 'dep_admin'
        )) > 0;
    }

    /**
     * Returns true if a user has any access to edit any course.
     */
    public static function has_course_update_role($userid) {
        global $DB;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            return true;
        }

        $sql = <<<SQL
            SELECT COUNT(ra.id)
            FROM mdl_role_assignments ra
            INNER JOIN mdl_context ctx
                ON ctx.id = ra.contextid
            INNER JOIN mdl_role_capabilities rc
                ON rc.roleid = ra.roleid
                AND (
                    ctx.path LIKE CONCAT("%/", rc.contextid, "/%")
                    OR ctx.path LIKE CONCAT("%/", rc.contextid)
                )
            WHERE ra.userid = :userid AND rc.capability = :capability AND rc.permission = 1
            GROUP BY rc.roleid
SQL;
        return $DB->count_records_sql($sql, array(
            'userid' => $userid,
            'capability' => 'moodle/course:update'
        )) > 0;
    }

    /**
     * Returns a user preference.
     */
    public static function get_user_preferences() {
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
    public static function get_user_preference($name, $default = null) {
        $content = static::get_user_preferences();
        return isset($content[$name]) ? $content[$name] : $default;
    }

    /**
     * Return beta preferences.
     */
    public static function get_beta_preferences() {
        global $USER;

        if (!isloggedin() || !isset($USER->preference)) {
            return array();
        }

        $prefs = $USER->preference;
        if (empty($prefs['betaprefs'])) {
            return array();
        }

        $data = array();

        $prefs = explode(',', $prefs['betaprefs']);
        foreach ($prefs as $pref) {
            list($k, $v) = explode('=', $pref);
            $data[$k] = $v == '1' ? true : false;
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