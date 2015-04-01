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
	 * @param string $ref Something to remember me by, e.g. 'delete_notify'. Used with $contextid to grab notifications.
	 * @param string $message The message (HTML is fine).
	 * @param boolean $dismissable Can this alert be dismissed by users?
	 */
	public function add_notification($contextid, $ref, $message, $dismissable = true) {
		// TODO.
	}

	/**
	 * Remove a notification.
	 */
	public function remove_notification($contextid, $ref) {
		// TODO.
	}

	/**
	 * Return a list of notifications.
	 */
	public function get_notifications() {
		// TODO.
	}

	/**
	 * Return a list of notifications within a specific context.
	 */
	public function get_notifications_context($contextid, $ref) {
		// TODO.
	}

	/**
	 * Mark a notification as 'seen' by a specific user.
	 */
	public function notification_seen($id, $userid) {
		// TODO.
	}
}