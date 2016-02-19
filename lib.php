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
 * Local lib code
 *
 * @package    local_kent
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Inject the GA code for every request.
if ((!defined("CLI_SCRIPT") || !CLI_SCRIPT) &&
    (!defined("AJAX_SCRIPT") || !AJAX_SCRIPT) &&
    (!defined("NO_MOODLE_COOKIES") || !NO_MOODLE_COOKIES) && (
        (!defined('ABORT_AFTER_CONFIG') || !ABORT_AFTER_CONFIG) ||
        (defined('ABORT_AFTER_CONFIG_CANCEL') && ABORT_AFTER_CONFIG_CANCEL)
    )) {
    \local_kent\GA::inject();
}

/**
 * Adds a Tii report to the category menu.
 *
 * @param  settings_navigation $nav     Nav menu
 * @param  context             $context Context of the menu
 * @return navigation_node              A new navigation mode to insert.
 */
function local_kent_extend_settings_navigation(\settings_navigation $nav, \context $context) {
    global $PAGE;

    if ($context->contextlevel != \CONTEXT_COURSECAT) {
        return;
    }

    // Check we can view the Tii report.
    if (!has_capability('report/turnitin:view', $context)) {
        return null;
    }

    $url = new \moodle_url('/report/turnitin/index.php', array(
        'category' => $context->instanceid
    ));

    $pluginname = get_string('pluginname', 'report_turnitin');
    $node = \navigation_node::create(
        $pluginname,
        $url,
        \navigation_node::NODETYPE_LEAF,
        'report_turnitin',
        'report_turnitin',
        new \pix_icon('e/document_properties', $pluginname)
    );

    if ($PAGE->url->compare($url, \URL_MATCH_BASE)) {
        $node->make_active();
    }

    $settingnode = $nav->find('categorysettings', null);
    $reportsnode = $settingnode->add('Reports', $settingnode->action, \navigation_node::TYPE_CONTAINER);
    $reportsnode->add_node($node);

    return $node;
}

/**
 * This function extends the navigation with the tool items for user settings node.
 *
 * @param navigation_node $navigation  The navigation node to extend
 * @param stdClass        $user        The user object
 * @param context         $usercontext The context of the user
 * @param stdClass        $course      The course to object for the tool
 * @param context         $coursecontext     The context of the course
 */
function local_kent_extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
    $url = new moodle_url('/local/kent/preferences.php');
    $subsnode = navigation_node::create('Kent Preferences', $url, navigation_node::TYPE_SETTING, null, 'kent');

    if (isset($subsnode) && !empty($navigation)) {
        $navigation->add_node($subsnode);
    }
}
