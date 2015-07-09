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

/**
 * Local stuff for Moodle Kent
 *
 * @package    local_kent
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\task;

/**
 * Config shouter.
 */
class config_shouter extends \core\task\scheduled_task
{
    public function get_name() {
        return "Config Shouter";
    }

    public function execute() {
        global $CFG, $DB;

        // What was the last time we shouted about in the config logs table?
        $lasttime = $this->get_last_run_time();
        if (empty($lasttime)) {
            return true;
        }

        $enabled = get_config("local_kent", "enable_config_shouter");
        if (!$enabled) {
            return true;
        }

        if (!\local_hipchat\HipChat::available()) {
            return true;
        }

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

        if (empty($entries)) {
            return true;
        }

        $messages = array();
        foreach ($entries as $entry) {
            $username = $entry->firstname . " " . $entry->lastname;
            $msg = "{$username} changed the value of '{$entry->name}'";
            $msg .= " ('{$entry->plugin}') from '{$entry->oldvalue}' to '{$entry->value}'.";
            $messages[] = $msg;
            \local_hipchat\Message::send($msg);
        }

        // Also submit a CR.
        $cr = new \local_kent\footprints\change_request("[Moodle] Config Change");
        $cr->set_user("w3moodle");
        $cr->set_emails(false, false, false);
        $cr->add_entry(implode("\n", $messages));
        $cr->add_assignee("Learning and Research Development");
        $cr->add_server_link($CFG->kent->servers);
        $cr->schedule();

        return true;
    }
} 