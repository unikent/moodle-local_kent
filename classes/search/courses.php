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
 * Search area for Moodle courses.
 *
 * @package    local_kent
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kent\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for Moodle courses.
 *
 * @package    local_kent
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses extends \core_search\area\base {
    /**
     * Returns recordset containing required data for indexing courses.
     *
     * @param int $modifiedfrom timestamp
     * @return \moodle_recordset
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        global $DB;
        return $DB->get_recordset_select('course', 'timemodified >= ?', array($modifiedfrom));
    }

    /**
     * Returns the document associated with this activity.
     *
     * This default implementation for activities sets the activity name to title and the activity intro to
     * content. Any activity can overwrite this function if it is interested in setting other fields than the
     * default ones, or to fill description optional fields with extra stuff.
     *
     * @param stdClass $record
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
        try {
            $context = \context_course::instance($record->id);
        } catch (\moodle_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document, not all required data is available: ' .
                $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', get_string('courseextendednamedisplay', '', $record));
        $doc->set('content', content_to_text($record->summary, $record->summaryformat));
        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->id);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        // Check if this document should be considered new.
        if (isset($options['lastindexedtime'])) {
            if ($options['lastindexedtime'] < $record->timecreated) {
                // If the document was created after the last index time, it must be new.
                $doc->set_is_new(true);
            }
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id The course instance id.
     * @return bool
     */
    public function check_access($id) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $id));
        if (!$course) {
            return \core_search\manager::ACCESS_DELETED;
        }

        if (can_access_course($course)) {
            return \core_search\manager::ACCESS_GRANTED;
        }

        return \core_search\manager::ACCESS_DENIED;
    }

    /**
     * Link to the module instance.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {;
        return $this->get_context_url($doc);
    }

    /**
     * Link to the module instance.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return new \moodle_url('/course/view.php', array('id' => $doc->get('courseid')));
    }
}
