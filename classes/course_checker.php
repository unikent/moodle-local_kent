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

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course checker.
 */
class course_checker
{
    const MIN_SECTIONS = 3;
    const MAX_SECTIONS = 12;
    const MIN_CM_PER_SECTION = 5;
    const MAX_CM_PER_SECTION = 20;
    const MIN_CM_NAME = 1;
    const MAX_CM_NAME = 5;

    /** @var int Course ID. */
    private $courseid;

    /** @var \course_modinfo Course mod info. */
    private $cminfo;

    /** @var array A list of checks, and their status. */
    private $checks = array();

    /**
     * Constructor.
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->cminfo = get_fast_modinfo($courseid);
    }

    /**
     * Returns the scores.
     */
    public function run() {
        $this->checks = array();

        $sectioncount = count($this->cminfo->get_sections());

        // Check the number of sections is greater than min section limit.
        $this->checks["section_min_limit"] = array(
            'score' => $sectioncount >= self::MIN_SECTIONS ? 15 : $sectioncount,
            'max' => 15,
            'fixable' => false,
            'text' => 'You should aim to have at least ' . self::MIN_SECTIONS . ' sections with activities.',
            'level' => 'warning'
        );

        // Check the number of sections doesnt exceed max section limit.
        $this->checks["section_max_limit"] = array(
            'score' => $sectioncount <= self::MAX_SECTIONS ? 15 : 0,
            'max' => 15,
            'fixable' => false,
            'text' => 'You should aim to have no more than ' . self::MAX_SECTIONS . ' sections with activities.',
            'level' => 'warning'
        );

        // For each section.
        $sections = $this->cminfo->get_section_info_all();
        foreach ($sections as $section) {
            $cms = empty($section->sequence) ? 0 : count(explode(',', $section->sequence));
            $summary = strtolower($section->summary);

            // Check section description contains no headings.
            $this->checks["section_{$section->section}_description_headings"] = array(
                'score' => strpos($summary, '<h') === false ? 5 : 0,
                'max' => 5,
                'fixable' => true,
                'text' => 'Section descriptions should contain no headings! Heading found in section ' . $section->section,
                'level' => 'danger'
            );

            // Check section description contains no images.
            $this->checks["section_{$section->section}_description_images"] = array(
                'score' => strpos($summary, '<img') === false ? 5 : 0,
                'max' => 5,
                'fixable' => true,
                'text' => 'Section descriptions should contain no images! Image found in section ' . $section->section,
                'level' => 'danger'
            );

            // Check section description contains no objects.
            $this->checks["section_{$section->section}_description_object"] = array(
                'score' => strpos($summary, '<object') === false ? 5 : 0,
                'max' => 5,
                'fixable' => true,
                'text' => 'Section descriptions should contain no objects! Object found in section ' . $section->section,
                'level' => 'danger'
            );

            // Check the min number of course modules.
            $this->checks["section_{$section->section}_num_cm_min"] = array(
                'score' => $cms >= self::MIN_CM_PER_SECTION ? self::MIN_CM_PER_SECTION : $cms,
                'max' => self::MIN_CM_PER_SECTION,
                'fixable' => false,
                'text' => 'You should have a minimum of ' . self::MIN_CM_PER_SECTION . ' activities per section. ' . $cms . ' found in section ' . $section->section,
                'level' => 'warning'
            );

            // Check the max number of course modules.
            $this->checks["section_{$section->section}_num_cm_max"] = array(
                'score' => $cms <= self::MAX_CM_PER_SECTION ? 15 : 0,
                'max' => 15,
                'fixable' => false,
                'text' => 'You should have a maximum of ' . self::MAX_CM_PER_SECTION . ' activities per section. ' . $cms . ' found in section ' . $section->section,
                'level' => 'danger'
            );
        }

        // For each CM.
        $cms = $this->cminfo->get_cms();
        foreach ($cms as $cm) {
            // Check the case.
            $name = $cm->name;
            $namelen = str_word_count($name);

            $casescore = 0;
            if (strtolower($name) != $name) {
                $casescore += 5;
            }

            if (strtoupper($name) != $name) {
                $casescore += 5;
            }

            if (strlen($name) > 0) {
                $check = strtoupper($name);
                if ($check[0] == $name[0]) {
                    $casescore += 5;
                }
            }

            $this->checks["cm_{$cm->id}_case"] = array(
                'score' => $casescore,
                'max' => 15,
                'fixable' => true,
                'text' => 'Activity names should be in sentence case, found "' . $name . '".',
                'level' => 'danger'
            );

            // Check the word length min.
            $this->checks["cm_{$cm->id}_word_length_min"] = array(
                'score' => $namelen >= self::MIN_CM_NAME ? self::MIN_CM_NAME : $namelen,
                'max' => self::MIN_CM_NAME,
                'fixable' => false,
                'text' => 'Activity names should have at least ' . self::MIN_CM_NAME . ' words. Found ' . $namelen . ' in "' . $name . '"',
                'level' => 'warning'
            );

            // Check the word length max.
            $this->checks["cm_{$cm->id}_word_length_max"] = array(
                'score' => $namelen <= self::MAX_CM_NAME ? 5 : 0,
                'max' => 5,
                'fixable' => false,
                'text' => 'Activity names should have no more than ' . self::MAX_CM_NAME . ' words. Found ' . $namelen . ' in "' . $name . '"',
                'level' => 'warning'
            );

            // Check no punctuation at the end.
            $trimname = trim($name);
            $letters = str_split($trimname);
            $letter = array_pop($letters);
            $this->checks["cm_{$cm->id}_name_punctuation"] = array(
                'score' => !ctype_punct($letter) ? 3 : 0,
                'max' => 3,
                'fixable' => true,
                'text' => 'Activity names should not end in punctuation. Found "' . $name . '"',
                'level' => 'warning'
            );
        }
    }

    /**
     * Returns any warnings / errors.
     */
    public function render_results() {
        global $OUTPUT;

        foreach ($this->checks as $check) {
            if ($check['score'] != $check['max']) {
                echo $OUTPUT->notification($check['text'], $check['level']);
            }
        }
    }

    /**
     * Returns the scores.
     */
    public function get_score() {
        $score = 0;
        $max = 0;
        $fixable = 0;

        foreach ($this->checks as $check) {
            $score += $check['score'];
            $max += $check['max'];
            if ($check['fixable']) {
                $fixable++;
            }
        }

        return array(
            'score' => $score,
            'max' => $max,
            'fixable' => $fixable
        );
    }
}
