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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_kent', get_string('pluginname', 'local_kent'));

    $settings->add(new admin_setting_configtext(
        'local_kent/enable_session_cron',
        'Enable Session Cron',
        'Runs a cron once a night to clear out the Memcached slabs',
        1,
        PARAM_BOOL
    ));

    $settings->add(new admin_setting_configtext(
        'local_kent/enable_memcached_shouter',
        'Enable Memcached Shouter',
        'Periodically checks Memcached and shouts at HipChat if there is a problem.',
        1,
        PARAM_BOOL
    ));

    $settings->add(new admin_setting_configtext(
        'local_kent/enable_config_shouter',
        'Enable Config Shouter',
        'Shouts out config modifications to HipChat',
        1,
        PARAM_BOOL
    ));

    $settings->add(new admin_setting_configtext(
        'local_kent/enable_course_shouter',
        'Enable Course Shouter',
        'Shouts out new courses to HipChat',
        0,
        PARAM_BOOL
    ));

    $ADMIN->add('localplugins', $settings);
}