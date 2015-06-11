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
    private $_module;

    /**
     * Create an instance of the activity manager.
     */
    public function __construct($activity) {
        global $DB;

        $this->_module = $DB->get_record('modules', array(
            'name' => $activity
        ), '*', \MUST_EXIST);
    }

    /**
     * Deprecate the activity.
     */
    public function deprecate() {
        global $DB;

        if (!self::is_deprecated($this->_module->name)) {
            debugging("[Deprecate] You forgot to add the module name to is_deprecated!");
        }

        // Grab a list of courses that have this activity.
        $courses = $DB->get_records_sql('
            SELECT c.id
            FROM {course} c
            INNER JOIN {course_modules} cm
                ON cm.course = c.id
                AND cm.module = :moduleid
        ', array(
            'moduleid' => $this->_module->id
        ));

        foreach ($courses as $course) {
            $this->notify($course->id);
        }

        // TODO.
        //  - Remove capabilities.
    }

    /**
     * Given a module name and a course, generate a deprecation notification if
     * required.
     */
    public function notify($courseid) {
        if (!self::is_deprecated($this->_module->name)) {
            return true;
        }

        // Regenerate the deprecated notification.
        $task = new \local_kent\task\generate_deprecated_notification();
        $task->set_custom_data(array(
            'courseid' => $courseid
        ));
        \core\task\manager::queue_adhoc_task($task);

        return true;
    }

    /**
     * Returns true if a specific module is deprecated.
     */
    public static function is_deprecated($activity) {
        return (
            $activity == 'turnitintool' ||
            $activity == 'hotpot'
        );
    }
}
