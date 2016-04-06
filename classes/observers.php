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
     * @param \core\event\course_created $event
     * @return bool
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;

        $course = $DB->get_record('course', array(
            "id" => $event->objectid
        ));

        // Ping the group manager.
        \local_kent\manager\group::course_created($course);

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
     * @param \core\event\course_deleted $event
     * @return bool
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
     * @param \core\event\course_module_created $event
     * @return bool
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        $activityman = new \local_kent\manager\activity($event->other['modulename']);
        return $activityman->notify($event->courseid);
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

        if ($context->contextlevel == \CONTEXT_COURSE) {
            // Get the role shortname.
            $shortname = $DB->get_field('role', 'shortname', array(
                'id' => $event->objectid
            ));

            // Ping the group manager?
            if (strpos($shortname, 'student') !== false) {
                \local_kent\manager\group::enrolment_created($context->instanceid, $event->relateduserid);
            }
        }

        return true;
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
        // Attach a deprecated notification, just in case.
        \local_kent\notification\deprecated::create(array(
            'objectid' => $event->courseid,
            'context' => \context_course::instance($event->courseid)
        ));
    }

    /**
     * Triggered when a recyclebin item has been created.
     *
     * @param \tool_recyclebin\event\course_bin_item_created $event
     */
    public static function course_bin_item_created(\tool_recyclebin\event\course_bin_item_created $event) {
        global $CFG, $DB;

        if (empty($CFG->shareddataroot)) {
            return;
        }

        $protect = array('quiz', 'turnitintool', 'turnitintooltwo');

        // Is this a quiz?
        $record = $DB->get_record_sql('SELECT trc.*, m.name as modulename
            FROM {tool_recyclebin_course} trc
            INNER JOIN {modules} m ON m.id = trc.module
        ', array('id' => $event->objectid));

        if ($record && in_array($record->modulename, $protect)) {
            // We need to protect this!
            $fs = get_file_storage();
            $files = $fs->get_area_files($event->get_context()->id, 'tool_recyclebin', TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA,
                $event->objectid, 'itemid, filepath, filename', false);
            if (empty($files)) {
                return;
            }

            $newfilename = "{$CFG->kent->distribution}_{$record->modulename}_{$record->courseid}_{$record->name}";
            if (!file_exists("{$CFG->shareddataroot}/backups")) {
                make_writable_directory("{$CFG->shareddataroot}/backups");
            }

            $file = reset($files);
            $file->copy_content_to("{$CFG->shareddataroot}/backups/{$newfilename}");
        }
    }
}
