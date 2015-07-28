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

namespace local_kent\task;

/**
 * Course contact preloader.
 */
class course_contact_preload extends \core\task\scheduled_task
{
    public function get_name() {
        return "Course contact preload";
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/coursecatlib.php");

        $keys = $DB->get_fieldset_select('course', 'id', '');

        // Delete ALL the cache.
        $cache = \cache::make('core', 'coursecontacts');
        $cache->delete_many($keys);
        $cache->set('contacts', $CFG->coursecontact);

        // Preload contacts.
        $courses = array();
        foreach ($keys as $id) {
            $courses[$id] = new \stdClass();
        }

        \coursecat::preload_course_contacts($courses);

        return true;
    }
}
