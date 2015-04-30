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
 * Moodle provisioner.
 * 
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

raise_memory_limit(MEMORY_HUGE);

$username = exec('logname');
$user = $DB->get_record('user', array(
    'username' => $username
));

if (!$user) {
	$user = get_admin();
}

\core\session\manager::set_user($user);

$csv = array_map('str_getcsv', file('modules.csv'));
foreach ($csv as $course) {
	list($id, $shortname, $fullname) = $course;

	$shareddata = $DB->get_record_sql('SELECT c.id, c.fullname, c.summary, c.format, cc.idnumber
		FROM moodle_2014.mdl_course c
		INNER JOIN moodle_2014.mdl_course_categories cc
			ON cc.id=c.category
		WHERE c.id=:id
	', array(
		'id' => $id
	));

	if (!$shareddata) {
		echo "$id is not valid (doesn't exist).\n";
		continue;
	}

	$cat = $DB->get_record('course_categories', array('idnumber' => $shareddata->idnumber));
	if (!$cat) {
		echo "$id is not valid (category).\n";
		continue;
	}

	$newshortname = \local_kent\Course::get_manual_shortname();

    $obj = new \stdClass();
    $obj->category = $cat->id;
    $obj->shortname = $newshortname;
    $obj->fullname = $shareddata->fullname;
    $obj->summary = $shareddata->summary;
    $obj->format = $shareddata->format;
    $obj->visible = 0;

    $course = create_course($obj);
    $ctx = \course_context::instance($course->id);
    
    $rolloverlink = new \moodle_url('/local/kent/courseclassify.php', array(
    	'id' => $course->id,
    	'classify' => 1
    ));
    
    $norolloverlink = new \moodle_url('/local/kent/courseclassify.php', array(
    	'id' => $course->id,
    	'classify' => 0
    ));

    // Add message.
    $kc = new \local_kent\Course($course->id);
    $kc->add_notification($ctx->id, 'manual_classify', "This manually created module has not been classified. Do you want this module to rollover next year? <a href=\"$rolloverlink\" class=\"alert-link\">Yes</a> <a href=\"$norolloverlink\" class=\"alert-link\">No</a>.", 'warning', true, false);

    echo "$shortname, $newshortname\n";
}