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
 * Checks cache.
 */
class cache_check extends \core\task\scheduled_task
{
    public function get_name() {
        return "Cache checker";
    }

    public function execute() {
        $enabled = get_config("local_kent", "enable_cache_shouter");
        if (!$enabled) {
            return;
        }

        if (!\local_hipchat\HipChat::available()) {
            return;
        }

        $instance = \cache_config::instance();
        $stores = $instance->get_all_stores();
        foreach ($stores as $name => $details) {
            $class = $details['class'];
            $store = new $class($details['name'], $details['configuration']);
            if (!$store->is_ready()) {
                \local_hipchat\Message::send("Could not communicate with cache '{$name}'!", "red");
            }
        }
    }
} 