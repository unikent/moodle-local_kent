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
 * Config stuff
 */
class Config
{
    /**
     * Run the shouter cron.
     */
    public static function cron() {
        global $DB;

        if (!\local_hipchat\HipChat::available()) {
            return false;
        }

        // What was the last time we shouted about in the config logs table?
        $lasttime = $DB->get_field('local_kent_trackers', 'value', array(
            'name' => 'config_tracker'
        ));

        // Update the time stamp.
        $DB->set_field('local_kent_trackers', 'value', time(), array(
            'name' => 'config_tracker'
        ));

        // Grab all entries since then, not made by admin.
        $sql = <<<SQL
            SELECT cl.id, cl.plugin, cl.name, cl.value, cl.oldvalue, u.firstname, u.lastname
            FROM {config_log} cl
            INNER JOIN {user} u ON u.id=cl.userid
            WHERE cl.timemodified > :time AND u.id > 2
            ORDER BY cl.id ASC
SQL;
        $entries = $DB->get_records_sql($sql, array(
            'time' => $lasttime
        ));

        foreach ($entries as $entry) {
            $username = $entry->firstname . " " . $entry->lastname;
            $msg = "{$username} changed the value of '{$entry->name}'";
            $msg .= " ('{$entry->plugin}') from '{$entry->oldvalue}' to '{$entry->value}'.";
            \local_hipchat\Message::send($msg);
        }
    }
}
