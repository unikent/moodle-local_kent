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
 * Course SHAREDB sync.
 */
class course_sharedb_sync extends \core\task\scheduled_task
{
    public function get_name() {
        return "Course ShareDB Sync";
    }

    public function execute() {
        global $CFG, $DB, $SHAREDB;

        if (!\local_kent\util\sharedb::available()) {
            return;
        }

        // Grab a list of courses in Moodle.
        $courses = $DB->get_records('course', array(), '', 'id,shortname,fullname,summary');

        // Generate dataset for shared_courses and shared_course_admins.
        $courseset = array();
        $adminset = array();
        foreach ($courses as $item) {
            $courseset[] = array(
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution,
                "moodle_id" => $item->id,
                "shortname" => $item->shortname,
                "fullname" => $item->fullname,
                "summary" => $item->summary
            );

            $ctx = \context_course::instance($item->id);
            $users = get_users_by_capability($ctx, 'moodle/course:update', 'u.id, u.username');
            foreach ($users as $user) {
                $adminset[] = array(
                    "moodle_env" => $CFG->kent->environment,
                    "moodle_dist" => $CFG->kent->distribution,
                    "courseid" => $item->id,
                    "username" => $user->username
                );
            }
        }

        unset($courses);

        $transaction = $SHAREDB->start_delegated_transaction();

        // Clear out SHAREDB.
        $SHAREDB->delete_records('shared_courses', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution
        ));
        $SHAREDB->delete_records('shared_course_admins', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution
        ));

        // Insert new records.
        $SHAREDB->insert_records("shared_courses", $courseset);
        $SHAREDB->insert_records("shared_course_admins", $adminset);

        $transaction->allow_commit();

        return true;
    }
}
