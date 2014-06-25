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
        $enabled = get_config("local_kent", "enable_role_sync");
        if (!$enabled) {
            return;
        }

        if (get_config("local_kent", "sync_panopto")) {
            $this->sync_panopto();
        }

        if (get_config("local_kent", "sync_helpdesk")) {
            $this->sync_helpdesk();
        }

        if (get_config("local_kent", "sync_cla")) {
            $this->sync_cla();
        }
    }

    /**
     * Grab's the ID for a given role.
     */
    private function get_role_id($shortname) {
        global $DB;

        return $DB->get_field('role', 'id', array(
            'shortname' => $shortname
        ));
    }

    /**
     * Push a given set of roles up to SHAREDB.
     */
    private function push_up($roleid) {
        global $CFG, $DB, $SHAREDB;

        // Grab users.
        $users = $DB->get_fieldset_sql("SELECT u.username
            FROM {role_assignments} ra
            INNER JOIN {user} u
              ON u.id=ra.userid
            WHERE ra.roleid = :roleid
            GROUP BY u.username
        ", array(
            "roleid" => $roleid
        ));

        $role = $DB->get_record('role', array(
            'id' => $roleid
        ), 'id, shortname');

        $params = array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleid' => $role->id,
            'shortname' => $role->shortname
        );

        // Sync the role itself.
        if (!$SHAREDB->record_exists('shared_roles', $params)) {
            $SHAREDB->insert_record('shared_roles', $params);
        }

        $shareid = $SHAREDB->get_field('shared_roles', 'id', $params);

        $params = array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            "roleid" => $shareid
        );

        // And the assignments for this context.
        $SHAREDB->delete_records("shared_role_assignments", $params);
        foreach ($users as $username) {
            $params['username'] = $username;
            $SHAREDB->insert_record("shared_role_assignments", $params);
        }
    }

    /**
     * Get a user ID for a username.
     */
    private function get_user($username) {
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
     * Pull a given set of roles from SHAREDB.
     */
    private function pull_down($roleid, $shortname) {
        global $CFG, $DB, $SHAREDB;

        $context = \context_system::instance();

        // Grab a list of enrolments, merge between installations.
        $records = $SHAREDB->get_records_sql("
            SELECT sra.id, sra.username, sr.shortname
            FROM {shared_role_assignments} sra
            INNER JOIN {shared_roles} sr
              ON sr.id=sra.roleid
            WHERE
              CONCAT(sra.moodle_env, '_', sra.moodle_dist) <> CONCAT(:moodle_env, '_', :moodle_dist)
              AND sr.shortname = :roleshortname
            GROUP BY sr.shortname, sra.username
        ", array(
            'moodle_env' => $CFG->kent->environment,
            'moodle_dist' => $CFG->kent->distribution,
            'roleshortname' => $shortname
        ));

        foreach ($records as $record) {
            $userid = $this->get_user($record->username);
            if ($userid && !user_has_role_assignment($userid, $roleid, $context->id)) {
                role_assign($roleid, $userid, $context->id);
            }
        }
    }

    /**
     * Sync panopto role between connected Moodle installations
     */
    private function sync_panopto() {
        $roleid = $this->get_role_id('panopto_academic');
        if ($roleid) {
            $this->push_up($roleid);
            $this->pull_down($roleid, 'panopto_academic');
        }

        $roleid = $this->get_role_id('panopto_non_academic');
        if ($roleid) {
            $this->push_up($roleid);
            $this->pull_down($roleid, 'panopto_non_academic');
        }
    }

    /**
     * Sync helpdesk role between connected Moodle installations
     */
    private function sync_helpdesk() {
        $roleid = $this->get_role_id('support');
        if ($roleid) {
            $this->push_up($roleid);
            $this->pull_down($roleid, 'support');
        }
    }

    /**
     * Sync cla role between connected Moodle installations
     */
    private function sync_cla() {
        $roleid = $this->get_role_id('cla_admin');
        if ($roleid) {
            $this->push_up($roleid);
            $this->pull_down($roleid, 'cla_admin');
        }
    }
} 