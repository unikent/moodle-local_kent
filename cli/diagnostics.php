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
 * This file runs a few tests to make sure things
 * are working as they should be.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Can we communicate with Memcached?
cli_heading("Testing Memcached");

$servers = array();

if ($CFG->kent->environment == 'live') {
    $servers[] = array('trove', 20001);
    $servers[] = array('trove', 20002);
    $servers[] = array('hoard', 20001);
    $servers[] = array('hoard', 20002);
}

if ($CFG->kent->environment == 'demo') {
    $servers[] = array('dump', 20004);
    $servers[] = array('dump', 20005);
}

if ($CFG->kent->environment == 'dev') {
    $servers[] = array('localhost', 11212);
    $servers[] = array('localhost', 11213);
}

foreach ($servers as $arr) {
    list($server, $port) = $arr;

    echo "  Testing $server:$port.\n";
    $cache = new \Memcached();
    $cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 200);
    $cache->setOption(\Memcached::OPT_SEND_TIMEOUT, 200);
    $cache->setOption(\Memcached::OPT_RECV_TIMEOUT, 200);
    $cache->setOption(\Memcached::OPT_POLL_TIMEOUT, 200);
    $cache->addServer($server, $port);

    if (!$cache->set("diag-ping", 1, time() + 2)) {
        cli_problem("Could not communicate with $server:$port!");
    } else {
        $cache->delete("diag-test");
    }

    $cache->quit();
}

echo "  Finished.\n";

// Can we select on the DB?
cli_heading("Testing DB");
try {
    if (!$DB->count_records('config')) {
        cli_problem("Could not communicate with DB!");
    }
} catch (\moodle_exception $e) {
    cli_problem("Could not communicate with DB:\n" . $e->getMessage());
}

echo "  Finished.\n";



// Are the Memcached clustering options set properly?
cli_heading("Testing Clustered Memcached");

$cache = new \Memcached(crc32("clustered-memcached"));
$cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 15);
$cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_SEND_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_RECV_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_POLL_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);

// Shift the first off.
list($server, $port) = reset($servers);
$cache->addServer($server, $port);
$cache->set('diag-test-cluster', 1);

// Add the second.
list($server, $port) = next($servers);
$cache->addServer($server, $port);

// Make sure this was 1.
if ($cache->get('diag-test-cluster') !== 1) {
    cli_problem("Error in Memcached cluster config - test #1");
}

$cache->set('diag-test-cluster', 2);
if ($cache->get('diag-test-cluster') !== 2) {
    cli_problem("Error in Memcached cluster config - test #2");
}

$cache->quit();


$cache = new \Memcached(crc32("clustered-memcached"));
$cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 15);
$cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_SEND_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_RECV_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_POLL_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 15);
$cache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);

// Shift the first off again.
list($server, $port) = reset($servers);
$cache->addServer($server, $port);
if ($cache->get('diag-test-cluster') !== 2) {
    cli_problem("Error in Memcached cluster config - test #3");
}

$cache->quit();


$cache = new \Memcached(crc32("clustered-memcached"));
$cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 15);
$cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_SEND_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_RECV_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_POLL_TIMEOUT, 200);
$cache->setOption(\Memcached::OPT_RETRY_TIMEOUT, 15);
$cache->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);

// Shift the first off again.
reset($servers);
list($server, $port) = next($servers);
$cache->addServer($server, $port);
if ($cache->get('diag-test-cluster') !== 2) {
    cli_problem("Error in Memcached cluster config - test #4");
}

$cache->quit();


echo "  Finished.\n";