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

namespace local_kent\notification;

defined('MOODLE_INTERNAL') || die();

class classify extends \local_notifications\notification\base {
    /**
     * Returns the component of the notification.
     */
    public static function get_component() {
        return 'local_kent';
    }

    /**
     * Returns the table name the objectid relates to.
     */
    public static function get_table() {
        return 'course';
    }

    /**
     * Returns the level of the notification.
     */
    public function get_level() {
        return \local_notifications\notification\base::LEVEL_INFO;
    }

    /**
     * Returns the notification.
     */
    public function render() {
        $rolloverlink = \html_writer::link(new \moodle_url('/local/kent/courseclassify.php', array(
            'id' => $this->objectid,
            'classify' => 1,
            'sesskey' => sesskey()
        )), 'Yes', array('class' => 'alert-link'));

        $norolloverlink = \html_writer::link(new \moodle_url('/local/kent/courseclassify.php', array(
            'id' => $this->objectid,
            'classify' => 0,
            'sesskey' => sesskey()
        )), 'No', array('class' => 'alert-link'));

        return "This manually created module has not been classified. Do you want this module to rollover next year? $rolloverlink / $norolloverlink.";
    }
}