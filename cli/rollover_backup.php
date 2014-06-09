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
 * Kent rollover backup script.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

raise_memory_limit(MEMORY_HUGE);

$settings = \local_kent\util\cli::std_in_to_array();

if (empty($settings)) {
    cli_error("No prefs detected!");
}

$controller = new \local_kent\backup\controllers\rollover($settings['id'], $settings);
$controller->execute_plan();
$result = $controller->get_results();
$file = $result['backup_destination'];

if ($file->get_contenthash()) {
    $packer = get_file_packer('application/vnd.moodle.backup');

    $destination = $CFG->tempdir . '/backup/' . $file->get_contenthash();

    $file->extract_to_pathname($packer, $destination);
    $file->delete();

    echo $destination;

    exit(0);
}

exit(1);
