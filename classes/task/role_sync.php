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

/**
 * Local stuff for Moodle Kent
 *
 * @package    local_kent
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\task;

/**
 * Checks cache.
 */
class role_sync extends \core\task\scheduled_task
{
    public function get_name() {
        return "Role Sync";
    }

    public function execute() {
        global $CFG, $SHAREDB;

        $enabled = get_config('local_kent', 'enable_role_sync');
        if (!$enabled) {
            return;
        }

        // What migration are we at?
        $migration = get_config('local_kent', 'role_migration');
        if (!$migration) {
            $migration = 1;
        }

        // What is the latest migration?
        $latest = \local_kent\shared\config::get('role_migration');

        // We done yet?
        if ($migration == $latest) {
            return true;
        }

        // Get all records between migration and the latest.
        $where = 'moodle_dist != :dist AND migration BETWEEN :val1 AND :val2';
        $migrations = $SHAREDB->get_records_select('shared_role_assignments', $where, array(
            'dist' => $CFG->kent->distribution,
            'val1' => $migration,
            'val2' => $latest
        ));

        // Run migrations.
        define('ROLESYNC_CRON_RUN', true);
        foreach ($migrations as $migration) {
            $this->migrate($migration);

            // Record now, in case we error.
            set_config('role_migration', $migration->migration, 'local_kent');
        }
        define('ROLESYNC_CRON_RUN', false);

        return true;
    }

    /**
     * Map a role to a shortname.
     */
    private function map_external_role($env, $dist, $id) {
        global $SHAREDB;

        static $map = array();
        if (empty($map)) {
            $records = $SHAREDB->get_records('shared_roles');

            foreach ($records as $record) {
                if (!isset($map[$record->moodle_env])) {
                    $map[$record->moodle_env] = array();
                }

                if (!isset($map[$record->moodle_env][$record->moodle_dist])) {
                    $map[$record->moodle_env][$record->moodle_dist] = array();
                }

                $map[$record->moodle_env][$record->moodle_dist][$record->roleid] = $record->shortname;
            }
        }

        if (isset($map[$env][$dist][$id])) {
            return $map[$env][$dist][$id];
        }

        return null;
    }

    /**
     * Grab's the ID for a given role.
     */
    private function map_internal_role($shortname) {
        global $DB;

        static $map = array();
        if (empty($map)) {
            $records = $DB->get_records('role');
            foreach ($records as $record) {
                $maps[$record->shortname] = $record->id;
            }
        }

        if (isset($maps[$shortname])) {
            return $maps[$shortname];
        }

        return null;
    }

    /**
     * Get a user ID for a username.
     */
    private function map_internal_username($username) {
        global $DB;

        static $cache = array();

        if (!isset($cache[$username])) {
            $cache[$username] = $DB->get_field('user', 'id', array(
                'username' => $username
            ));
        }

        return $cache[$username];
    }

    /**
     * Pull down and create a shared user.
     */
    private function grab_shared_user($username) {
        global $CFG, $SHAREDB;

        require_once($CFG->dirroot . "/user/lib.php");

        $user = $SHAREDB->get_record('shared_users', array(
            'username' => $username
        ));

        if ($user) {
            $user = \local_connect\user::get_user_object($user->username, $user->firstname, $user->lastname);
            try {
                return user_create_user($user, false);
            } catch (\Exception $e) {
                debugging($e->getMessage());
            }
        }

        return null;
    }

    /**
     * Run a migration.
     */
    private function migrate($migration) {
        $shortname = $this->map_external_role($migration->moodle_env, $migration->moodle_dist, $migration->roleid);
        $roleid = $this->map_internal_role($shortname);

        // Do we care?
        if (!$roleid || !\local_kent\role\manager::is_managed($roleid)) {
            return false;
        }

        // Map the username.
        $userid = $this->map_internal_username($migration->username);
        if (!$userid) {
            // Create the user.
            $userid = $this->grab_shared_user($migration->username);
            if (!$userid) {
                debugging("Unshared user encountered during migration: {$migration->username}");
                return false;
            }
        }

        // Get the context (one for now).
        $context = \context_system::instance();

        switch ($migration->action) {
            case 'add':
                if (!user_has_role_assignment($userid, $roleid, $context->id)) {
                    role_assign($roleid, $userid, $context->id);
                }
            break;

            case 'delete':
                if (user_has_role_assignment($userid, $roleid, $context->id)) {
                    role_unassign($roleid, $userid, $context->id);
                }
            break;

            default:
                debugging("Unknown migration action: {$migration->action}");
            break;
        }

        return true;
    }
} 