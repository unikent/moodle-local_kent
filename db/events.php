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
 * Local stuff for Kent
 *
 * @package    local_kent
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array (

    array (
        'eventname' => '\core\event\course_created',
        'callback' => '\local_kent\observers::course_created',
    ),

    array (
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_kent\observers::user_enrolment_created',
    ),

    array (
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_kent\observers::user_updated',
    ),

    array (
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_kent\observers::course_deleted',
    ),

    array (
        'eventname' => '\local_catman\event\course_purged',
        'callback' => '\local_kent\observers::course_purged',
    ),

    array (
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\local_kent\observers::user_enrolment_deleted',
    ),

    array (
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_kent\observers::role_assigned',
    ),

    array (
        'eventname' => '\core\event\role_unassigned',
        'callback'  => '\local_kent\observers::role_unassigned',
    ),

    array (
        'eventname' => '\local_rollover\event\rollover_started',
        'callback'  => '\local_kent\observers::rollover_started',
    ),

    array (
        'eventname' => '\local_rollover\event\rollover_finished',
        'callback'  => '\local_kent\observers::rollover_finished',
    ),

    array (
        'eventname' => '\local_rollover\event\rollover_error',
        'callback'  => '\local_kent\observers::rollover_error',
    )
);