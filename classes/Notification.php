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
 * Notification stuff/
 */
class Notification
{
    /**
     * Create a new notification for a given user.
     * 
     * @param  stdObject $to     The user object (or ID) to send the message to.
     * @param  string $shortdesc Short description (title) of the message.
     * @param  string $longdesc  Longer description, with more detail (optional).
     */
    public static function create($to, $shortdesc, $longdesc = '') {
        global $DB;

        // Map $to to an id.
        if (is_object($to)) {
            $to = $to->id;
        }

        // Check shortname.
        if (strlen($shortdesc) > 255) {
            debugging('Notification::create - $shortdesc must be less than 255 characters!');
            return false;
        }

        return $DB->insert_record('kent_notifications', array(
            'to' => $to,
            'shortdesc' => $shortdesc,
            'longdesc' => $longdesc,
            'updated' => time(),
            'seen' => 0
        ));
    }
}