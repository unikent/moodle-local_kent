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

namespace local_kent\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event Class
 */
abstract class sharedb_event extends \core\event\base
{
    /** @var array simple record cache */
    private $sharedrecordsnapshots = array();

    /**
     * Add cached data that will be most probably used in event observers.
     *
     * This is used to improve performance, but it is required for data
     * that was just deleted.
     *
     * @param string $tablename
     * @param \stdClass $record
     *
     * @throws \coding_exception if used after ::trigger()
     */
    public final function add_shared_record_snapshot($tablename, $record) {
        if ($this->triggered) {
            throw new \coding_exception('It is not possible to add snapshots after triggering of events');
        }

        $this->sharedrecordsnapshots[$tablename][$record->id] = $record;
    }

    /**
     * Returns cached record or fetches data from database if not cached.
     *
     * @param string $tablename
     * @param int $id
     * @return \stdClass
     *
     * @throws \coding_exception if used after ::restore()
     */
    public final function get_shared_record_snapshot($tablename, $id) {
        global $SHAREDB;

        if ($this->restored) {
            throw new \coding_exception('It is not possible to get snapshots from restored events');
        }

        if (isset($this->sharedrecordsnapshots[$tablename][$id])) {
            return clone($this->sharedrecordsnapshots[$tablename][$id]);
        }

        $record = $SHAREDB->get_record($tablename, array('id' => $id));
        $this->sharedrecordsnapshots[$tablename][$id] = $record;

        return $record;
    }
}
