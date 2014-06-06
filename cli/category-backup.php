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
 * This file backs up all courses in a category, then gives you the tgz.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'category' => false
    )
);

if (!$options['category']) {
    cli_error("Must specify category with --category=id!");
}

raise_memory_limit(MEMORY_HUGE);

cli_heading("Running Category Backup");

$username = exec('logname');
$user = $DB->get_record('user', array(
    'username' => $username
));

if ($user) {
    echo "Detected user: {$user->username}.\n";
} else {
    $user = get_admin();
    echo "No valid username detected - using admin.\n";
}

\core\session\manager::set_user($user);

$courses = $DB->get_fieldset_sql("
    SELECT c.id FROM {course} c
    INNER JOIN {course_categories} cc
      ON cc.id=c.category
    WHERE cc.path LIKE :cata
      OR cc.path LIKE :catb
", array(
    "cata" => "%/" . $options['category'] . "/%",
    "catb" => "%/" . $options['category']
));

$prefs = array();

foreach ($courses as $course) {
    cli_separator();
    echo "Backing up course $course...\n";

    $controller = new \local_kent\backup\controllers\simple($course, $prefs);
    $controller->execute_plan();
    $result = $controller->get_results();

    $file = $result['backup_destination'];
    $hash = $file->get_contenthash();
    if (!$hash) {
        cli_problem("Failed!\n");
        continue;
    }

    // Okay we have a backup file, copy it to temp dir.
    $filename = $CFG->tempdir . '/backup/' . $hash;
    file_put_contents($filename, $file->get_content());
    $file->delete();

    echo "Finished!\n";
    cli_separator();
}