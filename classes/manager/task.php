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

namespace local_kent\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Customise Moodle Scheduled Tasks.
 */
class task
{
    /**
     * Run all upgrade steps.
     */
    public function configure() {
        $this->configure_2015010600();
    }

    /**
     * Upgrade step for 2015010600.
     */
    public function configure_2015010600() {
        $this->configure_task('\\core\\task\\create_contexts_task', 15, '*/2');
        $this->disable_task('\\tool_langimport\\task\\update_langpacks_task');
        $this->disable_task('\\enrol_imsenterprise\\task\\cron_task');
        $this->disable_task('\\core\\task\\automated_backup_task');
    }

    /**
     * This should be called upon yearly rollover-proper (1st September each year).
     */
    public function yearly_rollover() {
        global $CFG;

        if ($CFG->kent->environment != 'live' || $CFG->kent->distribution == LIVE_MOODLE) {
            return;
        }

        // Disable a set of tasks that only need to be active on the current Moodle.
        $this->disable_task('\\enrol_connect\\task\\sync');
        $this->disable_task('\\local_connect\\task\\course_sync');
        $this->disable_task('\\local_connect\\task\\fix_mids');
        $this->disable_task('\\local_connect\\task\\group_sync');
        $this->disable_task('\\mod_cla\\task\\reminder_email');
        $this->disable_task('\\mod_thesis\\task\\submissions');
    }

    /**
     * Configure a task.
     * @param $taskname
     * @param string $minute
     * @param string $hour
     * @param string $month
     * @param string $dayofweek
     * @param string $day
     */
    private function configure_task($taskname, $minute = '*', $hour = '*', $month = '*', $dayofweek = '*', $day = '*') {
        try {
            $task = \core\task\manager::get_scheduled_task($taskname);
            if (!$task) {
                debugging("Invalid task: {$taskname}.");
                return;
            }

            $task->set_minute($minute);
            $task->set_hour($hour);
            $task->set_month($month);
            $task->set_day_of_week($dayofweek);
            $task->set_day($day);
            $task->set_disabled(false);
            $task->set_customised(true);
            \core\task\manager::configure_scheduled_task($task);
        } catch (\Exception $e) {
            debugging($taskname . " failed: " . $e->getMessage());
        }
    }

    /**
     * Enable a task.
     * @param $taskname
     */
    private function enable_task($taskname) {
        try {
            $task = \core\task\manager::get_scheduled_task($taskname);
            if (!$task) {
                debugging("Invalid task: {$taskname}.");
                return;
            }

            $task->set_disabled(false);
            $task->set_customised(true);

            \core\task\manager::configure_scheduled_task($task);
        } catch (\Exception $e) {
            debugging($taskname . " failed: " . $e->getMessage());
        }
    }

    /**
     * Disable a task.
     * @param $taskname
     */
    private function disable_task($taskname) {
        try {
            $task = \core\task\manager::get_scheduled_task($taskname);
            if (!$task) {
                debugging("Invalid task: {$taskname}.");
                return;
            }

            $task->set_disabled(true);
            $task->set_customised(true);

            \core\task\manager::configure_scheduled_task($task);
        } catch (\Exception $e) {
            debugging($taskname . " failed: " . $e->getMessage());
        }
    }
}
