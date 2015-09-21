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

require_once("{$CFG->libdir}/externallib.php");

use external_api;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_function_parameters;

/**
 * Kent's corner external services.
 */
class corner extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function user_enrol_detail_parameters() {
        return new external_function_parameters(array(
            'username' => new external_value(
                PARAM_USERNAME,
                'The username to lookup',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function user_enrol_detail_is_allowed_from_ajax() {
        return false;
    }

    /**
     * Returns the current user's preferences.
     *
     * @param $username
     * @return array[string]
     */
    public static function user_enrol_detail($username) {
        global $DB;

        // Validate the username.
        $params = self::validate_parameters(self::user_enrol_detail_parameters(), array(
            'username' => $username
        ));
        $username = $params['username'];

        // Find the user in connect.
        $user = \local_connect\user::get_by('login', $username);
        if (!$user) {
            return array();
        }

        // Find all enrolments in connect.
        $enrolments = \local_connect\enrolment::get_by('userid', $user->id, true);
        if (empty($enrolments)) {
            return array();
        }

        $ret = array();
        foreach ($enrolments as $enrolment) {
            $course = $enrolment->course;

            $ret[$enrolment->id] = array(
                'name' => $course->shortname,
                'url' => $course->get_moodle_url(),
                'active' => $course->is_in_moodle(),
                'visible' => $course->is_in_moodle() ? $course->course->visible : false,
                'pending' => !$enrolment->is_in_moodle(),
                'rolecorrect' => $enrolment->is_in_moodle_precise()
            );
        }

        return $ret;
    }

    /**
     * Returns description of user_enrol_detail() result value.
     *
     * @return external_multiple_structure
     */
    public static function user_enrol_detail_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name' => new external_value(PARAM_TEXT, 'Name of the module'),
                    'url' => new external_value(PARAM_TEXT, 'URL of the module in Moodle'),
                    'active' => new external_value(PARAM_BOOL, 'Whether or not the module is currently in Moodle'),
                    'visible' => new external_value(PARAM_BOOL, 'Whether or not the module is currently hidden'),
                    'pending' => new external_value(PARAM_BOOL, 'Whether or not the enrolment is active'),
                    'rolecorrect' => new external_value(PARAM_BOOL, 'Whether or not the enrolment has the correct role')
                )
            )
        );
    }
}