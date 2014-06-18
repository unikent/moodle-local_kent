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

namespace local_kent;

defined('MOODLE_INTERNAL') || die();

/**
 * Course stuff
 */
class Course
{
    /**
     * Course created observer.
     * @todo Just add a task for this to regen
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;

        $fullname = $event->other['fullname'];

        $hipchat = get_config("local_kent", "enable_course_shouter");
        if ($hipchat && \local_hipchat\HipChat::available()) {
            \local_hipchat\Message::send("A new course has been created: '{$fullname}'.");
        }

        $course = $DB->get_record('course', array(
            "id" => $event->objectid
        ));

        $SHAREDB->insert_record("shared_courses", array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution,
            "moodle_id" => $course->id,
            "shortname" => $course->shortname,
            "fullname" => $course->fullname,
            "summary" => $course->summary
        ));
    }

    /**
     * User enrolment created.
     * @todo Just add a task for this to regen
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $CFG, $DB, $SHAREDB;

        $user = $DB->get_record('user', array(
            'id' => $event->relateduserid
        ));

        $ctx = \context_course::instance($event->courseid);
        if (has_capability('moodle/course:update', $ctx, $event->relateduserid)) {
            $SHAREDB->insert_record("shared_course_admins", array(
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution,
                "courseid" => $event->courseid,
                "username" => $user->username
            ));
        }
    }

    /**
     * Run the shouter cron.
     */
    public static function cron() {
        global $DB;

        $hipchat = get_config("local_kent", "enable_course_shouter");
        $notifyalt = get_config("local_kent", "enable_course_alt_shouter");

        // What was the last time we shouted about in the config logs table?
        $lasttime = $DB->get_field('kent_trackers', 'value', array(
            'name' => 'course_tracker'
        ));

        // Update the time stamp.
        $DB->set_field('kent_trackers', 'value', time(), array(
            'name' => 'course_tracker'
        ));

        // Grab all entries since then, not made by admin.
        $entries = $DB->get_records_select('course', 'timecreated > :time', array(
            'time' => $lasttime
        ), '', 'id, shortname, fullname, category');

        if ($hipchat && \local_hipchat\HipChat::available()) {
            if (!empty($entries)) {
                $count = count($entries);
                $lastdate = strftime("%d-%m-%Y %H:%M", $lasttime);
                $coursestr = $count > 1 ? "courses have" : "course has";
                \local_hipchat\Message::send("{$count} {$coursestr} been created since {$lastdate}.");
            }
        }

        if ($notifyalt) {
            foreach ($entries as $entry) {
                static::send_email($entry);
            }
        }

        if (util\sharedb::available()) {
            // Regen SHAREDB.
            $lasttime = $DB->get_field('kent_trackers', 'value', array(
                'name' => 'sharedb_tracker'
            ));

            if (time() - $lasttime >= 86400) {
                static::regen_courses();

                $DB->set_field('kent_trackers', 'value', time(), array(
                    'name' => 'sharedb_tracker'
                ));
            }
        }
    }

    /**
     * Emails Academic Liason Team
     */
    private static function send_email($course) {
        global $CFG;

        $courses = \local_connect\course::get_by('mid', $course->id);

        // Force array.
        if (!is_array($courses)) {
            $courses = array($courses);
        }

        $campus = array();
        foreach ($courses as $obj) {
            $name = $obj->campus->name;
            if (!in_array($name, $campus)) {
                $campus[] = $name;
            }
        }

        if (!empty($campus)) {
            $campus = implode(', ', $campus);
        } else {
            $campus = 'unknown';
        }

        $email = <<<HTML
[###=== FP:TicketTemplate ===###]

[###=== FP:Config:WorkspaceID ===###] 2
[###=== FP:Config:EntryCount ===###] 1

[###=== FP:Contact:Username ===###] unk
[###=== FP:Contact:Email__bAddress ===###] unknown@kent.ac.uk

[###=== FP:Config:Assignees ===###] Academic__bLiaison db370
[###=== FP:Config:Priority ===###] 2
[###=== FP:Config:Status ===###] New
[###=== FP:Config:Title ===###] Moodle : New module created
[###=== FP:Entry:1:Field:Type__bof__bTicket ===###] Service__bRequest__b__u__bService
[###=== FP:Entry:1:Field:Category ===###] Library

[###=== FP:Entry:1:Email:Assignees ===###] yes
[###=== FP:Entry:1:Email:Contact ===###] no
[###=== FP:Entry:1:Email:CCs ===###] no

[###=== FP:Entry:1:Description:Description ===###]

Code: $course->shortname
Title: $course->fullname
Campus: $campus
Course: $CFG->wwwroot/course/view.php?id=$course->id
Category: $CFG->wwwroot/course/category.php?id=$course->category
HTML;

        $user = get_admin();
        $user->email = 'sbsrem@kent.ac.uk';
        email_to_user($user, get_admin(), 'FootPrints Templated Ticket Email', $email);
    }

    /**
     * Populate the Shared DB list of courses.
     * @todo Move to bulk insert in Moodle 2.7
     */
    public static function regen_courses() {
        global $CFG, $DB, $SHAREDB;

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

        // Clear out SHAREDB.
        $SHAREDB->delete_records('shared_courses', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution
        ));
        $SHAREDB->delete_records('shared_course_admins', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution
        ));

        // Copy across.
        foreach ($courses as $item) {
            // Insert.
            $SHAREDB->insert_record("shared_courses", array(
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution,
                "moodle_id" => $item->id,
                "shortname" => $item->shortname,
                "fullname" => $item->fullname,
                "summary" => $item->summary
            ), true, true);

            if (empty($item->logins)) {
                continue;
            }

            $logins = explode(',', $item->logins);
            foreach ($logins as $login) {
                $SHAREDB->insert_record("shared_course_admins", array(
                    "moodle_env" => $CFG->kent->environment,
                    "moodle_dist" => $CFG->kent->distribution,
                    "courseid" => $item->id,
                    "username" => $login
                ), true, true);
            }
        }
    }
}
