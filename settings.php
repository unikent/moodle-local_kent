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
    $ADMIN->add('reports', new admin_externalpage('reportsharedreport', 'ShareDB List',
        "$CFG->wwwroot/local/kent/reports/sharedb.php", 'local/connect:manage'));

    $settings->add(new admin_setting_configcheckbox(
        'local_kent/enable_session_cron',
        'Enable Session Cron',
        'Runs a cron once a night to clear out the Memcached slabs',
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

    $ADMIN->add('localplugins', $settings);

    $rolemgrsettings = new admin_settingpage('local_kent_role_mgr', 'Role Sync');

    if (!empty($CFG->kent->sharedb["host"])) {
        $rolemgrsettings->add(new admin_setting_configcheckbox(
            'local_kent/enable_role_sync',
            'Enable Role Synchronization',
            'Synchronizes roles between connected Moodle installations.',
            0
        ));

        if (get_config('local_kent', 'enable_role_sync')) {
            // Load roles.
            $choices = array();
            $systemcontext = \context_system::instance();
            $roles = get_assignable_roles(\context_system::instance(), ROLENAME_ALIAS, true);
            if (count($roles) == 3) {
                $roles = $roles[2];
                foreach ($roles as $id => $name) {
                    $choices[$id] = format_string($name);
                }
            }

            $rolemgrsettings->add(new admin_setting_configmultiselect(
                'local_kent/sync_roles',
                'Roles to synchronize',
                'Any roles selected here will be synchronized to all Moodles also configured to sync that role.',
                array(),
                $choices
            ));
        }
    }

    $ADMIN->add('roles', $rolemgrsettings);
}