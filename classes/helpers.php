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

namespace local_kent;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper functions.
 */
class helpers
{
    /**
     * Run a script on another Moodle.
     *
     * @param string $moodleinstallation The name of the installation to execute the script against (e.g. 2015).
     * @param string $script The relative script name (e.g. /admin/cli/upgrade.php).
     * @param array $args An array of arguments (note: they will be escaped by escapeshellcmd).
     */
    public static function execute_script_on($moodleinstallation, $script, $args = []) {
        global $CFG;

        if (defined("PHPUNIT_TEST") && PHPUNIT_TEST) {
            return true;
        }

        $moodleinstallation = escapeshellcmd($moodleinstallation);
        $script = escapeshellcmd($script);
        $args = array_map('escapeshellcmd', $args);
        $args = implode(' ', $args);

        $dirroot = '/var/www/vhosts/' . KENT_VHOST . '/public/' . $moodleinstallation;
        if (!file_exists($dirroot)) {
            throw new \moodle_exception("Cannot find Moodle installation {$moodleinstallation}.");
        }

        $filename = $dirroot . $script;
        if (!file_exists($dirroot)) {
            throw new \moodle_exception("Cannot find script {$filename}.");
        }

        $ret = '';
        passthru("/usr/bin/php {$filename} {$args}", $ret);

        return $ret;
    }

    /**
     * Returns a Moodle-in-Moodle DB.
     */
    public static function get_db($env, $dist) {
        global $CFG;

        if (!isset($CFG->dbcfg[$env]) || !isset($CFG->dbcfg[$env][$dist])) {
            return null;
        }

        // Get MIM config.
        $mimcfg = $CFG->dbcfg[$env][$dist];

        static $dbs = array();
        if (!isset($dbs["{$env}_{$dist}"])) {
            if (!$db = \moodle_database::get_driver_instance($mimcfg->dbtype, $mimcfg->dblibrary, true)) {
                throw new \dml_exception('dbdriverproblem', "Unknown driver for kent");
            }

            $db->connect(
                $mimcfg->dbhost,
                $mimcfg->dbuser,
                $mimcfg->dbpass,
                $mimcfg->prefix,
                $mimcfg->dboptions
            );

            $dbs["{$env}_{$dist}"] = $db;
        }

        return $dbs["{$env}_{$dist}"];
    }

    /**
     * Set a cli user.
     */
    public static function cli_set_user() {
        global $DB;

        $username = exec('logname');
        $user = $DB->get_record('user', array('username' => $username));
        if (!$user) {
            $user = get_admin();
        }

        \core\session\manager::set_user($user);
    }
}
