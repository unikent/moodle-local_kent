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
 * Course helpers
 */
class Course
{
	private $_courseid;

	public function __construct($courseid) {
		$this->_courseid = $courseid;
	}

	/**
	 * Add a notification to a course.
	 * 
	 * @param int $contextid The context ID of the component that is alerting.
	 * @param string $extref Something to remember me by, e.g. 'delete_notify'. Used with $contextid to grab notifications.
	 * @param string $message The message (HTML is fine).
	 * @param string $type One of the local_kent::Notification TYPE_* consts.
	 * @param boolean $actionable Can this alert be actioned by a user?
	 * @param boolean $dismissable Can this alert be dismissed by users?
	 */
	public function add_notification($contextid, $extref, $message, $type=Notification::TYPE_WARNING, $actionable = false, $dismissable = true) {
		\local_kent\Notification::create($this->_courseid, $contextid, $extref, $message, $type, $actionable, $dismissable);
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
}