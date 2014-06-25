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
 * Memcached Purger
 */
class memcached_purge extends \core\task\scheduled_task
{
    public function get_name() {
        return "Memcached Purger";
    }

    public function execute() {
        $enabled = get_config("local_kent", "enable_session_cron");
        if (!$enabled) {
            return;
        }

        $this->clear_slabs();
    }

    /**
     * Run through the Memcached slabs and clear out any old
     * sessions. Not supposed to be needed, but seems we need it.
     * Will investigate why at some point, but this works as a patch.
     *
     * Memcached is *supposed* to re-use items as they expire, but it seems
     * our Memcached boxes dont do that and so throw away "good" sessions.
     * Which is silly.
     */
    private function clear_slabs() {
        global $CFG, $DB;

        // Do we have anything to do?
        if (!isset($CFG->session_handler_class) || $CFG->session_handler_class !== '\core\session\memcached') {
            return false;
        }

        $time = strftime("%H:%M %d-%m-%Y");
        echo "Starting session cleanup at {$time}.\n";

        // Split up the save path.
        $servers = array();
        $parts   = explode(',', $CFG->session_memcached_save_path);
        foreach ($parts as $part) {
            $part = trim($part);
            $pos  = strrpos($part, ':');
            $host = substr($part, 0, $pos);
            $port = substr($part, ($pos + 1));
            $servers[] = array($host, $port);
        }

        // Boot up memcached.
        $memcache = new \Memcache();
        foreach ($servers as $server) {
            $memcache->addServer($server[0], $server[1]);
        }

        // Grab a list of all current sessions (Moodle handles GC... this is for cleaning up Memcached).
        $sessions = array();
        $rs = $DB->get_records_sql("SELECT sid FROM {sessions}");
        foreach ($rs as $session) {
            $sessions[$session->sid] = true;
        }

        $count = count($sessions);
        echo "Currently {$count} active sessions.\n";

        // Cleanup all old sessions that exist in Memcached.
        $count = 0;
        $slabs = $memcache->getExtendedStats('slabs');
        $items = $memcache->getExtendedStats('items');
        foreach ($slabs as $server => $slab) {
            foreach ($slab as $id => $meta) {
                if (!is_int($id)) {
                    continue;
                }

                $cdump = $memcache->getExtendedStats('cachedump', (int) $id, 100000000);
                foreach ($cdump as $server => $entries) {
                    if ($entries) {
                        foreach ($entries as $name => $data) {
                            // Is this a session key?
                            if (strpos($name, $CFG->session_memcached_prefix) !== 0) {
                                continue;
                            }

                            // Extract the session key.
                            $sesskey = substr($name, strlen($CFG->session_memcached_prefix));

                            // Is this a lock?
                            if (strpos($sesskey, "lock.") === 0) {
                                $sesskey = substr($sesskey, strlen("lock."));
                            }

                            // Is this valid?
                            if (isset($sessions[$sesskey])) {
                                continue;
                            }

                            $memcache->delete($name);
                            $count++;
                        }
                    }
                }
            }
        }

        // Halve count (locks + data).
        if ($count > 0) {
            $count = max($count / 2, 1);

            echo "Cleaned up {$count} sessions.\n";

            if (\local_hipchat\HipChat::available()) {
                \local_hipchat\Message::send("Cleaned up {$count} Memcached sessions.", "purple");
            }
        }
    }
} 