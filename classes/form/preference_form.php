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
 * Local stuff for Moodle Kent
 *
 * @package    local_kent
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class preference_form extends \moodleform
{
    /**
     * Form definition
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'themesettings', 'Theme Settings');
        $mform->addElement('checkbox', 'theme_fullscreen', 'Full Screen');
        $mform->addElement('checkbox', 'theme_contrast', 'Contrast Mode');
        $mform->setExpanded('themesettings');

        $this->add_action_buttons(true);
    }

    /**
     * Set default.
     */
    public function set_field_default($field, $val = 0) {
        $mform =& $this->_form;
        $mform->setDefault($field, $val);
    }
}
