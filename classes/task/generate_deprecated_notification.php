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

        $ctx = \context_course::instance($courseid);

        $message = '';
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            $section = $modinfo->get_section_info($section);
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $modnumber) {
                    $mod = $modinfo->cms[$modnumber];
                    $activityman = new \local_kent\manager\activity($mod->modname);
                    if ($activityman->is_deprecated()) {
                        $message .= '<li>' . \html_writer::link($mod->url, $mod->name, array('class' => 'alert-link')) . '</li>';
                    }
                }
            }
        }

        if (empty($message)) {
            return true;
        }

        $message = '<p>You have some deprecated activities. They may be removed in a future Moodle update.</p><ul>' . $message . '</ul>';

        $course = new \local_kent\Course($courseid);
        if (($notification = $course->get_notification($ctx->id, 'deprecated_modules'))) {
            $notification->delete();
        }
        $course->add_notification($ctx->id, 'deprecated_modules', $message, 'warning', true, false);

        return true;
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
