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

namespace local_kent\notification;

defined('MOODLE_INTERNAL') || die();

class deprecated extends \local_notifications\base {
    /**
     * Returns the component of the notification.
     */
    public static function get_component() {
        return 'local_kent';
    }

    /**
     * Returns the table name the objectid relates to.
     */
    public static function get_table() {
        return 'course';
    }

    /**
     * Returns the level of the notification.
     */
    public function get_level() {
        return \local_notifications\base::LEVEL_WARNING;
    }

    /**
     * Returns the notification.
     */
    public function render() {
        $relevantmods = array();
        $modinfo = get_fast_modinfo($this->objectid);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            $section = $modinfo->get_section_info($section);
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $modnumber) {
                    $mod = $modinfo->cms[$modnumber];
                    $activityman = new \local_kent\manager\activity($mod->modname);
                    if ($activityman->is_deprecated()) {
                        $relevantmods[] = \html_writer::link($mod->url, $mod->name, array(
                            'class' => 'alert-link'
                        ));
                    }
                }
            }
        }

        if (empty($relevantmods)) {
            $this->delete();
            return null;
        }

        $modlist = \html_writer::alist($relevantmods);
        return "You have some deprecated activities. They may be removed in a future Moodle update. {$modlist}";
    }

    /**
     * Checks custom data.
     */
    public function set_custom_data($data) {
        if (empty($data['mods'])) {
            throw new \moodle_exception('You must set a mod list.');
        }

        parent::set_custom_data($data);
    }
}
