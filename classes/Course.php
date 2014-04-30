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
 * Course stuff
 */
class Course
{
    /**
     * Run the shouter cron.
     */
    public static function cron() {
        global $DB;

        if (!\local_hipchat\HipChat::available()) {
            return false;
        }

        // What was the last time we shouted about in the config logs table?
        $lasttime = $DB->get_field('local_kent_trackers', 'value', array(
            'name' => 'course_tracker'
        ));

        // Update the time stamp.
        $DB->set_field('local_kent_trackers', 'value', time(), array(
            'name' => 'course_tracker'
        ));

        // Grab all entries since then, not made by admin.
        $sql = <<<SQL
            SELECT COUNT(c.id)
            FROM {course} c
            WHERE c.timecreated > :time
SQL;
        $entries = $DB->count_records_sql($sql, array(
            'time' => $lasttime
        ));

        if ($entries > 0) {
            $lastdate = strftime("%d-%m-%Y %H:%M", $lasttime);
            $coursestr = $entries > 1 ? "courses have" : "course has";
            \local_hipchat\Message::send("{$entries} {$coursestr} been created since {$lastdate}.");
        }
    }
}
