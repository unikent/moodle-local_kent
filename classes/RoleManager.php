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
 * Role Manager.
 */
class RoleManager
{
    private static $_managed_roles = array(
        'flt' => 'manager',
        'cla_admin' => null,
        'cla_viewer' => null,
        'academic_advisor' => 'teacher',
        'dep_admin' => 'editingteacher',
        'extexam' => 'teacher',
        'support_staff' => 'editingteacher',
        'sds_convenor' => 'editingteacher',
        'sds_teacher' => 'editingteacher',
        'sds_student' => 'student',
        'marker' => null,
        'panopto_academic' => null,
        'panopto_creator' => null,
        'panopto_non_academic' => null,
        'readinglist' => null,
        'support' => null
    );

    private static $_shared_roles = array(
        'system' => array(
            'cla_viewer',
            'flt',
            'cla_admin',
            'panopto_academic',
            'panopto_non_academic',
            'support'
        )/*,
        'coursecat' => array(
            'cla_viewer',
            'academic_advisor',
            'dep_admin',
            'extexam',
            'support_staff',
            'marker',
            'readinglist'
        )*/
    );

    /**
     * Configure all managed roles, call this from an upgrade script when
     * you change something.
     */
    public function configure() {
        foreach (static::$_managed_roles as $shortname => $archetype) {
            $this->install_or_update_role($shortname, $archetype);
        }
    }

    /**
     * Either update or install a managed role.
     */
    private function install_or_update_role($shortname, $archetype) {
        global $CFG, $DB;

        $xml = $CFG->dirroot . "/local/kent/db/roles/{$shortname}.xml";
        if (!file_exists($xml)) {
            debugging("'{$shortname}' is not a managed role!");
            return false;
        }

        $role = $DB->get_record('role', array(
            'shortname' => $shortname
        ));

        $xml = file_get_contents($xml);

        $options = array(
            'shortname'     => 1,
            'name'          => 1,
            'description'   => 1,
            'permissions'   => 1,
            'archetype'     => 1,
            'contextlevels' => 1,
            'allowassign'   => 1,
            'allowoverride' => 1,
            'allowswitch'   => 1
        );

        $definitiontable = new \local_kent\util\define_role_table_kent(\context_system::instance(), $role ? $role->id : 0);

        if ($archetype) {
            $definitiontable->force_archetype($archetype, $options);
        }

        $definitiontable->force_preset($xml, $options);
        $definitiontable->read_submitted_permissions();

        if (!$definitiontable->is_submission_valid()) {
            debugging("'{$shortname}' Not configured properly! Invalid definition.");
            return false;
        }

        $definitiontable->save_changes();
        return $definitiontable->get_role_id();
    }

    /**
     * Sync with SHAREDB.
     */
    public function sync() {
        global $CFG;

        $CFG->in_role_sync = true;
        foreach (static::$_shared_roles as $context => $roles) {
            foreach ($roles as $shortname) {
                $this->sync_role_type($context, $shortname);
            }
        }
        $CFG->in_role_sync = false;
    }

    /**
     * Sync a given context and shortname.
     */
    private function sync_role_type($contextlevel, $shortname) {
        global $DB, $SHAREDB;

        // Get all shared roles.
        $shared = $SHAREDB->get_records('shared_roles', array(
            'contextlevel' => $contextlevel,
            'shortname' => $shortname
        ));

        // Group by context.
        $contexts = array();
        foreach ($shared as $sharedrole) {
            if (!in_array($sharedrole->contextname, $contexts)) {
                $contexts[] = $sharedrole->contextname;
            }
        }

        foreach ($contexts as $contextname) {
            $this->sync_role_context($contextlevel, $contextname, $shortname);
        }
    }

    /**
     * Sync a given context and shortname.
     */
    private function sync_role_context($contextlevel, $contextname, $shortname) {
        global $DB, $SHAREDB;

        // Resolve local context.
        $context = $this->get_context($contextlevel, $contextname);
        if (!$context) {
            return;
        }

        // Resolve local role.
        $role = $DB->get_record('role', array('shortname' => $shortname));
        if (!$role) {
            return;
        }

        // Get all shared roles.
        $shared = $SHAREDB->get_records('shared_roles', array(
            'contextlevel' => $contextlevel,
            'contextname' => $contextname,
            'shortname' => $shortname
        ));

        $processed = array();
        foreach ($shared as $sharedrole) {
            // Grab user.
            $userid = $this->get_userid($sharedrole->username);
            if (!$userid) {
                continue;
            }

            // Ensure the enrolment exists!
            if (!user_has_role_assignment($userid, $role->id, $context->id)) {
                role_assign($role->id, $userid, $context->id);
            }

            $processed[] = $userid;
        }

        // Get all local role assignments.
        $local = $DB->get_records('role_assignments', array(
            'roleid' => $role->id,
            'contextid' => $context->id
        ));
        foreach ($local as $localra) {
            if (!in_array($localra->userid, $processed)) {
                if (user_has_role_assignment($localra->userid, $role->id, $context->id)) {
                    role_unassign($role->id, $localra->userid, $context->id);
                }
            }
        }
    }

    /**
     * Resolve shared context-isms.
     */
    private function get_context($contextlevel, $ident) {
        global $DB;

        if ($contextlevel == CONTEXT_SYSTEM) {
            return \context_system::instance();
        }

        if ($contextlevel == CONTEXT_COURSECAT) {
            $coursecat = $DB->get_record('course_categories', array(
                'idnumber' => $ident
            ));

            if ($coursecat) {
                return \context_coursecat::instance($coursecat->id);
            }
        }

        return null;
    }

    /**
     * Get a user ID for a username.
     */
    private function get_userid($username) {
        global $CFG, $DB, $SHAREDB;

        static $cache = array();

        if (!isset($cache[$username])) {
            $user = $DB->get_field('user', 'id', array(
                'username' => $username
            ));

            // User doesnt exist, try and create one.
            if (!$user) {
                require_once($CFG->dirroot . "/user/lib.php");
                $info = $SHAREDB->get_record('shared_users', array(
                    'username' => $username
                ));

                if ($info) {
                    $user = \local_connect\user::get_user_object($user->info, $user->info, $user->info);
                    $user = user_create_user($user, false);
                }
            }

            $cache[$username] = $user;
        }

        return $cache[$username];
    }

    /**
     * Migrate the action up to SHAREDB.
     */
    public static function role_created($roleid, $userid) {
        $rm = new static();
        return $rm->migrate('add', $roleid, $userid);
    }

    /**
     * Migrate the action up to SHAREDB.
     */
    public static function role_deleted($roleid, $userid) {
        $rm = new static();
        return $rm->migrate('delete', $roleid, $userid);
    }

    /**
     * Is the roleid in our sphere of care?
     */
    public function is_managed($shortname) {
        foreach (static::$_shared_roles as $context => $roles) {
            if (in_array($shortname, $roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Migrate a new role to SHAREDB.
     */
    private function migrate($action, $roleid, $userid) {
        global $CFG, $DB, $SHAREDB;

        if (isset($CFG->in_role_sync)) {
            return true;
        }

        // Get the role shortname.
        $shortname = $DB->get_field('role', 'shortname', array(
            'id' => $roleid
        ));
        if (!$this->is_managed($shortname)) {
            return true;
        }

        $this->share_role_mapping($roleid, $shortname);

        // Get the user.
        $user = $DB->get_record('user', array(
            'id' => $userid
        ));
        $this->share_user($user);

        // Update the migration.
        $migration = \local_kent\shared\config::increment("role_migration");

        $SHAREDB->insert_record('shared_role_assignments', array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleid' => $roleid,
            'username' => $user->username,
            'action' => $action,
            'migration' => $migration
        ));

        return true;
    }

    /**
     * Create mapping in SHAREDB for roleid.
     */
    private function share_role_mapping($roleid, $shortname) {
        global $CFG, $SHAREDB;

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
    }

    /**
     * Create record in SHAREDB for user.
     */
    private function share_user($user) {
        global $SHAREDB;

        if (!$SHAREDB->record_exists('shared_users', array('username' => $user->username))) {
            $SHAREDB->insert_record('shared_users', array(
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname
            ));
        }
    }
}