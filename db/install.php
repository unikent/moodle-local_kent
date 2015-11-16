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
    global $DB, $SHAREDB;

    // Install SHAREDB if needs be.
    if (\local_kent\util\sharedb::available()) {
        $sharedbman = $SHAREDB->get_manager();
        $table = new xmldb_table("shared_courses");
        if (!$sharedbman->table_exists($table)) {
            $sharedbman->install_from_xmldb_file(dirname(__FILE__) . '/sharedb.xml');
        }
    }

    // Not if we are installing a phpunit test site.
    if (defined("PHPUNIT_UTIL") && PHPUNIT_UTIL) {
        return true;
    }

    // Not if we are installing a behat test site.
    if (defined("BEHAT_UTIL") && BEHAT_UTIL) {
        return true;
    }

    $dbman = $DB->get_manager();

    // Configure Kent managed roles.
    $roleman = new \local_kent\manager\role();
    $roleman->configure();

    // Configure to Kent defaults.
    $configman = new \local_kent\manager\config();
    $configman->configure();

    // Configure scheduled tasks to Kent defaults.
    $taskman = new \local_kent\manager\task();
    $taskman->configure();

    // Create basic categories.
    $catman = \local_kent\manager\category::instance();
    $catman->install();

    return true;
}
