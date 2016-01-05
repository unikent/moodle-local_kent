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

/**
 * Post deploy hooks.
 * This is run as w3moodle (magic!).
 */

// Re-symlink the climaintenance template.
$path = "{$CFG->dataroot}/climaintenance.template.html";
if (file_exists($path)) {
    unlink($path);
}

symlink("{$CFG->dirroot}/theme/kent/pages/climaintenance.html", $path);