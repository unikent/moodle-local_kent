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

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$roles = \local_kent\manager\role::get_managed_roles();
foreach ($roles as $role) {
    $role = $DB->get_record('role', array('shortname' => $role), 'id,shortname', MUST_EXIST);
    $xml = core_role_preset::get_export_xml($role->id);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml);
    $dom->formatOutput = true;
    $dom->save(dirname(__FILE__) . "/../db/roles/{$role->shortname}.xml");
}
