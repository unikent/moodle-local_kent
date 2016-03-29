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
 * This file runs a few tests to make sure things
 * are working as they should be.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');

// Connect!
$ldapconn = ldap_connect("ldap.id.kent.ac.uk");
if (!$ldapconn) {
    cli_error("Cannot connect to LDAP.");
}

// Bind!
$ldapbind = ldap_bind($ldapconn);
if (!$ldapbind) {
    cli_error("Cannot bind to LDAP.");
}

cli_heading("Syncing user data with LDAP");

// Grab a list of users.
$users = $DB->get_recordset('user');
foreach ($users as $user) {
    if ($user->id <= 2) {
        continue;
    }

    $results = ldap_search($ldapconn, 'o=kent.ac.uk,o=uni', "(uid={$user->username})");
    $info = ldap_get_entries($ldapconn, $results);
    if (empty($info) || $info['count'] != 1) {
        continue;
    }

    $ldapuser = (object)$info[0];
    $unikentaccounttype = isset($ldapuser->unikentaccounttype) && $ldapuser->unikentaccounttype['count'] == 1 ? $ldapuser->unikentaccounttype[0] : '';
    $givenname = isset($ldapuser->givenname) && $ldapuser->givenname['count'] == 1 ? $ldapuser->givenname[0] : '';
    $sn = isset($ldapuser->sn) && $ldapuser->sn['count'] == 1 ? $ldapuser->sn[0] : '';

    if ($user->firstname !== $givenname || $user->lastname !== $sn) {
        cli_writeln("{$user->username} is {$givenname} {$sn}");

        $user->firstname = $givenname;
        $user->lastname = $sn;
        user_update_user($user, false, false);
    }
}
$users->close();
