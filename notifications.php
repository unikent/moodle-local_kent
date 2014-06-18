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

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/kent/notifications.php');
$PAGE->set_title("Notifications");

$PAGE->navbar->add("Notifications");

$PAGE->requires->css('/local/kent/styles/styles.css');

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Your Notifications");

// Do we have an action?
$action = optional_param('action', null, PARAM_ALPHA);
if ($action !== null) {
    require_sesskey();

    $id = required_param('id', PARAM_INT);

    $DB->set_field('kent_notifications', 'seen', 1, array(
        'id' => $id,
        'userid' => $USER->id
    ));
}

// Grab and display all notifications.
$notifications = \local_kent\Notification::get($USER->id, 0);
foreach ($notifications as $notification) {
    echo $OUTPUT->box_start('generalbox notification');

    echo $OUTPUT->heading($notification->shortdesc, 3);
    if (!empty($notification->longdesc)) {
        echo html_writer::tag('p', $notification->longdesc);
    }

    echo $OUTPUT->single_button('/local/kent/notifications.php?id=' . $notification->id . '&action=read', 'Mark as read');

    echo $OUTPUT->box_end();
    echo html_writer::empty_tag('br');
}

if (count($notifications) <= 0) {
    echo html_writer::tag('p', 'You have no notifications!');
}

// Output footer.
echo $OUTPUT->footer();
