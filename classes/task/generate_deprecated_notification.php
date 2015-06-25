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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\task;

/**
 * Generate a deprecated_module notification for a given course.
 */
class generate_deprecated_notification extends \core\task\adhoc_task
{
    public function get_component() {
        return 'local_kent';
    }

    public function execute() {
        $data = (array)$this->get_custom_data();
        $courseid = $data['courseid'];

        $relevantmods = array();
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            $section = $modinfo->get_section_info($section);
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $modnumber) {
                    $mod = $modinfo->cms[$modnumber];
                    $activityman = new \local_kent\manager\activity($mod->modname);
                    if ($activityman->is_deprecated()) {
                        $relevantmods[] = array(
                            'id' => $mod->id,
                            'instance' => $mod->instance,
                            'name' => $mod->name,
                            'module' => $mod->module,
                            'modname' => $mod->modname,
                            'url' => $mod->url
                        );
                    }
                }
            }
        }

        $context = \context_course::instance($courseid);

        // Delete?
        if (empty($relevantmods)) {
            $notification = \local_kent\notification\deprecated::get($courseid, $context);
            if ($notification) {
                $notification->delete();
            }
            return true;
        }

        // Create.
        \local_kent\notification\deprecated::create(array(
            'objectid' => $courseid,
            'context' => $context,
            'other' => array(
                'mods' => $relevantmods
            )
        ));
    }

    /**
     * Setter for $customdata.
     * @param mixed $customdata (anything that can be handled by json_encode)
     */
    public function set_custom_data($customdata) {
        if (empty($customdata['courseid'])) {
            throw new \moodle_exception("courseid cannot be empty!");
        }

        return parent::set_custom_data($customdata);
    }
}
