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
 * Generate cohorts based on LDAP attrs.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

$context = \context_system::instance();
$cohorts = cohort_get_cohorts($context->id, 0, 1000);
$cohortids = array();
foreach ($cohorts['cohorts'] as $cohort) {
    $cohortids[$cohort->idnumber] = $cohort->id;
}

if (!isset($cohortids['staff'])) {
    $cohortids['staff'] = cohort_add_cohort((object)array(
        'idnumber' => 'staff',
        'name' => 'Staff',
        'description' => 'University of Kent staff',
        'contextid' => $context->id,
        'component' => 'auth_kentsaml'
    ));
}

if (!isset($cohortids['students'])) {
    $cohortids['students'] = cohort_add_cohort((object)array(
        'idnumber' => 'students',
        'name' => 'Students',
        'description' => 'University of Kent students',
        'contextid' => $context->id,
        'component' => 'auth_kentsaml'
    ));
}

$users = $DB->get_records('user');
foreach ($users as $user) {
    $infodata = (object)\local_kent\User::get_all_infodata($user->id);
    if (!empty($infodata->kentacctype)) {
        if (!isset($cohortids[$infodata->kentacctype])) {
            $cohortids[$infodata->kentacctype] = cohort_add_cohort((object)array(
                'idnumber' => $infodata->kentacctype,
                'name' => $infodata->kentacctype,
                'contextid' => $context->id,
                'component' => 'auth_kentsaml'
            ));
        }

        if (!cohort_is_member($cohortids[$infodata->kentacctype], $user->id)) {
            cohort_add_member($cohortids[$infodata->kentacctype], $user->id);
        }
    }

    if (!empty($user->department)) {
        if (!isset($cohortids[$user->department])) {
            $cohortids[$user->department] = cohort_add_cohort((object)array(
                'idnumber' => $user->department,
                'name' => $user->department,
                'contextid' => $context->id,
                'component' => 'auth_kentsaml'
            ));
        }

        if (!cohort_is_member($cohortids[$user->department], $user->id)) {
            cohort_add_member($cohortids[$user->department], $user->id);
        }
    }
}
