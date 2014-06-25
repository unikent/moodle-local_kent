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

        $sql = <<<SQL
        SELECT c.id, c.shortname, c.fullname, c.summary, GROUP_CONCAT(u.username) as logins
        FROM {course} c

        LEFT OUTER JOIN {enrol} e ON e.courseid=c.id
        LEFT OUTER JOIN {role} r ON r.id=e.roleid
        LEFT OUTER JOIN {user_enrolments} ue ON ue.enrolid=e.id
        LEFT OUTER JOIN {user} u ON u.id=ue.userid

        WHERE r.shortname IN ("sds_teacher", "sds_convenor", "convenor", "dep_admin", "manager", "editingteacher", "support_staff")
            OR r.id IS NULL

        GROUP BY c.id
SQL;

        // Grab a list of courses in Moodle.
        $courses = $DB->get_records_sql($sql);

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

            if (empty($item->logins)) {
                continue;
            }

            $logins = explode(',', $item->logins);
            foreach ($logins as $login) {
                $adminset[] = array(
                    "moodle_env" => $CFG->kent->environment,
                    "moodle_dist" => $CFG->kent->distribution,
                    "courseid" => $item->id,
                    "username" => $login
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
    }
} 