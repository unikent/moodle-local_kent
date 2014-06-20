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

die("You don't want to do that...\nTrust me, I'm a computer.");

/**
 * This file completely obliterates every course in Moodle.
 * You probably dont want to do that.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'dry' => true
    )
);

// Dry is true by default.
if ($options['dry']) {
    echo "Running in DRY mode...\n";
} else {
    echo "Running in LIVE mode...\n";
}

\local_hipchat\Message::send("Err.. you should probably know that someone is destroying Moodle...", "red");

// Destroy everything.
cli_heading("Destroying Build");

// Delete all courses.
$count = $DB->count_records('course');

$rs = $DB->get_recordset('course');
foreach ($rs as $course) {
    if ($course->id <= 1) {
        continue;
    }

    echo "Deleting {$course->id}...\n";
    if (!$options['dry']) {
        delete_course($course);
    }
}
$rs->close();

$count -= $DB->count_records('course');

echo "Deleted {$count} courses.\n";
\local_hipchat\Message::send("Too late... {$count} courses just got wiped.", "red");