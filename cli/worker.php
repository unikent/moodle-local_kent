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
 * Heterogeneous worker.
 *
 * @package    local_kent
 * @author     Skylar Kelty <S.Kelty@kent.ac.uk>
 * @copyright  2016 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(ticks = 1);

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once("{$CFG->libdir}/clilib.php");
require_once("{$CFG->libdir}/cronlib.php");

$CFG->sighup = false;

// Install custom signal handler.
if (function_exists('pcntl_signal')) {
    function local_kent_worker_sig_handler($sig) {
        global $CFG;
        $CFG->sighup = true;
    }

    pcntl_signal(SIGINT,  'local_kent_worker_sig_handler');
    pcntl_signal(SIGTERM, 'local_kent_worker_sig_handler');
    pcntl_signal(SIGHUP,  'local_kent_worker_sig_handler');
}

// Start watching tubes.
$beanstalk = new \queue_beanstalk\queue();
foreach ($CFG->kent->paths as $moodle => $path) {
    $beanstalk->watch("moodle-{$moodle}-tasks");
}

while (!$CFG->sighup) {
    foreach ($CFG->kent->paths as $moodle => $path) {
        if ($CFG->sighup) {
            continue;
        }

        // Prefer the live Moodle.
        $timeout = 1;
        if ($CFG->kent->distribution == LIVE_MOODLE) {
            $timeout = 5;
        }

        // Reserve a job.
        $job = $beanstalk->reserveFromTube("moodle-{$moodle}-tasks", $timeout);
        if (!$job) {
            continue;
        }

        // Decode and check data.
        $received = json_decode($job->getData());
        if (!isset($received->id)) {
            cli_writeln('Received invalid job: ' . json_encode($received));
            $beanstalk->delete($job);

            continue;
        }

        // Delete this job.
        $beanstalk->delete($job);

        // Run this separately from this process.
        $phppath = isset($CFG->phppath) ? $CFG->phppath : '/usr/bin/php';
        $script = $path . 'admin/tool/adhoc/cli/manage.php --execute=' . escapeshellcmd($received->id);
        passthru("{$phppath} {$script}", $ret);
    }
}
