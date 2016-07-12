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
require_once($CFG->libdir . '/enrollib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'enable' => 0,
        'disable' => 0
    )
);

if ((!isset($options['enable']) && !isset($options['disable'])) || (isset($options['enable']) && isset($options['disable']))) {
    die("Please run with either --enable or --disable.");
}

$sql = <<<SQL
    SELECT c.* FROM {course} c
    WHERE c.id > 1
SQL;

$enrol = enrol_get_plugin('guest');
$courses = $DB->get_records_sql($sql);
foreach ($courses as $course) {
	$instances = $DB->get_records('enrol', array(
        'enrol' => 'guest',
        'courseid' => $course->id
    ));

	if (count($instances) > 1) {
    	foreach ($instances as $instance) {
            $enrol->delete_instance($instance);
        }

        $instance = null;
	} else {
		$instance = reset($instances);
	}

	if (!$instance) {
		$instance = $enrol->add_default_instance($course);
		$instance = $DB->get_record('enrol', array(
			'id' => $instance
		));
	}

    if (isset($options['enable'])) {
	       $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);
    } else {
	       $enrol->update_status($instance, ENROL_INSTANCE_DISABLED);
    }
}
