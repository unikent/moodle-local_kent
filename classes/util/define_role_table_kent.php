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

namespace local_kent\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Our own, special role table.
 */
class define_role_table_kent extends \core_role_define_role_table_advanced
{
    /**
     * Update $this->permissions based on submitted data, while making a list of
     * changed capabilities in $this->changed.
     */
    public function read_submitted_permissions() {
        $this->changed = array();

        foreach ($this->capabilities as $cap) {
            if ($cap->locked || $this->skip_row($cap)) {
                // The user is not allowed to change the permission for this capability.
                continue;
            }

            // TODO - be a bit more intelligent about this one day.
            $this->changed[] = $cap->name;
        }
    }

    protected function save_allow($type) {
        global $DB;

        $arr = $this->{'allow'.$type};
        if (in_array($this->roleid, $arr) && in_array(-1, $arr)) {
            $key = array_search($this->roleid, $arr);
            unset($this->{'allow'.$type}[$key]);
        }

        return parent::save_allow($type);
    }
}