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

namespace local_kent\traits;

defined('MOODLE_INTERNAL') || die();

/**
 * A data pod contains (optionally managed) data.
 */
trait datapod
{
    /** Stores all our data */
    private $_data;

    /**
     * Optionally returns an array of immutable fields for this data object.
     */
    protected function immutable_fields() {
    	return array();
    }

    /**
     * Optionally returns an array of valid fields for this data object.
     */
    protected function valid_fields() {
    	return array();
    }

    /**
     * Get all of our data as an object
     */
    public final function get_data() {
        return (object)$this->_data;
    }

    /**
     * Magic method!
     */
    public function __get($name) {
        $additional = "_get_" . $name;
        if (method_exists($this, $additional)) {
            return $this->$additional();
        }

        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        $validfields = $this->valid_fields();
        if (!empty($validfields) && !in_array($name, $validfields)) {
            debugging("Invalid field: $name!");
        }

        return null;
    }

    /**
     * Magic!
     */
    public function __set($name, $value) {
        $validfields = $this->valid_fields();
        if (!empty($validfields) && !in_array($name, $this->valid_fields())) {
            debugging("Invalid field: $name!");
            return;
        }

        $validation = "_validate_" . $name;
        if (method_exists($this, $validation)) {
            if (!$this->$validation($value)) {
                throw new \moodle_exception("Invalid value for field '$name': $value!");
            }
        }

        $this->_data[$name] = $value;
    }

    /**
     * Magic!
     */
    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    /**
     * Magic!
     */
    public function __unset($name) {
        unset($this->_data[$name]);
    }
}