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

namespace local_kent\shared;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared config interface.
 */
class config
{
    /**
     * Set a shared config.
     */
    public static function set($name, $value) {
        global $SHAREDB;

        // Get the record.
        $record = self::get($name, true);
        if (!$record) {
            $record = new \stdClass();
            $record->name = $name;
        }

        $record->value = $value;

        if (isset($record->id)) {
            $SHAREDB->update_record('shared_config', $record);
        } else {
            $SHAREDB->insert_record('shared_config', $record);
        }
    }

    /**
     * Get a shared config.
     */
    public static function get($name, $full = false) {
        global $SHAREDB;

        $record = $SHAREDB->get_record('shared_config', array(
            'name' => $name
        ));

        if (!$record) {
            return null;
        }

        if ($full) {
            return $record;
        }

        return $record->value;
    }

    /**
     * Atomic increment, returns new value.
     */
    public static function increment($name) {
        global $SHAREDB;

        $result = $SHAREDB->get_record_sql("SELECT value FROM {shared_config} WHERE name=:name FOR UPDATE", array(
            'name' => $name
        ));

        if (!$result) {
            $result = new \stdClass();
            $result->value = 0;
        }

        $result->value = (int)$result->value + 1;

        self::set($name, $result->value);

        $SHAREDB->execute("commit");

        return $result->value;
    }
}