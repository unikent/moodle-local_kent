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
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\task;

/**
 * GeoIP Database Sync.
 */
class geoip_sync extends \core\task\scheduled_task
{
    public function get_name() {
        return "GeoIP DB Sync";
    }

    public function execute() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

	if (!file_exists("{$CFG->dataroot}/geoip")) {
        	make_writable_directory("{$CFG->dataroot}/geoip");
	}

        $dir = make_request_directory();
        $filepath = "{$dir}/GeoLiteCity.dat.gz";

        $c = new \curl();
        $results = $c->download(array(array(
            'url' => 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz',
            'filepath' => $filepath
        )));

        $gz = gzopen($filepath, 'rb');
        if (!$gz) {
            throw new \moodle_exception("Cannot open source.");
        }

        $dest = fopen("{$CFG->dataroot}/geoip/GeoLiteCity.dat", 'wb');
        if (!$dest) {
            gzclose($gz);
            throw new \moodle_exception("Cannot open destination.");
        }

        stream_copy_to_stream($gz, $dest);

        gzclose($gz);
        fclose($dest);

        return true;
    }
}
