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
 * A Notification
 */
class Notification
{
	private $_data;

	private function __construct($notification) {
		$this->_data = (array)$notification;
		$this->_data['_seen'] = array();
	}

	/**
	 * Create.
	 */
	public static function create($courseid, $contextid, $extref, $message, $type, $actionable, $dismissable) {
		global $DB;

		$DB->insert_record('course_notifications', array(
			'courseid' => $courseid,
			'contextid' => $contextid,
			'extref' => $extref,
			'message' => $message,
			'type' => $type,
			'actionable' => $actionable ? '1' : '0',
			'dismissable' => $dismissable ? '1' : '0'
		));
	}

	/**
	 * Instance.
	 */
	public static function instance($objorid) {
		global $DB;

		if (is_object($objorid)) {
			return new static($objorid);
		}

		$obj = $DB->get_record('course_notifications', array(
			'id' => $objorid
		));

		if ($obj) {
			return new static($obj);
		}

		return null;
	}

	/**
	 * Magic.
	 * But remember! All magic, comes with a price!
	 */
	public function __get($name) {
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}

		$method = "get_$name";
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		return null;
	}

	/**
	 * More magic.
	 */
	public function __isset($name) {
		return isset($this->_data[$name]) || method_exists($this, "get_$name");
	}

	/**
	 * Have we been seen?
	 */
	public function get_seen($userid = null) {
		global $DB, $USER;

		if ($userid === null) {
			$userid = $USER->id;
		}

		if (!isset($this->_data['_seen'][$userid])) {
			$this->_data['_seen'][$userid] = $DB->record_exists('course_notifications_seen', array(
				'nid' => $this->id,
				'userid' => $userid
			));
		}

		return $this->_data['_seen'][$userid];
	}

	/**
	 * Mark this as seen by the current user.
	 */
	public function set_seen($userid = null) {
		global $DB, $USER;

		if ($userid === null) {
			$userid = $USER->id;
		}

		$DB->insert_record('course_notifications_seen', array(
			'nid' => $this->id,
			'userid' => $userid
		));
	}

	/**
	 * Delete this.
	 */
	public function delete() {
		global $DB;

		$DB->delete_records('course_notifications_seen', array(
			'nid' => $this->id
		));

		$DB->delete_records('course_notifications', array(
			'id' => $this->id
		));
	}
}