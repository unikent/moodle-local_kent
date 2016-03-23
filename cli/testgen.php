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
 * Generates a test school.
 *
 * @package    local_kent
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/user/lib.php');

if ($CFG->kent->environment !== 'dev') {
    die("Can only be run in dev mode.");
}

cli_writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
cli_writeln('Welcome to the test env generator!');
cli_writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
cli_writeln('');

cli_writeln('Running user generator...');

// First provision users.
$users = array();
foreach (array('skylark', 'jake') as $username) {
    $user = new \stdClass();
    $user->username = $username;
    $user->email = $username . "@kent.ac.uk";
    $user->auth = "kentsaml";
    $user->password = "not cached";
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->firstname = $username;
    $user->lastname = $username;

    $users[$username] = user_create_user($user, false);
}

cli_writeln('Running school generator...');

$category = $DB->get_record('course_categories', array('name' => 'School of Witchcraft and Wizardry'));
if (!$category) {
    cli_writeln('Creating category...');
    $category = \coursecat::create(array(
        'name' => 'School of Witchcraft and Wizardry',
        'description' => 'A school like no other!',
        'descriptionformat' => 1,
        'parent' => 0,
        'sortorder' => 520000,
        'coursecount' => 0,
        'visible' => 1,
        'visibleold' => 1,
        'timemodified' => 0,
        'depth' => 1,
        'path' => '/2',
        'theme' => ''
    ));
} else {
    cli_writeln('Using existing category.');
}

$courses = array(
    'Astronomy',
    'Charms',
    'Dark Arts',
    'Defence Against the Dark Arts',
    'Flying',
    'Herbology',
    'History of Magic',
    'Muggle Studies',
    'Potions',
    'Transfiguration',
    'Alchemy',
    'Apparition',
    'Arithmancy',
    'Care of Magical Creatures',
    'Divination',
    'Study of Ancient Runes',
    'Extra-curricular subjects',
    'Ancient Studies',
    'Art',
    'Frog Choir',
    'Ghoul Studies',
    'Magical Theory',
    'Muggle Art',
    'Music',
    'Muggle Music',
    'Orchestra',
    'Xylomancy'
);

$enrol = enrol_get_plugin('manual');
$role = $DB->get_record('role', array('shortname' => 'editingteacher'));

$generator = \phpunit_util::get_data_generator();
$id = 1000;
foreach ($courses as $course) {
    if ($DB->record_exists('course', array('fullname' => $course))) {
        continue;
    }

    $courserecord = array(
        'shortname' => "WZ{$id}",
        'fullname' => $course,
        'numsections' => 10,
        'startdate' => usergetmidnight(time()),
        'category' => $category->id
    );

    $course = $generator->create_course($courserecord, array('createsections' => true));

    // Enrolments.
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'));
    foreach ($users as $username => $userid) {
        $enrol->enrol_user($instance, $userid, $role->id);
    }

    $id += 100;
}
