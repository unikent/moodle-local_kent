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

// Force OPcache reset if used, we do not want any stale caches
// when detecting if upgrade necessary or when running upgrade.
if (function_exists('opcache_reset') and !isset($_SERVER['REMOTE_ADDR'])) {
    opcache_reset();
}

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->libdir . '/environmentlib.php');

$user = posix_getpwuid(posix_geteuid());
if ($user['name'] !== 'w3moodle') {
    die("This script must be run as w3moodle.");
}

// Check this isn't a brand new installation!
if (!core_tables_exist()) {
    die("No core tables, will exit.");
}

/*
 * Post deploy hooks.
 * This is run as w3moodle (magic!).
 */

// Re-symlink the climaintenance template.
$path = "{$CFG->dataroot}/climaintenance.template.html";
if (file_exists($path) || is_link($path)) {
    unlink($path);
}

symlink("{$CFG->dirroot}/theme/kent/pages/climaintenance.html", $path);

 // Reset caches.
 cache_helper::purge_all(true);
 purge_all_caches();

// Check for any upgrades.
if (moodle_needs_upgrading()) {
    cli_writeln("Moodle {$CFG->kent->distribution} needs upgrading!");

    require("$CFG->dirroot/version.php");

    // Environment checks.
    list($envstatus, $environment_results) = check_moodle_environment(normalize_version($release), ENV_SELECT_RELEASE);
    if (!$envstatus || !core_plugin_manager::instance()->all_plugins_ok($version, $failed) || (
        isset($maturity) && $maturity < MATURITY_STABLE && $CFG->kent->distribution !== 'future' && $CFG->kent->distribution !== 'future-demo')) {
        cli_error("Bad deploy! Not upgrading.");
    }

    // Upgrade core.
    if ($version > $CFG->version) {
        upgrade_core($version, true);
        set_config('release', $release);
        set_config('branch', $branch);
    }

    // Upgrade non-core plugins.
    upgrade_noncore(true);

    // Log in as admin - we need doanything permission when applying defaults.
    \core\session\manager::set_user(get_admin());

    // Apply all default settings, just in case do it twice to fill all defaults.
    admin_apply_default_settings(NULL, false);
    admin_apply_default_settings(NULL, false);

    // Make sure we are ok now.
    cache_helper::purge_all(true);
    if (moodle_needs_upgrading()) {
        cli_writeln("Moodle {$CFG->kent->distribution} still needs upgrading!");
    }
}

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

// Re-check nagios.
\local_nagios\Core::regenerate_list();

// Re-generate tutorials list.
\local_tutorials\loader::update();
