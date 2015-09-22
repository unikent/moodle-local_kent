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
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $PAGE, $OUTPUT, $CFG, $SHAREDB;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/kent/reports/sharedb.php');
$PAGE->requires->css('/local/kent/styles/styles.css');

$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', 30, PARAM_INT);
$dist       = optional_param('dist', null, PARAM_ALPHANUM);
$conditions = empty($dist) ? null : array("moodle_dist" => $dist);

admin_externalpage_setup('reportsharedreport', '', null, '', array('pagelayout' => 'report'));

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sharedreport", "local_connect"));

// Output table displaying all courses in the Shared Database, with paging.
if (true) {
    echo $OUTPUT->box_start('reportbox');

    $table = new \html_table();
    $table->head = array("Environment", "Distribution", "Course ID", "Shortname", "Fullname");
    $table->attributes = array('class' => 'admintable generaltable');
    $table->data = array();

    // Grab a list of courses we can see.
    $records = $SHAREDB->get_records('shared_courses', $conditions, '',
        'id,moodle_env,moodle_dist,moodle_id,shortname,fullname', $page * $perpage, $perpage);
    foreach ($records as $record) {
        $table->data[] = new \html_table_row(array(
            $record->moodle_env,
            $record->moodle_dist,
            $record->moodle_id,
            $record->shortname,
            $record->fullname,
        ));
    }

    echo \html_writer::table($table);

    echo $OUTPUT->box_end();
}

// Output paging bar.
if (true) {
    $count = $SHAREDB->count_records('shared_courses', $conditions);
    $baseurl = new moodle_url('/local/kent/reports/sharedb.php', array('perpage' => $perpage, 'dist' => $dist));
    echo $OUTPUT->paging_bar($count, $page, $perpage, $baseurl);
}

// Output footer.
echo $OUTPUT->footer();
