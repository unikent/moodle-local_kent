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
 * Base stuff
 */
abstract class Base
{
    /**
     * Get a tracker value.
     */
    protected function get_tracker($name) {
        global $DB;

        return $DB->get_field('kent_trackers', 'value', array(
            'name' => $name
        ));
    }

    /**
     * Set a tracker value to time().
     */
    protected function update_tracker($name) {
        global $DB;

        $params = array(
            'name' => $name
        );

        if ($DB->record_exists('kent_trackers', $params)) {
            $DB->set_field('kent_trackers', 'value', time(), $params);
            return;
        }

        $params['value'] = time();
        $DB->insert_record('kent_trackers', $params);
    }
}