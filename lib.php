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
 * Local lib code
 *
 * @package    local_kent
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Inject the GA code for every request.
if ((!defined("CLI_SCRIPT") || (defined("CLI_SCRIPT") && !CLI_SCRIPT)) &&
    (!defined("AJAX_SCRIPT") || (defined("AJAX_SCRIPT") && !AJAX_SCRIPT)) &&
    (!defined("NO_MOODLE_COOKIES") || (defined("NO_MOODLE_COOKIES") && !NO_MOODLE_COOKIES)) && (
        (!defined('ABORT_AFTER_CONFIG') || !ABORT_AFTER_CONFIG) ||
        (defined('ABORT_AFTER_CONFIG_CANCEL') && ABORT_AFTER_CONFIG_CANCEL)
    )) {
    \local_kent\GA::inject();
}