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

$tasks = array(
    array(
        'classname' => 'local_kent\task\config_shouter',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_kent\task\course_shouter',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_kent\task\course_sharedb_sync',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '1',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_kent\task\course_contact_preload',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_kent\task\geoip_sync',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => 'R',
        'day' => 'R',
        'dayofweek' => '*',
        'month' => '*'
    )
);
