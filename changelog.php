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

require_login();

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/local/kent/changelog.php');
$PAGE->set_title("Kent Moodle Changelog");

// Output header.
echo $OUTPUT->header();

$info = file_get_contents("$CFG->dirroot/changelog.md");
echo markdown_to_html($info);

// Output footer.
echo $OUTPUT->footer();