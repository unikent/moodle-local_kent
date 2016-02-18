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

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/kent/massenrol.php');
$PAGE->set_title("Mass-Enrol tool");

require_capability('moodle/site:config', $PAGE->context);

// Form setup.
$form = new \local_kent\form\mass_enrol();
if ($form->is_cancelled()) {
    redirect($PAGE->url);
}

if ($data = $form->get_data()) {
    $users = $data->users;
    $courses = $data->courses;

    $plugin = enrol_get_plugin('manual');
    foreach ($courses as $courseid) {
        // Make sure there is an enrol instance.
        $course = $DB->get_record('course', array(
            'id' => $courseid
        ));
        $plugin->add_default_instance($course);

        // Grab the instance.
        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }

        // Enrol each student.
        foreach ($users as $userid) {
            $plugin->enrol_user($instance, $userid, $data->roleid);
        }
    }

    redirect($PAGE->url, "Successfully enrolled " . count($users) . " users on " . count($courses) . " courses.");
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Mass-Enrol tool");

echo \html_writer::tag("p", "This tool will mass-enrol users on courses, in a specified role.");

$form->display();

// Output footer.
echo $OUTPUT->footer();
