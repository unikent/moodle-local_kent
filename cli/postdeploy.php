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

if (!defined('KENT_MOODLE')) {
    define('CLI_SCRIPT', true);
    require_once(dirname(__FILE__) . '/../../../config.php');
}

/**
 * Post deploy hooks.
 * This is run as w3moodle (magic!).
 */

// Re-symlink the climaintenance template.
$path = "{$CFG->dataroot}/climaintenance.template.html";
if (file_exists($path) || is_link($path)) {
    unlink($path);
}

symlink("{$CFG->dirroot}/theme/kent/pages/climaintenance.html", $path);

// Re-check nagios.
\local_nagios\Core::regenerate_list();

// Signal supervisord to restart.
$beanstalk = new \tool_adhoc\beanstalk();
$beanstalk->add_job('\\tool_adhoc\\jobs\\supervisord', 'restart');