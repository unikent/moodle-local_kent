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

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');

$user = posix_getpwuid(posix_geteuid());
if ($user['name'] !== 'w3moodle') {
    die("This script must be run as w3moodle.");
}

/*
 * Post deploy hooks.
 * This is run as w3moodle (magic!).
 */

// Signal supervisord to restart.
$beanstalkv = $DB->get_field('config', 'value', array('name' => 'beanstalk_deploy'));
if (!$beanstalkv) {
    $DB->insert_record('config', array(
        'name' => 'beanstalk_deploy',
        'value' => 1
    ));
} else {
    $DB->set_field('config', 'value', $beanstalkv + 1, array('name' => 'beanstalk_deploy'));
}

// A kick will cause all workers to reload.
\tool_adhoc\beanstalk::kick_workers();

// Re-symlink the climaintenance template.
$path = "{$CFG->dataroot}/climaintenance.template.html";
if (file_exists($path) || is_link($path)) {
    unlink($path);
}

symlink("{$CFG->dirroot}/theme/kent/pages/climaintenance.html", $path);

// Re-check nagios.
\local_nagios\Core::regenerate_list();

// Re-generate tutorials list.
\local_tutorials\loader::update();
