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
$PAGE->set_url('/local/kent/index.php');
$PAGE->set_title("Kent Moodle");

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading('Kent Moodle');

echo <<<HTML5
<p>Here you can change your <a href="optin.php">beta preferences</a> or view the <a href="changelog.php">Kent Moodle changelog</a>.</p>
HTML5;

// Output footer.
echo $OUTPUT->footer();