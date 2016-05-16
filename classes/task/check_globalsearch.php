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
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\task;

/**
 * Global search health checks.
 * This simply checks globalsearch is working okay.
 */
class check_globalsearch extends \core\task\scheduled_task
{
    public function get_name() {
        return "Globalsearch health checks";
    }

    public function execute() {
        if (class_exists("\\core_search\\manager") && \core_search\manager::is_global_search_enabled()) {
            try {
                $search = \core_search\manager::instance();
            } catch (\core_search\engine_exception $e) {
                if ($e->errorcode == 'engineserverstatus') {
                    // Setup the schema again.
                    $schema = new \search_solr\schema();
                    if ($schema->can_setup_server()) {
                        $schema->setup();
                    }

                    // Schedule a re-index.
                    $task = new \local_kent\task\index_globalsearch();
                    $task->set_custom_data(array(
                        'full' => true
                    ));
                    \core\task\manager::queue_adhoc_task($task);
                }
            }
        }

        return true;
    }
}
