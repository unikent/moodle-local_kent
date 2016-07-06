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

require_once(dirname(__FILE__) . '/../../../config.php');

$courseid = required_param('id', PARAM_INT);
$preview = optional_param('preview', false, PARAM_BOOL);

require_login($courseid);
require_capability('moodle/course:update', $PAGE->context);

if (!$preview) {
    require_sesskey();

    $checker = new \local_kent\course_checker($courseid);
    $checker->run();
    $checker->dispatch_fixes(false);
    redirect(new \moodle_url('/local/kent/course/score.php', array('id' => $courseid)));
}

$PAGE->set_url('/local/kent/course/fix.php', array('id' => $courseid));
$PAGE->set_title("Course fixer");

// Output header.
echo $OUTPUT->header();

echo $OUTPUT->notification('<i class="fa fa-warning"></i> Please note: this tool is still under development and may not yet function properly. You are advised to backup your course before auto-fixing. The rules are also still being heavily tweaked based on user feedback, this tool is meant purely for assistance in module design alongside the good Moodle guide.');

echo $OUTPUT->heading("Course fixer");

$checker = new \local_kent\course_checker($courseid);
$checker->run();
$checker->dispatch_fixes($preview);

if ($preview) {
    echo \html_writer::link(new \moodle_url($PAGE->url, array(
        'preview' => false,
        'sesskey' => sesskey()
    )), 'Save changes', array(
        'class' => 'btn btn-primary'
    ));
}

// Output footer.
echo $OUTPUT->footer();
