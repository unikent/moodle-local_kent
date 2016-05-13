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

/**
 * Moodle manual module provisioner.
 *
 * @package    local_kent
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'moodle' => LIVE_MOODLE,
        'dry' => false
    ),
    array(
        'm' => 'moodle',
        'd' => 'dry'
    )
);

raise_memory_limit(MEMORY_HUGE);

// Set the user.
\local_kent\helpers::cli_set_user();

// Grab a list of courses.
$moodle = $options['moodle'];
$sql = <<<SQL
    SELECT c.id, c.fullname, c.summary, c.format, cc.idnumber
    FROM moodle_{$moodle}.mdl_course c
    INNER JOIN moodle_2014.mdl_course_categories cc
        ON cc.id=c.category
    WHERE c.shortname LIKE "DP%" AND cc.idnumber <> NULL
SQL;
$courses = $DB->get_records_sql($sql);
foreach ($courses as $course) {
    $cat = $DB->get_record('course_categories', array('idnumber' => $course->idnumber));
    if (!$cat) {
        echo "$id is not valid (category).\n";
        continue;
    }

    $obj = new \stdClass();
    $obj->category = $cat->id;
    $obj->shortname = $course->shortname;
    $obj->fullname = $course->fullname;
    $obj->summary = $course->summary;
    $obj->format = $course->format;
    $obj->visible = 0;

    cli_writeln("Creating course {$obj->shortname }...");
    if (!$options['dry']) {
        create_course($obj);
    }
}
