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

require_once($CFG->dirroot . "/local/kent/lib/footprints/src/API.php");

/**
 * Sync a specified user's enrolments.
 */
class footprints_send extends \core\task\adhoc_task
{
    public function get_component() {
        return 'local_kent';
    }

    public function execute() {
        $data = (array)$this->get_custom_data();
        $json = $data['json'];

        $obj = json_decode($json);
        if (!$obj) {
            throw new \moodle_exception("Error: Invalid JSON.");
        }

        $ticketnumber = \unikent\Footprints\API::create_raw(json_encode(array($obj)));

        $event = \local_kent\event\footprints_ticket_created::create(array(
            'context' => \context_system::instance(),
            'other' => array(
                'ticketnumber' => $ticketnumber,
                'workspace' => $obj->Workspace
            )
        ));
        $event->trigger();
    }

    /**
     * Setter for $customdata.
     * @param mixed $customdata (anything that can be handled by json_encode)
     */
    public function set_custom_data($customdata) {
        if (empty($customdata['json'])) {
            throw new \moodle_exception("JSON cannot be empty!");
        }

        return parent::set_custom_data($customdata);
    }
}
