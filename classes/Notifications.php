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
 * Notifications Manager.
 */
class Notifications
{
	/**
	 * Returns an array of notifications for a given user.
	 */
	public function get($user) {
		global $SHAREDB;

		$user = is_object($user) ? $user->username : $user;

		return $SHAREDB->get_records('shared_notifications', array(
			'username' => $user,
			'seen' => 0
		));
	}

	/**
	 * Mark a notification as seen.
	 */
	public function seen($notification) {
		global $SHAREDB;

		$notification->seen = 1;
		$SHAREDB->update_record('shared_notifications', $notification);
	}

	/**
	 * Create a new notification.
	 */
	public function create($username, $content) {
		global $SHAREDB;

		$notification = new \stdClass();
		$notification->username = $username;
		$notification->content = $content;
		$notification->time = time();
		$notification->seen = 0;

		$SHAREDB->insert_record('shared_notifications', $notification);
	}
}
