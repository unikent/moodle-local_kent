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

defined('MOODLE_INTERNAL') || die;

global $SHAREDB, $SDSDB, $SITSDB;

require_once(dirname(__FILE__) . "/classes/util/sharedb.php");
$SHAREDB = new \local_kent\util\sharedb();

require_once(dirname(__FILE__) . "/../connect/classes/sds/sdsdb.php");
$SDSDB = new \local_connect\sds\sdsdb();

require_once(dirname(__FILE__) . "/../connect/classes/sits/sitsdb.php");
$SITSDB = new \local_connect\sits\sitsdb();

// Shared dataroot.
$CFG->shareddataroot = realpath($CFG->shareddataroot);
if ($CFG->shareddataroot === false) {
    if (function_exists('make_writable_directory')) {
        try {
            make_writable_directory($CFG->shareddataroot);
        } catch (\invalid_dataroot_permissions $e) {
            unset($CFG->shareddataroot);
        }
    } else {
        unset($CFG->shareddataroot);
    }
}

if (!PHPUNIT_TEST || PHPUNIT_UTIL) {
    set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
        global $CFG, $USER;

        // In case the error is with Splunk..
        if (error_reporting() > 0 && !isset($CFG->kent_error_handler_ran) && !empty(trim($errstr))) {
            $CFG->kent_error_handler_ran = true;

            // Splunk it.
            $splunkparams = array(
                'timecreated' => time(),
                'eventname' => 'phperror',
                'other' => serialize(array(
                    'errno' => $errno,
                    'errstr' => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline
                ))
            );

            if ($USER) {
                $splunkparams['userid'] = $USER->id;
                $splunkparams['realuserid'] = $USER->id;
                $splunkparams['relateduserid'] = $USER->id;
            }

            // Send to Splunk.
            \logstore_splunk\splunk::log_standardentry($splunkparams);
        }

        // Get Moodle's default error handler to handle this.
        return default_error_handler($errno, $errstr, $errfile, $errline, $errcontext);
    });
}

// Cool utility for CLI scripts.
if (defined("CLI_SCRIPT") && CLI_SCRIPT) {
    function cli_progress($done, $total) {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
        fwrite(STDERR, $write);
    }

    function cli_progress_end() {
        cli_writeln("", STDERR);
    }
}
