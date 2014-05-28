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

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_session_cron',
        'Enable Session Cron',
        'Runs a cron once a night to clear out the Memcached slabs',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_cache_shouter',
        'Enable Cache Shouter',
        'Periodically checks cache definitions and shouts at HipChat if there is a problem.',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_config_shouter',
        'Enable Config Shouter',
        'Shouts out config modifications to HipChat',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_course_shouter',
        'Enable Course Shouter',
        'Shouts out new courses to HipChat.',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_course_alt_shouter',
        'Enable Course Shouter',
        'Shouts out new courses to Academic Liason Team.',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_log_buffer',
        'Enable Log Buffering',
        'Buffers log writes to a temporary table',
        0
    ));

    $ADMIN->add('localplugins', $settings);
}