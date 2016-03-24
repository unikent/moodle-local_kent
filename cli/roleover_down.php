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
// This script pulls context roles out from a given file.

list($options, $unrecognized) = cli_get_params(
    array('filename' => $cwd . '/roles.out'),
    array('f' => 'filename')
);

$roledata = file_get_contents($options['filename']);
$roledata = unserialize($roledata);

$roles = $DB->get_records('role', null, '', 'shortname, id');
$coursecats = $DB->get_records_select('course_categories', 'idnumber <> \'\'', null, '', 'idnumber, id');

$rm = new \local_kent\manager\role();
foreach ($roledata as $ra) {
    $ra = (object)$ra;
    if (!isset($roles[$ra->shortname])) {
        continue;
    }

    $role = $roles[$ra->shortname];

    $userid = $rm->get_userid($ra->username);
    if (!$userid) {
        continue;
    }

    $context = null;
    switch ($ra->contextlevel) {
        case \CONTEXT_SYSTEM:
            $context = \context_system::instance();
        break;

        case \CONTEXT_COURSECAT:
            if (isset($coursecats[$ra->contextname])) {
                $coursecat = $coursecats[$ra->contextname];
                $context = \context_coursecat::instance($coursecat->id);
            }
        break;
    }

    if (!$context) {
        continue;
    }

    // Ensure the enrolment exists!
    if (!user_has_role_assignment($userid, $role->id, $context->id)) {
        role_assign($role->id, $userid, $context->id);
    }
}
