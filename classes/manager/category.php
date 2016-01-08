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

namespace local_kent\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Category Manager.
 */
class category
{
    use \local_kent\traits\singleton;

    /**
     * Our set of categories.
     */
    private $categories;

    /**
     * Constructor.
     */
    private function __construct() {
        global $CFG, $kentcategories;

        require($CFG->dirroot . "/local/kent/db/categories.php");

        $this->categories = $kentcategories;
    }

    /**
     * Returns all our categories.
     */
    public function get_categories() {
        return $this->categories;
    }

    /**
     * Install.
     */
    public function install() {
        global $CFG;

        if (!isset($CFG->coursecontact)) {
            $CFG->coursecontact = '';
        }

        $kentcategories = $this->categories;

        $localcatmap = array();
        while (!empty($kentcategories)) {
            foreach ($kentcategories as $category) {
                $category = (object)$category;

                if ($category->parent > 1) {
                    if (!isset($localcatmap[$category->parent])) {
                        continue;
                    }

                    $category->parent = $localcatmap[$category->parent];
                }

                if (empty($category->idnumber)) {
                    $category->idnumber = $category->id;
                }

                $coursecat = \coursecat::create($category);
                $localcatmap[$category->id] = $coursecat->id;

                unset($kentcategories[$category->id]);
            }
        }
    }

    /**
     * Create a specific category from the datafile.
     */
    public function create($id) {
        global $DB;

        if (!isset($this->categories[$id])) {
            throw new \moodle_exception("Invalid kent category id {$id}!");
        }

        $category = $this->categories[$id];
        $category = (object)$category;

        if ($category->parent > 0) {
            $category->parent = $DB->get_field('course_categories', 'id', array(
                'idnumber' => $category->parent
            ), \MUST_EXIST);
        }

        return \coursecat::create($category);
    }

    /**
     * Returns true if we are managed by the category manager.
     */
    public function is_managed(\stdClass $category) {
        foreach ($this->categories as $match) {
            if ($match['idnumber'] == $category->idnumber) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the given category is unique.
     */
    public function is_unique(\stdClass $category) {
        $matches = 0;
        foreach ($this->categories as $match) {
            if ($match['name'] == $category->name) {
                $matches++;
            }
        }

        return $matches <= 1;
    }

    /**
     * Returns the ID number for a given category based on name and parents.
     */
    public function get_idnumber(\stdClass $category, $allcategories) {
        // Special case.
        if ($category->id == 1) {
            return 'miscellaneous';
        }

        // If we are unqiue, then just return the idnumber.
        if ($this->is_unique($category)) {
            foreach ($this->categories as $match) {
                if ($match['name'] == $category->name) {
                    return $match['idnumber'];
                }
            }
        }

        // Not unique :( boo! Do fancy parent matching.
        // Grab our path.
        $path = explode('/', $category->path);
        array_shift($path);
        array_pop($path);
        if (empty($path)) {
            throw new \moodle_exception("Non-unique root category detected: " . $category->name);
        }

        // Get a list of all of our Moodle-side parents.
        $parents = array_map(function($id) use ($allcategories) {
            foreach ($allcategories as $cat) {
                if ($cat->id == $id) {
                    return $cat->name;
                }
            }

            return null;
        }, $path);

        // Now. Work it out.
        foreach ($this->categories as $match) {
            if ($match['name'] != $category->name) {
                continue;
            }

            // Extract parent path.
            $matchpath = explode('/', $match['path']);
            array_shift($matchpath);
            array_pop($matchpath);
            if (empty($matchpath)) {
                throw new \coding_exception("Non-unique root category detected: " . $match['name']);
            }

            // Grab parent data.
            $matchparents = array();
            foreach ($matchpath as $matchparent) {
                if (!isset($this->categories[$matchparent])) {
                    debugging("Couldn't find managed parent: {$matchparent}\n");
                    continue;
                }
                $matchparents[] = $this->categories[$matchparent]['name'];
            }

            // Now. Check the parents line up.
            if ($parents == $matchparents) {
                return $match['idnumber'];
            }
        }

        throw new \moodle_exception("Could not find category idnumber for " . $category->name);
    }
}
