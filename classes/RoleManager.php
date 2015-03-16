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
        ),
        'coursecat' => array(
            'cla_viewer',
            'academic_advisor',
            'dep_admin',
            'extexam',
            'support_staff',
            'marker',
            'readinglist'
        )
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