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
    private $_data = array();

    /**
     * Optionally returns an array of valid fields for this data object.
     */
    protected static function valid_fields() {
        return array();
    }

    /**
     * Get all of our data as an object
     */
    public final function get_data() {
        return (object)$this->_data;
    }

    /**
     * Given an object containing data, set appropriate class vars.
     * This is done quickly, and skips most checks.
     * @param $data
     */
    protected function set_data($data) {
        if (!is_array($data)) {
            $data = get_object_vars($data);
        }

        $validfields = $this->valid_fields();
        foreach ($data as $key => $value) {
            if (empty($validfields) || in_array($key, $validfields)) {
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * Magic method!
     * @param $name
     * @return null
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
     * @param $name
     * @param $value
     * @throws \moodle_exception
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
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    /**
     * Magic!
     * @param $name
     */
    public function __unset($name) {
        unset($this->_data[$name]);
    }

    /**
     * Returns a flexitable.
     * @param $baseurl
     * @return \flexible_table
     */
    public function get_flexible_table($baseurl = null) {
        global $CFG, $PAGE;

        require_once($CFG->libdir . '/tablelib.php');

        if (!$baseurl) {
            $baseurl = $PAGE->url;
        }

        $class = get_called_class();

        $table = new \flexible_table("{$class}_{$this->id}");
        $table->define_columns(array('variable', 'value'));
        $table->define_headers(array('Variable', 'Value'));
        $table->define_baseurl($baseurl);
        $table->setup();

        foreach ($this->_data as $k => $v) {
            $prettymethod = "_pretty_" . $k;
            if (method_exists($this, $prettymethod)) {
                $v = $this->$prettymethod();
            } else {
                $v = nl2br(trim($v));
            }

            $table->add_data(array($k, $v));
        }

        return $table;
    }

    /**
     * Returns raw data to var_dump.
     */
    public function __debugInfo() {
        return $this->_data;
    }
}
