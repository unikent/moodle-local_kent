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

namespace local_kent\group;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Group Manager.
 */
class manager
{
    /**
     * Observe a course created event.
     */
    public static function course_created($course) {
        global $DB;

        // Check if there is already a group.
        if ($DB->record_exists('groups', array(
            'courseid' => $course->id,
            'name' => $course->shortname
        ))) {
            return true;
        }

        // Create one.
        $data = new \stdClass();
        $data->name = $course->shortname;
        $data->courseid = $course->id;
        $data->description = '';
        groups_create_group($data);

        return true;
    }

    /**
     * Sync all enrolments for a course.
     * This isnt usually used, just here for the upgrade script.
     */
    public static function sync_enrolments($course) {
        global $DB;

        // Get group ID.
        $group = $DB->get_record('groups', array(
            'courseid' => $course->id,
            'name' => $course->shortname
        ));

        // Has this since been deleted?
        if (!$group) {
            return false;
        }

        // Get a list of enrolments for this course.
        $enrolments = $DB->get_records_sql("
            SELECT ue.userid FROM {user_enrolments} ue
            INNER JOIN {enrol} e
                ON e.id = ue.enrolid
            LEFT OUTER JOIN {groups_members} gm
                ON gm.userid = ue.userid
                AND gm.groupid = :groupid
            WHERE e.courseid = :courseid AND gm.id IS NULL
        ", array(
            "courseid" => $course->id,
            "groupid" => $group->id
        ));

        // Make sure all these users are in the group.
        foreach ($enrolments as $enrolment) {
            groups_add_member($group, $enrolment->userid, 'enrol_connect');
        }

        return true;
    }
}
