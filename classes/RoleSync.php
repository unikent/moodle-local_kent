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
 * Role Sync stuff
 */
class RoleSync
{
    /**
     * Run the shouter cron.
     */
    public static function cron() {
        $engine = new static();

        if (get_config("local_kent", "sync_panopto")) {
            $engine->sync_panopto();
        }

        if (get_config("local_kent", "sync_helpdesk")) {
            $engine->sync_helpdesk();
        }

        if (get_config("local_kent", "sync_cla")) {
            $engine->sync_cla();
        }

        if (get_config("local_kent", "sync_category_admins")) {
            $engine->sync_category_admins();
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
    private function push_up($roleid, $contextid) {
        global $CFG, $DB, $SHAREDB;

        // Grab users.
        $users = $DB->get_fieldset_sql("SELECT u.username
            FROM {role_assignments} ra
            INNER JOIN {user} u
              ON u.id=ra.userid
            WHERE ra.roleid = :roleid AND ra.contextid = :contextid
            GROUP BY u.username
        ", array(
            "roleid" => $roleid,
            "contextid" => $contextid
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
     * Pull a given set of roles from SHAREDB.
     */
    private function pull_down($roleid, $context) {

    }

    /**
     * Sync panopto role between connected Moodle installations
     */
    private function sync_panopto() {
        $context = \context_system::instance();

        $roleid = $this->get_role_id('panopto_academic');
        $this->push_up($roleid, $context->id);
        $this->pull_down($roleid, $context->id);

        $roleid = $this->get_role_id('panopto_non_academic');
        $this->push_up($roleid, $context->id);
        $this->pull_down($roleid, $context->id);
    }

    /**
     * Sync helpdesk role between connected Moodle installations
     */
    private function sync_helpdesk() {

    }

    /**
     * Sync cla role between connected Moodle installations
     */
    private function sync_cla() {

    }

    /**
     * Sync category_admins role between connected Moodle installations
     */
    private function sync_category_admins() {

    }

}