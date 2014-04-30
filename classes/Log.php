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

namespace local_kent;

defined('MOODLE_INTERNAL') || die();

/**
 * Overrides parts of the add_to_log function.
 */
class Log
{
    /**
     * Which table should we use for inserts?
     */
    public static function get_log_table() {
        $enabled = get_config("local_kent", "enable_log_buffer");
        return $enabled ? 'kent_log_buffer' : 'log';
    }

    /**
     * Run the buffer->table cron
     */
    public static function cron() {
        global $DB;

        $ids = array();

        // Copy from the buffer into log.
        $rs = $DB->get_recordset('kent_log_buffer');
        foreach ($rs as $record) {
            $id = $record->id;
            unset($record->id);

            if ($DB->insert_record_raw('log', $record, false)) {
                $ids[] = $id;
            }
        }
        $rs->close();

        // Cleanup.
        $DB->delete_records_list('kent_log_buffer', 'id', $ids);
    }
}