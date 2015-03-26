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

namespace local_kent\task;

/**
 * Sync a specified user's enrolments.
 */
class role_migrate extends \core\task\adhoc_task
{
    public function get_component() {
        return 'local_kent';
    }

    public function execute() {
        global $DB, $SHAREDB;

        $rm = new \local_kent\RoleManager();
        $syncset = array();

        // Grab a list of course categories.
        $categories = $DB->get_records('course_categories');

        // Push up all managed enrolments in all categories.
        foreach ($categories as $category) {
            $ctx = \context_coursecat::instance($category->id);
            $ras = $rm->get_local_enrolments_context($ctx);
            foreach ($ras as $ra) {
                if ($rm->is_managed($ra->shortname)) {
                    $syncset[$ra->id] = array(
                        'shortname' => $ra->shortname,
                        'username' => $ra->username,
                        'contextlevel' => \CONTEXT_COURSECAT,
                        'contextname' => $category->name
                    );
                }
            }
        }

        $ctx = \context_system::instance();
        $ras = $rm->get_local_enrolments_context($ctx);
        foreach ($ras as $ra) {
            if ($rm->is_managed($ra->shortname)) {
                $syncset[$ra->id] = array(
                    'shortname' => $ra->shortname,
                    'username' => $ra->username,
                    'contextlevel' => \CONTEXT_SYSTEM,
                    'contextname' => ''
                );
            }
        }

        $SHAREDB->delete_records('shared_roles');
        $SHAREDB->insert_records('shared_roles', $syncset);
    }
}
