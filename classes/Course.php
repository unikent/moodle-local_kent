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

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course helpers
 */
class Course
{
    private $_courseid;

    public function __construct($courseid) {
        $this->_courseid = $courseid;
    }

    /**
     * Helper for creating a manual module code.
     * Rollover should be true or false, null means we don't know.
     */
    public static function get_manual_shortname($rollover = null) {
        global $DB;

        $rollover = $rollover === null ? 'X' : ($rollover == true ? 'P' : 'O');
        $shortname = "D{$rollover}";

        $like = $DB->sql_like('shortname', ':shortname');
        $like2 = $DB->sql_like('shortname', ':shortname2');
        $sql = <<<SQL
            SELECT shortname
            FROM {course}
            WHERE {$like}

            UNION

            SELECT shortname
            FROM {course_request}
            WHERE {$like2}
SQL;

        $courses = $DB->get_records_sql($sql, array(
            'shortname' => $shortname . "%",
            'shortname2' => $shortname . "%"
        ));

        $num = 1000;
        foreach ($courses as $course) {
            $pos = (int)substr($course->shortname, 2);
            if ($pos >= $num) {
                $num = $pos + 25;
            }
        }

        return "{$shortname}{$num}";
    }

    /**
     * Is this a manual course?
     */
    public function is_manual() {
        global $DB;

        $shortname = $DB->get_field('course', 'shortname', array(
            'id' => $this->_courseid
        ));

        $indicator = substr($shortname, 0, 2);

        return in_array($indicator, array('DX', 'DP', 'DO'));
    }
}
