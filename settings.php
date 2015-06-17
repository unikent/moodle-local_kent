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
    $ADMIN->add('reports', new admin_externalpage(
        'reportsharedreport',
        'ShareDB List',
        "$CFG->wwwroot/local/kent/reports/sharedb.php",
        'local/connect:manage'
    ));

    $settings = new admin_settingpage('local_kent', get_string('pluginname', 'local_kent'));
    $ADMIN->add('localplugins', $settings);

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
        'local_kent/enable_footprints',
        'Enable Footprints',
        'Enable Footprints tickets to be sent.',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_role_sync',
        'Enable Role Synchronization',
        'Synchronizes roles between connected Moodle installations.',
        0
    ));
}
