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

require_login();

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/local/kent/optin.php');
$PAGE->set_title("Kent Beta Preferences");

// Create form.
$form = new \local_kent\form\optin_form();

// Did we cancel?
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/kent/optin.php');
}

// Did we submit?
if ($data = $form->get_data()) {
    $arr = (array)$data;
    unset($arr['submitbutton']);

    $prefs = array();
    foreach ($arr as $k => $v) {
        $prefs[] = $k . "=" . ($v ? '1' : '0');
    }

    set_user_preference("betaprefs", implode(',', $prefs));
} else {
    // Set defaults.
    $prefs = \local_kent\User::get_beta_preferences();
    foreach ($prefs as $k => $v) {
        $form->set_field_efault($k, $v);
    }
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Beta Preferences");

// Warning.
echo \html_writer::tag("p", "Warning! These options enable features that may or may not be very unstable, we do not recommend you enable any of them.<br />But, it could be fun...");

// Show form.
$form->display();

// Output footer.
echo $OUTPUT->footer();
