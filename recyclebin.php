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

$courseid = required_param('course', PARAM_INT);
$cmid = optional_param('cm', null, PARAM_INT);
$coursecontext = \context_course::instance($courseid, MUST_EXIST);

require_login($courseid);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_url('/local/kent/recyclebin.php', array(
    'course' => $courseid
));
$PAGE->set_title('Recycle Bin');

$course = new \local_kent\Course($courseid);

if (isset($cmid)) {
    require_sesskey();

    // Restore it.
    $course->restore_from_recycle_bin($cmid);
    redirect($PAGE->url);
}

$items = $course->get_recycle_bin_items();
if (empty($items)) {
    redirect(new \moodle_url('/course/view.php', array(
        'id' => $courseid
    )));
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading('Recycle Bin');

echo '<ul>';

foreach ($items as $cm) {
    $icon = '';
    if (!empty($cm->icon)) {
        // Each url has an icon in 2.0.
        $icon .= '<img src="' . $OUTPUT->pix_url($cm->icon) . '" class="activityicon" alt="' . get_string('modulename', $cm->modname) . '" /> ';
    } else {
        $icon .= '<img src="' . $OUTPUT->pix_url('icon', $cm->modname) . '" class="icon" alt="' . get_string('modulename', $cm->modname) . '" /> ';
    }

    $restore = new \moodle_url('/local/kent/recyclebin.php', array(
        'course' => $courseid,
        'cm' => $cm->id,
        'sesskey' => sesskey()
    ));
    $restore = \html_writer::link($restore, '<i class="fa fa-history"></i>', array(
        'alt' => 'Restore',
        'title' => 'Restore'
    ));

    echo "<li>{$icon}{$cm->name}  {$restore}</li>";
}

echo '</ul>';

// Output footer.
echo $OUTPUT->footer();