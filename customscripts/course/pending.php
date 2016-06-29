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

require_login();
require_capability('moodle/site:approvecourse', context_system::instance());

$approve = optional_param('approve', 0, PARAM_INT);

if (!empty($approve) && confirm_sesskey()) {
    $course = $DB->get_record('course_request', array('id' => $approve));
    if ($DB->record_exists('course', array('shortname' => $course->shortname))) {
        $rollover = null;

        $code = substr($course->shortname, 1, 1);
        if ($code == 'P') {
            $rollover = true;
        }

        if ($code == 'O') {
            $rollover = false;
        }

        $course->shortname = \local_kent\Course::get_manual_shortname($rollover);
        $DB->update_record('course_request', $course);
    }
}
