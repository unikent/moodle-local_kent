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

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course helpers
 */
class Course
{
	private $_courseid;

	public function __construct($courseid) {
		$this->_courseid = $courseid;
	}

	/**
	 * Helper for creating a manual module code.
	 * Rollover should be true or false, null means we don't know.
	 */
	public static function get_manual_shortname($rollover = null) {
		global $DB;

		$rollover = $rollover === null ? 'X' : ($rollover == true ? 'P' : 'O');
		$shortname = "D{$rollover}";

		$like = $DB->sql_like('shortname', ':shortname');
		$like2 = $DB->sql_like('shortname', ':shortname2');
		$sql = <<<SQL
			SELECT shortname
			FROM {course}
			WHERE {$like}

			UNION

			SELECT shortname
			FROM {course_request}
			WHERE {$like2}
SQL;

		$courses = $DB->get_records_sql($sql, array(
		    'shortname' => $shortname . "%",
		    'shortname2' => $shortname . "%"
		));


		$num = 1000;
		foreach ($courses as $course) {
			$pos = (int)substr($course->shortname, 2);
			if ($pos >= $num) {
				$num = $pos + 25;
			}
		}

		return "{$shortname}{$num}";
	}

	/**
	 * Add a notification to a course.
	 * 
	 * @param int $contextid The context ID of the component that is alerting.
	 * @param string $extref Something to remember me by, e.g. 'delete_notify'. Used with $contextid to grab notifications.
	 * @param string $message The message (HTML is fine).
	 * @param string $type warning, danger, info.
	 * @param boolean $actionable Can this alert be actioned by a user?
	 * @param boolean $dismissable Can this alert be dismissed by users?
	 */
	public function add_notification($contextid, $extref, $message, $type = 'warning', $actionable = false, $dismissable = true) {
		return \local_kent\Notification::create($this->_courseid, $contextid, $extref, $message, $type, $actionable, $dismissable);
	}

	/**
	 * Return a list of notifications.
	 */
	public function get_notifications($type = null) {
		global $DB;

		$objects = array();

		$params = array('courseid' => $this->_courseid);
		if ($type !== null) {
			$params['type'] = $type;
		}

		$records = $DB->get_records('course_notifications', $params);
		foreach ($records as $record) {
			$objects[] = \local_kent\Notification::instance($record);
		}

		return $objects;
	}

	/**
	 * Return a list of notifications within a specific context.
	 */
	public function get_notification($contextid, $extref) {
		global $DB;

		$record = $DB->get_record('course_notifications', array(
			'courseid' => $this->_courseid,
			'contextid' => $contextid,
			'extref' => $extref
		));

		if (!$record) {
			return null;
		}

		return \local_kent\Notification::instance($record);
	}

	/**
	 * Return a count of actionable notifications for a course.
	 */
	public function get_actionable_notifications_count() {
		global $DB;

		$sql = "SELECT SUM(actionable) as actions FROM {course_notifications} WHERE courseid = :courseid AND actionable > 0";
		$count = $DB->get_record_sql($sql, array("courseid" => $this->_courseid));

		if (!$count) {
			return 0;
		}

		return $count->actions;
	}

    /**
     * Restore an item from the recycle bin.
     */
    public function restore_from_recycle_bin($cmid) {
        global $DB;

        $modinfo = get_fast_modinfo($this->_courseid);
        if (!isset($modinfo->cms[$cmid])) {
            throw new \moodle_exception("Invalid CMID.");
        }
        $cminfo = $modinfo->cms[$cmid];

        // Just move it back to the first section.
        moveto_module($cminfo, $modinfo->get_section_info(0));
    }

    /**
     * Returns the recycle bin section.
     */
    public function get_recycle_bin() {
        global $DB;

        // Do we have one?
        $section = $DB->get_record('course_sections', array(
            'course' => $this->_courseid,
            'section' => 99999
        ));

        if (!$section) {
            $section = new \stdClass();
            $section->name = 'Recycle bin';
            $section->summary = '';
            $section->summaryformat = 1;
            $section->course = $this->_courseid;
            $section->section = 99999;
            $section->visible = 0;

            $section->id = $DB->insert_record('course_sections', $section);
        }

        return $section;
    }

    /**
     * Returns the items in the recycle bin.
     */
    public function get_recycle_bin_items() {
        $cms = array();
        $section = $this->get_recycle_bin();

        $modinfo = get_fast_modinfo($this->_courseid);
        foreach ($modinfo->cms as $cm) {
            if ($cm->section == $section->id) {
                $cms[] = $cm;
            }
        }

        return $cms;
    }

    /**
     * Called when someone tries to delete a quiz.
     * If this returns "true", we will continue with the deletion.
     * If it returns "false", we will stop and cry a bit but NOT delete the actual quiz.
     *
     * @param $quiz
     * @return bool
     */
    public static function on_quiz_delete($quiz) {
        global $DB;

        // Disable the hook if we are testing.
        if (defined("PHPUNIT_UTIL") && PHPUNIT_UTIL) {
            return true;
        }

        // We want to add the module to a "Recycle Bin".
        $course = new static($quiz->course);
        $section = $course->get_recycle_bin();

        $module = $DB->get_record('modules', array(
            'name' => 'quiz'
        ));

        // Create a module container.
        $cm = new \stdClass();
        $cm->course   = $quiz->course;
        $cm->module   = $module->id;
        $cm->instance = $quiz->id;
        $cm->section  = $section->id;
        $cm->visible  = 1;

        // Create the module.
        $cm = add_course_module($cm);
        course_add_cm_to_section($quiz->course, $cm, $section->section);

        return false;
    }
}
