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

/**
 * Local stuff for Moodle
 *
 * @package    local_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/aspirelists/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;

/**
 * Kent's course external services.
 */
class course extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function provision_fresh_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(
                PARAM_INT,
                'The course ID',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Search a list of modules.
     *
     * @param $modulecode
     * @return array [string]
     * @throws \invalid_parameter_exception
     */
    public static function provision_fresh($courseid) {
        global $DB;

        $params = self::validate_parameters(self::provision_fresh_parameters(), array(
            'courseid' => $courseid
        ));
        $courseid = $params['courseid'];

        $course = $DB->get_record('course', array(
            'id' => $courseid
        ), '*', MUST_EXIST);

        require_capability('moodle/course:update', \context_course::instance($courseid));

        // Add the forum.
        forum_get_course_forum($course->id, 'news');

        // Setup an aspire lists instance.
        $module = $DB->get_record('modules', array(
            'name' => 'aspirelists'
        ));

        // Create the instance.
        $instance = aspirelists_add_instance((object)array(
            'course' => $course->id,
            'name' => 'Reading list',
            'intro' => '',
            'introformat' => 1,
            'category' => 'all',
            'timemodified' => time()
        ), null);

        // Find the first course section.
        $section = $DB->get_record_sql("SELECT id, sequence FROM {course_sections} WHERE course=:cid AND section=0", array(
            'cid' => $course->id
        ));

        // Create a module container.
        $cm = new \stdClass();
        $cm->course     = $course->id;
        $cm->module     = $module->id;
        $cm->instance   = $instance;
        $cm->section    = $section->id;
        $cm->visible    = 1;

        // Create the module.
        $cm = add_course_module($cm);
        course_add_cm_to_section($course->id, $cm, 0);

        return array('success' => true);
    }

    /**
     * Returns description of provision_fresh() result value.
     *
     * @return external_description
     */
    public static function provision_fresh_returns() {
        return new external_single_structure(array(
            'success' => new external_value(PARAM_BOOL, 'Success or failue (true/false).')
        ));
    }
}
