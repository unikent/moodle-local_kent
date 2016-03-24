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

namespace local_kent\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Role Manager.
 */
class role
{
    private static $managedroles = array(
        'flt' => 'manager',
        'cla_admin' => null,
        'cla_viewer' => null,
        'academic_advisor' => 'teacher',
        'dep_admin' => 'editingteacher',
        'extexam' => 'teacher',
        'support_staff' => 'editingteacher',
        'convenor' => 'editingteacher',
        'sds_convenor' => 'editingteacher',
        'sds_teacher' => 'editingteacher',
        'sds_student' => 'student',
        'editingteacher' => 'editingteacher',
        'teacher' => 'teacher',
        'student' => 'student',
        'is_helpdesk' => 'user',
        'marker' => null,
        'panopto_creator' => null,
        'readinglist' => null,
        'support' => null,
        'restwsu' => null
    );

    private static $sharedroles = array(
        \CONTEXT_SYSTEM => array(
            'cla_viewer',
            'flt',
            'cla_admin',
            'support',
            'is_helpdesk'
        ),
        \CONTEXT_COURSECAT => array(
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
     * Grab managed roles.
     */
    public static function get_managed_roles() {
        return array_keys(static::$managedroles);
    }

    /**
     * Configure all managed roles, call this from an upgrade script when
     * you change something.
     * @param string $role
     */
    public function configure($role = 'all') {
        if ($role != 'all') {
            if (!in_array($role, array_keys(static::$managedroles))) {
                throw new \coding_exception("Invalid role '{$role}'!");
            }

            $this->install_or_update_role($role, static::$managedroles[$role]);
            return;
        }

        foreach (static::$managedroles as $shortname => $archetype) {
            $this->install_or_update_role($shortname, $archetype);
        }
    }

    /**
     * Grab roles.
     */
    protected function grab_roles($roles) {
        global $DB;

        if ($roles == '*') {
            return $DB->get_records('role', null, '', 'id');
        }

        if (is_array($roles)) {
            list($sql, $params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'shortname');
            return $DB->get_records_select('role', 'shortname '. $sql, $params, '', 'id');
        }

        return $DB->get_records('role', array(
            'shortname' => $roles
        ), '', 'id');
    }

    /**
     * Allow role assignments.
     */
    public function allow_assign($target, $roles) {
        global $DB;

        $roles = $this->grab_roles($roles);
        $target = $this->grab_roles($target);
        $target = reset($target);

        foreach ($roles as $role) {
            if (!$DB->record_exists('role_allow_assign', array('roleid' => $role->id, 'allowassign' => $target->id))) {
                allow_assign($role->id, $target->id);
            }
        }
    }

    /**
     * Helper for add_capability and remove_capability.
     * @param $capability
     * @param $permission
     * @param $roles
     * @throws \Exception
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function set_capability($capability, $permission, $roles) {
        global $DB;

        $context = \context_system::instance();
        $roles = $this->grab_roles($roles);
        foreach ($roles as $role) {
            if (empty($permission)) {
                unassign_capability($capability, $role->id);
            } else {
                assign_capability($capability, $permission, $role->id, $context->id, true);
            }
        }
    }

    /**
     * Add a capability to a role (or roles).
     * @param $capability
     * @param string $roles
     */
    public function add_capability($capability, $roles = "*") {
        $this->set_capability($capability, \CAP_ALLOW, $roles);
    }

    /**
     * Remove a capability from a role (or roles).
     * @param $capability
     * @param string $roles
     */
    public function remove_capability($capability, $roles = "*") {
        $this->set_capability($capability, '', $roles);
    }

    /**
     * Either update or install a managed role.
     * @param $shortname
     * @param $archetype
     * @return bool
     * @throws \Exception
     * @throws \coding_exception
     * @throws \dml_exception
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
     * Grab all shared roles.
     */
    public function get_shared() {
        return static::$sharedroles;
    }

    /**
     * Is the roleid in our sphere of care?
     *
     * @param $shortname
     * @param null $contextlevel
     * @return bool
     */
    public function is_shared($shortname, $contextlevel = null) {
        foreach (static::$sharedroles as $ctxlevel => $roles) {
            if ($contextlevel && $contextlevel != $ctxlevel) {
                continue;
            }

            if (in_array($shortname, $roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a user ID for a username.
     * @param $username
     * @return
     * @throws \moodle_exception
     */
    public function get_userid($username) {
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

                if (!$info) {
                    $info = new \stdClass();
                    $info->username = $username;
                    $info->firstname = 'Unknown';
                    $info->lastname = 'Unknown';
                }

                $user = \local_connect\user::get_user_object($info->username, $info->firstname, $info->lastname);
                $user = user_create_user($user, false);
            }

            $cache[$username] = $user;
        }

        return $cache[$username];
    }

    /**
     * Create record in SHAREDB for user.
     * @param $user
     */
    private function share_user(\stdClass $user) {
        global $SHAREDB;

        if (!$SHAREDB->record_exists('shared_users', array('username' => $user->username))) {
            $SHAREDB->insert_record('shared_users', array(
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname
            ));
        }
    }

    /**
     * Returns a list of local enrolments within a given context.
     * Returns a mapped list ready for SHAREDB (shortname, username).
     * @param $ctx
     * @return array
     */
    public function get_local_enrolments_context(\context $ctx) {
        global $DB;

        return $DB->get_records_sql('
            SELECT ra.id, r.shortname, u.username
            FROM {role_assignments} ra
            INNER JOIN {role} r
                ON r.id = ra.roleid
            INNER JOIN {user} u
                ON u.id = ra.userid
            WHERE ra.contextid = :contextid
        ', array(
            'contextid' => $ctx->id
        ));
    }
}
