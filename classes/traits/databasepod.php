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

    protected static $internalcache;

    /**
     * The name of our database table.
     */
    protected static function get_table() {
        throw new \moodle_exception("get_table() must be implemented.");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

    /**
     * Optionally returns an array of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array();
    }

    /**
     * Returns an array of fields that link to other databasepods.
     * fieldname -> classname
     */
    protected static function linked_fields() {
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
        if (isset($this->_data["{$name}id"])) {
            $linkedfields = $this->linked_fields();
            if (isset($linkedfields["{$name}id"])) {
                $this->_data["{$name}id"] = $value->id;
                return;
            }
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
     * Get internal cache.
     */
    private static function get_internal_cache() {
        global $CFG;

        // This helps with phpunit resets.
        if (!isset($CFG->local_kent_cache_uuid)) {
            $CFG->local_kent_cache_uuid = uniqid();
        }

        if (!isset(static::$internalcache['__uuid__']) || static::$internalcache['__uuid__'] !== $CFG->local_kent_cache_uuid) {
            static::$internalcache = array(
                '__uuid__' => $CFG->local_kent_cache_uuid
            );
        }

        return static::$internalcache;
    }

    /**
     * This is *basically* a public version of set_class_data.
     * Pseudo-forces singletons.
     */
    public static function from_sql_result($data) {
        $cache = static::get_internal_cache();
        if (!isset($cache[$data->id])) {
            $result = new static();
            $result->set_data($data);
            $cache[$data->id] = $result;
        }

        return $cache[$data->id];
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
    /**
     * Get an object by a specified field.
     */
    public static function get_by($field, $val, $forcearray = false) {
        global $DB;

        if (!in_array($field, static::valid_fields())) {
            debugging("Invalid field: $field!");
            return;
        }

        $data = $DB->get_records(static::get_table(), array(
            $field => $val
        ));

        if (!$forcearray) {
            if (!$data) {
                return null;
            }

            if (count($data) === 1) {
                return static::from_sql_result(array_pop($data));
            }
        }

        $ret = array();
        foreach ($data as $obj) {
            $ret[] = static::from_sql_result($obj);
        }

        return $ret;
    }

    /**
     * Get an object by ID
     */
    public static function get($id) {
        return static::get_by('id', $id);
    }

    /**
     * Returns all known objects.
     *
     * @param bool raw Return raw (stdClass) objects?
     */
    public static function get_all($raw = false) {
        global $DB;

        $set = $DB->get_records(static::get_table());

        if (!$raw) {
            foreach ($set as &$o) {
                $o = static::from_sql_result($o);
            }
        }

        return $set;
    }

    /**
     * Run a given method against all objects in a memory-efficient way.
     * The method will be provided with a single argument (object).
     */
    public static function batch_all($func, $conditions = array()) {
        global $DB;

        $errors = array();

        $rs = $DB->get_recordset(static::get_table(), $conditions);

        // Go through each record, create an object and call the function.
        foreach ($rs as $record) {
            try {
                $obj = static::from_sql_result($record);
                $func($obj);
            } catch (\moodle_exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $rs->close();

        return $errors;
    }
}