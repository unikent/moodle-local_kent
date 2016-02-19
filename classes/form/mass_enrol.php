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
 * Defines the local_kent forms.
 *
 * @package    local_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * The local_kent question post form.
 *
 * @package    local_kent
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mass_enrol extends \moodleform
{
    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Add student select box.
        $mform->addElement('select', 'users', 'Users', $DB->get_records_menu('user', array(), '', 'id,username'), array(
            'class' => 'chosen',
            'multiple' => 'multiple'
        ));
        $mform->addRule('users', null, 'required', null, 'client');

        // Add student select box.
        $courses = $DB->get_records_menu('course', array(), '', 'id,CONCAT(shortname, \': \', fullname)');
        $mform->addElement('select', 'courses', 'Courses', $courses, array(
            'class' => 'chosen',
            'multiple' => 'multiple'
        ));
        $mform->addRule('courses', null, 'required', null, 'client');
        unset($courses);

        // Add role select box.
        $course = get_site();
        $roles = get_assignable_roles(\context_course::instance($course->id));
        $mform->addElement('select', 'roleid', 'Role', $roles);
        $mform->addRule('roleid', null, 'required', null, 'client');

        $this->add_action_buttons(true, 'Enrol');
    }
}
