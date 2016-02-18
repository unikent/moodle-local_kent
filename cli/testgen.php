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
 * @package    tool_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

cli_writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
cli_writeln('Welcome to the school generator!');
cli_writeln('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
cli_writeln('');

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

cli_writeln('Creating courses...');
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

    $generator->create_course($courserecord, array('createsections' => true));
    $id += 100;
}
