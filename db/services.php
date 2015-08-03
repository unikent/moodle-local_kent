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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
    'Kent service' => array(
        'functions' => array (
            'local_kent_user_get_my_prefs',
            'local_kent_user_get_my_info_data',
            'local_kent_course_provision_fresh'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1
    )
);

$functions = array(
    'local_kent_user_get_my_prefs' => array(
        'classname'   => 'local_kent\external\user',
        'methodname'  => 'get_my_prefs',
        'description' => 'Get my preferences.',
        'type'        => 'read'
    ),
    'local_kent_user_get_my_info_data' => array(
        'classname'   => 'local_kent\external\user',
        'methodname'  => 'get_my_info_data',
        'description' => 'Get my info data.',
        'type'        => 'read'
    ),
    'local_kent_course_provision_fresh' => array(
        'classname'    => 'local_kent\external\course',
        'methodname'   => 'provision_fresh',
        'description'  => 'Provision a fresh, new course.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:update'
    )
);
