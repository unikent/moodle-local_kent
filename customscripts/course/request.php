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

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/request_form.php');

// Where we came from. Used in a number of redirects.
$url = new moodle_url('/course/request.php');
$return = optional_param('return', null, PARAM_ALPHANUMEXT);
if ($return === 'management') {
    $url->param('return', $return);
    $returnurl = new moodle_url('/course/management.php', array('categoryid' => $CFG->defaultrequestcategory));
} else {
    $returnurl = new moodle_url('/course/index.php');
}

$PAGE->set_url($url);

// Check permissions.
require_login(null, false);
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

// Set up the form.
$data = course_request::prepare();

$requestform = new \local_kent\form\course_request($url, compact('editoroptions'));
$requestform->set_data($data);

$strtitle = get_string('courserequest');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

// Standard form processing if statement.
if ($requestform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $requestform->get_data()) {
    $data->shortname = \local_kent\Course::get_manual_shortname($data->type == 'true');
    $request = course_request::create($data);

    // And redirect back to the course listing.
    notice(get_string('courserequestsuccess'), $returnurl);
}

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);

$icon = \html_writer::tag('i', '', array(
    'class' => 'fa fa-info-circle'
));
$text = 'This form is for ancillary modules only. Academic modules are created automatically from SDS data and should not be requested here.';
echo $OUTPUT->notification("{$icon} {$text}", 'notifyinfo');

// Show the request form.
$requestform->display();

echo $OUTPUT->footer();

die;