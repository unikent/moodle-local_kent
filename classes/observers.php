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
 * Observers
 */
class observers
{
    /**
     * Course created observer.
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;

        $course = $DB->get_record('course', array(
            "id" => $event->objectid
        ));

        // Ping the group manager.
        \local_kent\GroupManager::course_created($course);

        if (!util\sharedb::available()) {
            return true;
        }

        $params = array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution,
            "moodle_id" => $course->id
        );

        if (!$SHAREDB->record_exists('shared_courses', $params)) {
            $params["shortname"] = $course->shortname;
            $params["fullname"] = $course->fullname;
            $params["summary"] = $course->summary;

            $SHAREDB->insert_record('shared_courses', $params);
        }

        return true;
    }

    /**
     * Course deleted observer.
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $CFG, $SHAREDB;

        if (!util\sharedb::available()) {
            return true;
        }

        $SHAREDB->delete_records('shared_courses', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution,
            "moodle_id" => $event->objectid
        ));

        return true;
    }

    /**
     * Course module created observer.
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        $activityman = new \local_kent\manager\activity($event->other['modulename']);
        return $activityman->notify($event->courseid);
    }

    /**
     * Course module deleted observer.
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        $activityman = new \local_kent\manager\activity($event->other['modulename']);
        return $activityman->notify($event->courseid);
    }

    /**
     * Course scheduled observer.
     */
    public static function course_scheduled(\local_catman\event\course_scheduled $event) {
        $ctx = \context_course::instance($event->courseid);

        $course = new \local_kent\Course($event->courseid);
        if (($notification = $course->get_notification($ctx->id, 'catman'))) {
            $notification->delete();
        }

        $time = \local_catman\core::get_expiration($event->courseid);
        $time = strftime("%H:%M %d/%m/%Y", $time);

        $message = "<i class=\"fa fa-exclamation-triangle\"></i> This course is scheduled for deletion at {$time}.";
        $course->add_notification($ctx->id, 'catman', $message, 'warning', false, false);

        return true;
    }

    /**
     * Course unscheduled observer.
     */
    public static function course_unscheduled(\local_catman\event\course_unscheduled $event) {
        $ctx = \context_course::instance($event->courseid);

        $course = new \local_kent\Course($event->courseid);
        if (($notification = $course->get_notification($ctx->id, 'catman'))) {
            $notification->delete();
        }
    }

    /**
     * Course purged observer.
     */
    public static function course_purged(\local_catman\event\course_purged $event) {
        $shortname = $event->other['shortname'];
        $msg = "Deleting '{$shortname}' ({$event->objectid})...";

        // Notify HipChat.
        try {
            \local_hipchat\Message::send($msg, "purple", "text");
        } catch (\Exception $e) {
            // Ignore.
            debugging($e->getMessage());
        }

        return true;
    }

    /**
     * User enrolment created.
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $CFG, $DB, $SHAREDB;

        if (!util\sharedb::available()) {
            return true;
        }

        $ctx = \context_course::instance($event->courseid);
        if (!has_capability('moodle/course:update', $ctx, $event->relateduserid)) {
            return true;
        }

        $user = $DB->get_record('user', array(
            'id' => $event->relateduserid
        ));

        $params = array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution,
            "courseid" => $event->courseid,
            "username" => $user->username
        );

        if (!$SHAREDB->record_exists('shared_course_admins', $params)) {
            $SHAREDB->insert_record('shared_course_admins', $params);
        }

        return true;
    }

    /**
     * User enrolment deleted.
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $CFG, $DB, $SHAREDB;

        if (!util\sharedb::available()) {
            return true;
        }

        $user = $DB->get_record('user', array(
            'id' => $event->relateduserid
        ));

        $SHAREDB->delete_records('shared_course_admins', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution,
            "courseid" => $event->courseid,
            "username" => $user->username
        ));

        return true;
    }

    /**
     * Triggered via role_assigned event.
     *
     * @param \core\event\role_assigned $event
     * @return bool true on success.
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        // Get the context.
        $context = $event->get_context();

        // Get the role.
        $role = $DB->get_record('role', array(
            'id' => $event->objectid
        ));

        // Ping the group manager?
        if ($context->contextlevel == \CONTEXT_COURSE && strpos($role->shortname, 'student') !== false) {
            \local_kent\GroupManager::enrolment_created($context->instanceid, $event->relateduserid);
        }

        // Ping the role manager.
        $enabled = get_config('local_kent', 'enable_role_sync');
        if ($enabled) {
            $rm = new \local_kent\RoleManager();
            $rm->on_role_created($context, $event->objectid, $event->relateduserid);
        }

        return true;
    }

    /**
     * Triggered when user role is unassigned.
     *
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;

        // Get the context.
        $context = $event->get_context();

        // Ping the role manager.
        $enabled = get_config('local_kent', 'enable_role_sync');
        if ($enabled) {
            $rm = new \local_kent\RoleManager();
            $rm->on_role_deleted($context, $event->objectid, $event->relateduserid);
        }
    }

    /**
     * Triggered when user is updated.
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        $cache = \cache::make('local_kent', 'userprefs');
        $cache->delete($event->objectid . '_prefs');
    }

    /**
     * Triggered when a rollover finished.
     *
     * @param \local_rollover\event\rollover_finished $event
     */
    public static function rollover_finished(\local_rollover\event\rollover_finished $event) {
        // Regenerate the deprecated notification.
        $task = new \local_kent\task\generate_deprecated_notification();
        $task->set_custom_data(array(
            'courseid' => $event->courseid
        ));
        \core\task\manager::queue_adhoc_task($task);
    }
}
