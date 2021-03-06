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

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'dry' => false,
        'category' => ''
    )
);

if (empty($options['category'])) {
    cli_error("You must specify a category!");
}

// Dry is true by default.
if ($options['dry']) {
    cli_writeln("Running in DRY mode...");
} else {
    cli_writeln("Running in LIVE mode...");
}

function purge_cat($category, $options) {
    global $DB;

    // Destroy everything.
    cli_heading("Destroying {$category}...");

    // Delete all courses.
    $count = $DB->count_records('course');

    $rs = $DB->get_recordset_sql("SELECT c.* FROM mdl_course c
        INNER JOIN mdl_course_categories cc
          ON cc.id=c.category
        WHERE cc.path LIKE :p1
          OR cc.path LIKE :p2
    ", array(
        'p1' => "%/{$category}/%",
        'p2' => "%/{$category}"
    ));

    foreach ($rs as $course) {
        if ($course->id <= 1) {
            continue;
        }

        cli_writeln("Deleting {$course->id}...");
        if (!$options['dry']) {
            delete_course($course);
        }
    }
    $rs->close();

    $count -= $DB->count_records('course');

    cli_writeln("Deleted {$count} courses.");
}

$categories = $options['category'];
$categories = explode(',', $categories);
foreach ($categories as $category) {
    purge_cat($category, $options);
}
