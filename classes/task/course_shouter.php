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
 * New Course Shouter.
 *
 * Sends to Hipchat and Academic Liason Team
 */
class course_shouter extends \core\task\scheduled_task
{
    public function get_name() {
        return "Course Shouter";
    }

    public function execute() {
        global $DB;

        // What was the last time we shouted about in the config logs table?
        $lasttime = $this->get_last_run_time();
        if (empty($lasttime) || $lasttime === 0) {
            return true;
        }

        // Grab all entries since then, not made by admin.
        $entries = $DB->get_records_select('course', 'timecreated > :time', array(
            'time' => $lasttime
        ), '', 'id, shortname, fullname, category');

        if (!empty($entries)) {
            $this->send_emails($entries);
            $this->send_hipchats($entries);
        }

        return true;
    }

    /**
     * Send a message about a new course to HipChat.
     */
    private function send_hipchats($courses) {
        $hipchat = get_config("local_kent", "enable_course_shouter");
        if (!$hipchat || !\local_hipchat\HipChat::available()) {
            return;
        }

        $shortnames = array();
        foreach ($courses as $course) {
            $shortnames[] = $course->shortname;
        }
        $shortnames = implode(', ', $shortnames);

        \local_hipchat\Message::send("New courses have been created: '{$shortnames}'.");
    }

    /**
     * Emails Academic Liason Team
     */
    private function send_emails($courses) {
        global $CFG;

        $notifyalt = get_config("local_kent", "enable_course_alt_shouter");
        if (!$notifyalt) {
            return;
        }

        $formatted = array();

        foreach ($courses as $course) {
            $courses = \local_connect\course::get_by('mid', $course->id, true);

            $campus = array();
            foreach ($courses as $obj) {
                $name = $obj->campus->name;
                if (!in_array($name, $campus)) {
                    $campus[] = $name;
                }
            }

            if (!empty($campus)) {
                $campus = implode(', ', $campus);
            } else {
                $campus = 'unknown';
            }

            $courseurl = "{$CFG->wwwroot}/course/view.php?id={$course->id}";
            $categoryurl = "{$CFG->wwwroot}/course/category.php?id={$course->category}";

            $formatted[] = <<<HTML
-----------------------------------
Code: $course->shortname
Title: $course->fullname
Campus: $campus
Course: $courseurl
Category: $categoryurl
-----------------------------------
HTML;
        }

        $formatted = implode("\n\n", $formatted);


        $ticket = new \local_kent\footprints\ticket("[Moodle] New Modules Created");
        $ticket->set_user("w3moodle");
        $ticket->add_entry($formatted);
        $ticket->add_cc("D.Bedford@kent.ac.uk");
        $ticket->add_assignee("Academic Liaison");
        $ticket->set_type("Service Request - Service");
        $ticket->set_category("Library");
        $ticket->schedule();
    }
} 