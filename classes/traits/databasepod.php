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
 * A validated data pod contains managed data.
 */
trait databasepod
{
    use datapod;

    /**
     * Returns an array of fields that link to other databasepods.
     * fieldname -> classname
     */
    protected function linked_fields() {
        return array();
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

        // Are we trying to get the object for an id column?
        $linkedfields = $this->linked_fields();
        if (isset($this->_data["{$name}id"]) && isset($linkedfields["{$name}id"])) {
            $class = $linkedfields["{$name}id"];
            if (class_exists($class)) {
                $id = $this->_data["{$name}id"];
                return $class::get_by('id', $id);
            }
        }

        if (!in_array($name, $this->valid_fields())) {
            debugging("Invalid field: $name!");
        }

        return null;
    }

    /**
     * Magic!
     */
    public function __set($name, $value) {
        $additional = "_set_" . $name;
        if (method_exists($this, $additional)) {
            return $this->$additional($value);
        }

        // Are we trying to set the object for an id column?
        $linkedfields = $this->linked_fields();
        if (isset($this->_data["{$name}id"]) && isset($linkedfields["{$name}id"])) {
            $this->_data["{$name}id"] = $value->id;
            return;
        }

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
     * Save to the Connect database
     *
     * @return boolean
     */
    public function save() {
        global $DB;

        $table = $this->get_table();
        if ($table === null) {
            return false;
        }

        $params = (array)$this->get_data();

        $sets = array();
        foreach ($params as $field => $value) {
            if (!in_array($field, $this->immutable_fields())) {
                $sets[] = "$field = :" . $field;
            } else {
                unset($params[$field]);
            }
        }

        $ids = array();
        foreach ($this->key_fields() as $key) {
            $ids[] = $key . " = :" . $key;
            $params[$key] = $this->_data[$key];
        }

        $idstr = implode(' AND ', $ids);
        $sets = implode(', ', $sets);
        $sql = "UPDATE {{$table}} SET {$sets} WHERE {$idstr}";

        return $DB->execute($sql, $params);
    }
}