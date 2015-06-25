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
 * Activity manager
 */
class activity
{
    private $_name;

    /**
     * Create an instance of the activity manager.
     */
    public function __construct($activity) {
        $this->_name = $activity;
    }

    /**
     * Deprecate the activity.
     */
    public function deprecate() {
        global $DB;

        if (!$this->is_deprecated()) {
            debugging("[Deprecate] You forgot to add the module name to is_deprecated!");
        }

        // Grab a list of courses that have this activity.
        $courses = $DB->get_records_sql('
            SELECT c.id
            FROM {course} c
            INNER JOIN {course_modules} cm
                ON cm.course = c.id
            INNER JOIN {modules} m
                ON m.id = cm.module
                AND m.name = :module
        ', array(
            'module' => $this->_name
        ));

        // Notify all the courses that they have a deprecated activity.
        foreach ($courses as $course) {
            $this->notify($course->id);
        }

        // Remove capabilities.
        $roleman = new \local_kent\RoleManager();
        $roleman->remove_capability("mod/{$this->_name}:addinstance");
    }

    /**
     * Generate a deprecation notification if required.
     */
    public function notify($courseid) {
        if (!$this->is_deprecated()) {
            return true;
        }

        \local_kent\notification\deprecated::create(array(
            'objectid' => $courseid,
            'context' => \context_course::instance($courseid)
        ));

        return true;
    }

    /**
     * Returns true if this activity is deprecated.
     */
    public function is_deprecated() {
        return (
            $this->_name == 'turnitintool' ||
            $this->_name == 'hotpot'
        );
    }
}
