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

namespace local_kent\role;

defined('MOODLE_INTERNAL') || die();

/**
 * Role Manager.
 */
class manager
{
    /**
     * Migrate the action up to SHAREDB.
     */
    public static function role_created($roleid, $userid) {
        global $CFG, $DB;

        if (defined("ROLESYNC_CRON_RUN") && ROLESYNC_CRON_RUN) {
            return true;
        }

        // Get the user.
        $username = $DB->get_field('user', 'username', array(
            'id' => $userid
        ));

        $params = array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleid' => $roleid,
            'username' => $username,
            'action' => 'created',
            'migration' => $migration
        );

        return true;
    }

    /**
     * Migrate the action up to SHAREDB.
     */
    public static function role_deleted($roleid, $userid) {
        global $CFG, $DB;

        if (defined("ROLESYNC_CRON_RUN") && ROLESYNC_CRON_RUN) {
            return true;
        }

        // Get the user.
        $username = $DB->get_field('user', 'username', array(
            'id' => $userid
        ));

        return true;
    }
}