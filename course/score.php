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

require_login($courseid);
require_capability('moodle/course:update', $PAGE->context);

$PAGE->set_url('/local/kent/course/score.php');
$PAGE->set_title("Course checker");

// Output header.
echo $OUTPUT->header();

echo $OUTPUT->notification('<i class="fa fa-warning"></i> Please note: this tool is still under development and may not yet function properly. You are advised to backup your course before auto-fixing. The rules are also still being heavily tweaked based on user feedback.');

echo $OUTPUT->heading("Course checker");

$checker = new \local_kent\course_checker($courseid);
$checker->run();

$scores = $checker->get_score();
$width = ((float)$scores['score'] / (float)$scores['max']) * 100.0;

$color = 'danger';
if ($width > 50) {
    $color = 'warning';
}

if ($width > 75) {
    $color = 'success';
}

echo <<<HTML5
    <div class="progress">
        <div class="progress-bar progress-bar-{$color}" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="{$scores['max']}" style="width: {$width}%;">
            {$scores['score']} / {$scores['max']}
        </div>
    </div>
HTML5;

$checker->render_results();

// Output footer.
echo $OUTPUT->footer();
