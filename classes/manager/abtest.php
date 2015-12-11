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
 * A/B testing manager.
 */
class abtest
{
	/**
	 * Initiate an A/B test for 10% of users.
	 */
	public static function start_with_10_percent($pref, $val = 1) {
		static::start_with(10, $pref, $val);
	}

	/**
	 * Initiate an A/B test for 25% of users.
	 */
	public static function start_with_25_percent($pref, $val = 1) {
		static::start_with(25, $pref, $val);
	}

	/**
	 * Initiate an A/B test for 50% of users.
	 */
	public static function start_with_50_percent($pref, $val = 1) {
		static::start_with(50, $pref, $val);
	}

	/**
	 * Initiate an A/B test for 75% of users.
	 */
	public static function start_with_75_percent($pref, $val = 1) {
		static::start_with(25, $pref, $val);
	}

	/**
	 * Initiate an A/B test for {$perc}% of users.
	 */
	public static function start_with($perc, $pref, $val = 1) {
		global $DB;

        if (strpos($pref, 'kent_') !== 0) {
            $pref = "kent_{$pref}";
        }

        $current = $DB->get_records('user_preferences', array('name' => $pref));

        $intersect = $DB->get_fieldset_select('user_preferences', 'userid', 'name = :name', array('name' => $pref));
        $users = $DB->get_fieldset_select('user', 'id');

        $existing = ((float)count($intersect) / (float)count($users)) * 100.0;
        $perc -= $existing;
        if ($perc <= 0) {
        	return 0;
        }

        $records = array();
        $users = array_intersect($users, $intersect);
        shuffle($users);

        $perc = (float)$perc / 100.0;
        for ($i = 0; $i < (count($users) * $perc); $i++) {
        	$records[] = array(
        		'userid' => $users[$i],
        		'name' => $pref,
        		'value' => $val
        	);
        }

        $DB->insert_records('user_preferences', $records);
	}

	/**
	 * Finish an A/B test (deletes all prefs).
	 */
	public static function finish_test($pref) {
		global $DB;

        if (strpos($pref, 'kent_') !== 0) {
            $pref = "kent_{$pref}";
        }

        $DB->delete_records('user_preferences', array('name' => $pref));
	}
}