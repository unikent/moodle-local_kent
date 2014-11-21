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

namespace local_kent\footprints;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/local/kent/lib/footprints/src/Ticket.php");
require_once($CFG->dirroot . "/local/kent/lib/footprints/src/ChangeRequest.php");

/**
 * Footprints ticket class.
 */
class change_request extends \unikent\Footprints\ChangeRequest
{
    /**
     * Schedule ticket creation.
     */
    public function schedule() {
        $task = new \local_kent\task\footprints_send();
        $task->set_custom_data(array(
            'json' => json_encode($this->get_footprints_entry())
        ));
        \core\task\manager::queue_adhoc_task($task);
    }
}
