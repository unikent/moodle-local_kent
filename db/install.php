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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_kent_install() {
    global $CFG, $DB;

    // Not if we are installing a phpunit test site.
    if (defined("PHPUNIT_UTIL") && PHPUNIT_UTIL) {
        return true;
    }

    // Not if we are installing a behat test site.
    if (defined("BEHAT_UTIL") && BEHAT_UTIL) {
        return true;
    }

    $dbman = $DB->get_manager();

    // Configure to Kent defaults.
    $configman = new \local_kent\ConfigManager();
    $configman->configure();

    // Configure scheduled tasks to Kent defaults.
    $taskman = new \local_kent\TaskManager();
    $taskman->configure();

    // Create basic categories.
    $localcatmap = array();

    global $kentcategories;
    require(dirname(__FILE__) . "/categories.php");

    while (!empty($kentcategories)) {
        foreach ($kentcategories as $category) {
            $category = (object)$category;

            if ($category->parent > 1) {
                if (!isset($localcatmap[$category->parent])) {
                    continue;
                }

                $category->parent = $localcatmap[$category->parent];
            }

            if (empty($category->idnumber)) {
                $category->idnumber = $category->id;
            }

            $coursecat = \coursecat::create($category);
            $localcatmap[$category->id] = $coursecat->id;

            unset($kentcategories[$category->id]);
        }
    }

    // Define index ip (not unique) to be added to logstore_standard_log.
    $table = new xmldb_table('logstore_standard_log');
    $index = new xmldb_index('ip', XMLDB_INDEX_NOTUNIQUE, array('ip'));

    // Conditionally launch add index ip.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index eventname (not unique) to be added to logstore_standard_log.
    $table = new xmldb_table('logstore_standard_log');
    $index = new xmldb_index('eventname', XMLDB_INDEX_NOTUNIQUE, array('eventname'));

    // Conditionally launch add index eventname.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index context (not unique) to be added to logstore_standard_log.
    $table = new xmldb_table('logstore_standard_log');
    $index = new xmldb_index('context', XMLDB_INDEX_NOTUNIQUE, array('contextlevel', 'contextinstanceid'));

    // Conditionally launch add index context.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index userid (not unique) to be added to logstore_standard_log.
    $table = new xmldb_table('logstore_standard_log');
    $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

    // Conditionally launch add index userid.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index relateduserid (not unique) to be added to logstore_standard_log.
    $table = new xmldb_table('logstore_standard_log');
    $index = new xmldb_index('relateduserid', XMLDB_INDEX_NOTUNIQUE, array('relateduserid'));

    // Conditionally launch add index relateduserid.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define index enablecompletion (not unique) to be added to course.
    $table = new xmldb_table('course');
    $index = new xmldb_index('enablecompletion', XMLDB_INDEX_NOTUNIQUE, array('enablecompletion'));

    // Conditionally launch add index enablecompletion.
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    return true;
}
