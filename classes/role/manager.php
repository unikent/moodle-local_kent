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
        return self::migrate('add', $roleid, $userid);
    }

    /**
     * Migrate the action up to SHAREDB.
     */
    public static function role_deleted($roleid, $userid) {
        return self::migrate('delete', $roleid, $userid);
    }

    /**
     * Is the roleid in our sphere of care?
     */
    public static function is_managed($roleid) {
        $config = get_config('local_kent', 'sync_roles');
        $ids = explode(',', $config);

        return in_array($roleid, $ids);
    }

    /**
     * Helper for the above.
     */
    private static function migrate($action, $roleid, $userid) {
        global $CFG, $DB, $SHAREDB;

        if (defined("ROLESYNC_CRON_RUN") && ROLESYNC_CRON_RUN) {
            return true;
        }

        if (!self::is_managed($roleid)) {
            return true;
        }

        // Get the role shortname.
        $shortname = $DB->get_field('role', 'shortname', array(
            'id' => $roleid
        ));

        // Check the role exists in the mapping table #1.
        $params = array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleid' => $roleid
        );
        $sharedrole = $SHAREDB->get_record('shared_roles', $params);
        $params['shortname'] = $shortname;

        // Check the role exists in the mapping table #2.
        if (!$sharedrole) {
            $SHAREDB->insert_record('shared_roles', $params);
        } else {
            // Does it need updating?
            if ($sharedrole->shortname != $shortname) {
                $params['id'] = $sharedrole->id;
                $SHAREDB->update_record('shared_roles', $params);
            }
        }

        // Get the user.
        $username = $DB->get_field('user', 'username', array(
            'id' => $userid
        ));

        // Update the migration.
        $migration = \local_kent\shared\config::increment("role_migration");

        $SHAREDB->insert_record('shared_role_assignments', array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleid' => $roleid,
            'username' => $username,
            'action' => $action,
            'migration' => $migration
        ));

        return true;
    }
}