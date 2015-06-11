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
 * Activity manager
 */
class activity
{
    private $_module;

    /**
     * Create an instance of the activity manager.
     */
    public function __construct($activity) {
        global $DB;

        $this->_module = $DB->get_record('modules', array(
            'name' => $activity
        ), '*', \MUST_EXIST);
    }

    /**
     * Deprecate the activity.
     */
    public function deprecate() {
        if (!self::is_deprecated($this->_module->name)) {
            debugging("[Deprecate] You forgot to add the module name to is_deprecated!");
        }

        // TODO.
        //  - Notify all courses with one of the activities.
        //  - Remove capabilities.
    }

    /**
     * Returns true if a specific module is deprecated.
     */
    public static function is_deprecated($activity) {
        return (
            $activity == 'turnitintool' ||
            $activity == 'hotpot'
        );
    }
}
