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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
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
        global $USER;

        $mform =& $this->_form;

        $mform->addElement('header', 'uxsettings', 'Interface Settings');
        $mform->addElement('checkbox', 'kent_kco_notifications', 'Show notifications in \'My Modules\' block');
        $mform->addElement('checkbox', 'kent_theme_menu_hide_text', 'Hide menu icon\'s text');
        $mform->setExpanded('uxsettings');

        $mform->addElement('header', 'accesssettings', 'Accessibility Settings');
        $mform->addElement('checkbox', 'kent_theme_contrast', 'Contrast mode');
        $mform->addElement('select', 'kent_theme_zoom', 'Zoom level', array(
            '1' => 'Standard',
            '2' => 'High',
            '3' => 'Highest'
        ));
        $mform->setExpanded('accesssettings');

        $mform->addElement('header', 'betasettings', 'Beta Programs');
        $mform->addElement('html', '<div class="alert alert-warning"><i class="fa fa-warning"></i> These options are not well tested and may not work properly!</div>');
        $mform->addElement('checkbox', 'kent_beta', 'General', 'Receive any bleeding-edge features as they become available.');
        $mform->addElement('checkbox', 'kent_theme_flexbox', 'Flexbox theme', 'Switches the theme\'s grid system to flexbox.');

        $this->add_action_buttons(true);
    }

    /**
     * Set default.
     * @param $field
     * @param int $val
     */
    public function set_field_default($field, $val = 0) {
        $mform =& $this->_form;
        $mform->setDefault($field, $val);
    }
}
