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

$cwd = getcwd();

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get it? It's roleover because it's rolling over roles!
// Such funnies.
//
// This script pushes context roles into a given file, for later import.

list($options, $unrecognized) = cli_get_params(
    array('filename' => $cwd . '/roles.out'),
    array('f' => 'filename')
);

$syncset = array();
$rm = new \local_kent\manager\role();

// Push up all system roles.
$ctx = \context_system::instance();
$ras = $rm->get_local_enrolments_context($ctx);
foreach ($ras as $ra) {
    if ($rm->is_shared($ra->shortname)) {
        $syncset[$ra->id] = array(
            'shortname' => $ra->shortname,
            'username' => $ra->username,
            'contextlevel' => \CONTEXT_SYSTEM,
            'contextname' => ''
        );
    }
}
unset($ras);

// Push up all managed enrolments in all categories.
$categories = $DB->get_records('course_categories');
foreach ($categories as $category) {
    $ctx = \context_coursecat::instance($category->id);
    $ras = $rm->get_local_enrolments_context($ctx);
    foreach ($ras as $ra) {
        if ($rm->is_shared($ra->shortname)) {
            $syncset[$ra->id] = array(
                'shortname' => $ra->shortname,
                'username' => $ra->username,
                'contextlevel' => \CONTEXT_COURSECAT,
                'contextname' => $category->idnumber
            );
        }
    }
    unset($ras);
}
unset($categories);

file_put_contents($options['filename'], serialize($syncset));
