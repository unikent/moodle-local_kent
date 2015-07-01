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

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Group Manager.
 */
class group
{
    /**
     * Observe a course created event.
     */
    public static function course_created($course) {
        global $DB;

        if (defined("PHPUNIT_TEST") && PHPUNIT_TEST) {
            return true;
        }

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
     */
    public static function enrolment_created($courseid, $userid) {
        global $DB;

        if (defined("PHPUNIT_TEST") && PHPUNIT_TEST) {
            return true;
        }

        $course = $DB->get_record('course', array(
            'id' => $courseid
        ));

        if (!$course) {
            return false;
        }

        // Get group ID.
        $group = $DB->get_record('groups', array(
            'courseid' => $course->id,
            'name' => $course->shortname
        ));

        if (!$group) {
            return false;
        }

        groups_add_member($group, $userid, 'enrol_connect');

        return true;
    }
}
