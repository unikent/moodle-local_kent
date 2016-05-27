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
require_once($CFG->dirroot . "/user/lib.php");

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

if (empty($options['moodle'])) {
    cli_error("You must specify a Moodle installation to grab data from.");
}

raise_memory_limit(MEMORY_HUGE);

// Set the user.
\local_kent\helpers::cli_set_user();

// Create MIM DB.
$mimdb = \local_kent\helpers::get_db($CFG->kent->environment, $options['moodle']);
if (!$mimdb) {
    cli_error("Cannot create MIM DB.");
}

// Grab a list of courses.
$sql = <<<SQL
    SELECT c.id, c.shortname, c.fullname, c.summary, c.format, cc.idnumber
    FROM {course} c
    INNER JOIN {course_categories} cc
        ON cc.id=c.category
    WHERE c.shortname LIKE :shortname AND cc.idnumber IS NOT NULL
SQL;
$courses = $mimdb->get_records_sql($sql, array('shortname' => 'DP%'));
foreach ($courses as $course) {
    $cat = $DB->get_record('course_categories', array('idnumber' => $course->idnumber));
    if (!$cat) {
        cli_writeln("{$course->shortname} does not have a valid category.");
        continue;
    }

    $obj = new \stdClass();
    $obj->category = $cat->id;
    $obj->shortname = $course->shortname;
    $obj->fullname = $course->fullname;
    $obj->summary = $course->summary;
    $obj->format = $course->format;
    $obj->visible = 0;

    cli_writeln("Creating course {$obj->shortname}...");
    if (!$options['dry']) {
        $localcourse = create_course($obj);
    } else {
        continue;
    }

    $ctx = \context_course::instance($localcourse->id);

    // Enrolments.
    $sql = <<<SQL
        SELECT ue.userid
        FROM {user_enrolments} ue
        INNER JOIN {enrol} e
            ON e.id=ue.enrolid
        WHERE e.courseid=:courseid AND e.enrol=:manual
SQL;
    $enrolments = $mimdb->get_records_sql($sql, array(
        'courseid' => $course->id,
        'manual' => 'manual'
    ));

    // Roles.
    $sql = <<<SQL
        SELECT ra.userid, r.shortname
        FROM {role_assignments} ra
        INNER JOIN {role} r
            ON r.id=ra.roleid
        INNER JOIN {context} ctx
            ON ctx.contextlevel=:ctxlevel AND ctx.instanceid=:instanceid
        WHERE ra.contextid=ctx.id
SQL;
    $roles = $mimdb->get_records_sql($sql, array(
        'instanceid' => $course->id,
        'ctxlevel' => \CONTEXT_COURSE
    ));

    // Map the roles.
    $localroles = $DB->get_records('role', null, '', 'shortname,id');

    // Grab user info.
    $users = array();
    foreach ($enrolments as $enrolment) {
        $users[$enrolment->userid] = new stdClass();
    }
    foreach ($roles as $role) {
        $users[$role->userid] = new stdClass();
    }

    list($sql, $params) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'id');
    $users = $mimdb->get_records_select('user', 'id '. $sql, $params);

    // Copy the users over.
    $usermap = array();
    foreach ($users as $user) {
        $localuser = $DB->get_record('user', array('username' => $user->username));
        if (!$localuser) {
            // Create user.
            $localuser = clone $user;
            unset($localuser->id);
            $localuser->id = user_create_user($localuser, false);
        }

        $usermap[$user->id] = $localuser->id;
    }

    // Copy the enrolments and roles across.
    $enrol = enrol_get_plugin('manual');

    // Find the instance.
    $instance = $DB->get_record('enrol', array(
        'courseid' => $localcourse->id,
        'enrol' => 'manual'
    ));

    if (!$instance) {
        $instanceid = $enrol->add_default_instance($localcourse);
        $instance = $DB->get_record('enrol', array(
            'id' => $instanceid
        ));
    }

    // Enrol the users.
    foreach ($enrolments as $enrolment) {
        $userid = $usermap[$enrolment->userid];
        $enrol->enrol_user($instance, $userid);
    }

    // Do the roles.
    foreach ($roles as $role) {
        $roleid = $localroles[$role->shortname]->id;
        $userid = $usermap[$role->userid];
        role_assign($roleid, $userid, $ctx->id);
    }
}
