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

namespace local_kent\form;

require_once($CFG->dirroot . '/course/request_form.php');

class course_request extends \course_request_form
{
    public function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;

        if ($pending = $DB->get_records('course_request', array('requester' => $USER->id))) {
            $mform->addElement('header', 'pendinglist', get_string('coursespending'));
            $list = array();
            foreach ($pending as $cp) {
                $list[] = format_string($cp->fullname);
            }
            $list = implode(', ', $list);
            $mform->addElement('static', 'pendingcourses', get_string('courses'), $list);
        }

        $mform->addElement('header','coursedetails', get_string('courserequestdetails'));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('select', 'type', 'Rollover Settings', array(
        	'true' => 'Auto rollover',
        	'false' => 'Don\'t auto rollover'
        ));
        $mform->setDefault('type', 'O');

        if (!empty($CFG->requestcategoryselection)) {
            $displaylist = \coursecat::make_categories_list();
            $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
            $mform->setDefault('category', $CFG->defaultrequestcategory);
            $mform->addHelpButton('category', 'coursecategory');
        }

        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, \course_request::summary_editor_options());
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header','requestreason', get_string('courserequestreason'));

        $mform->addElement('textarea', 'reason', get_string('courserequestsupport'), array('rows'=>'15', 'cols'=>'50'));
        $mform->addRule('reason', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('reason', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('requestcourse'));
    }
}