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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$id = required_param("id", PARAM_INT);
$context = \context_course::instance($id);

$classify = required_param("classify", PARAM_INT);

require_login();
require_capability('moodle/course:update', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/kent/courseclassify.php');
$PAGE->set_title("Course Classification");

// Classify the course.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$course->shortname = \local_kent\Course::get_manual_shortname($classify == 1);
$DB->update_record('course', $course);

// Remove the notification.
$kc = new \local_kent\Course($id);
$notification = $kc->get_notification($context->id, 'manual_classify');
if ($notification) {
    $notification->delete();
}

// Redirect to the course.
redirect(new \moodle_url('/course/view.php', array(
	'id' => $id
)));
