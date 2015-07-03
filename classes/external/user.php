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

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_function_parameters;

/**
 * Kent's module external services.
 */
class user extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_my_info_data_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function get_my_info_data_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the current user's preferences.
     *
     * @return array[string]
     */
    public static function get_my_info_data() {
        $prefs = \local_kent\User::get_all_infodata();

        $formatted = array();
        foreach ($prefs as $k => $v) {
            $formatted[] = array(
                'name' => $k,
                'value' => $v
            );
        }

        return $formatted;
    }

    /**
     * Returns description of get_my_info_data() result value.
     *
     * @return external_description
     */
    public static function get_my_info_data_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name' => new external_value(PARAM_TEXT, 'Name of preference'),
                    'value' => new external_value(PARAM_RAW, 'Value of preference')
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_my_prefs_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function get_my_prefs_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns the current user's preferences.
     *
     * @return array[string]
     */
    public static function get_my_prefs() {
        global $USER;

        check_user_preferences_loaded($USER);

        $formatted = array();
        foreach ($USER->preference as $k => $v) {
            $formatted[] = array(
                'name' => $k,
                'value' => $v
            );
        }

        return $formatted;
    }

    /**
     * Returns description of get_my_prefs() result value.
     *
     * @return external_description
     */
    public static function get_my_prefs_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name' => new external_value(PARAM_TEXT, 'Name of preference'),
                    'value' => new external_value(PARAM_RAW, 'Value of preference')
                )
            )
        );
    }
}