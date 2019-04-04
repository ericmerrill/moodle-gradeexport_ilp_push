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
 * A data record for the database.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * A data record for the database.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class saved_grade {
    // Intentionally leaving gaps, incase we need more statuses.
    const GRADING_STATUS_EDITING = 5;
    const GRADING_STATUS_SUBMITTED = 10;
    const GRADING_STATUS_PROCESSING = 15;
    const GRADING_STATUS_PROCESSED = 20;
    const GRADING_STATUS_FAILURE = 25;
    const GRADING_STATUS_LOCKED = 30;

    /** @var object The database record object */
    protected $record;

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = ['id', 'status', 'gradetype', 'revision', 'courseid', 'courseilpid', 'submitterid', 'submitterilpid',
                         'studentid', 'studentilpid', 'grade', 'incompletegrade', 'incompletedeadline', 'datelastattended',
                         'resultstatus', 'additional', 'usersubmittime', 'ilpsendtime', 'timecreated', 'timemodified'];

    /** @var array An array of default property->value pairs */
    protected $defaults = ['status' => self::GRADING_STATUS_EDITING];

    /** @var object Object that contains additional data about the object. This will be JSON encoded. */
    protected $additionaldata;

    /** @var array Array of keys will be used to see if two objects are the same. */
    protected $diffkeys = ['gradetype', 'courseid', 'courseilpid', 'studentid', 'studentilpid', 'grade', 'incompletegrade',
                           'incompletedeadline', 'datelastattended'];

    /** @var bool Intentionally public key, this will not be saved, only used transiently. */
    public $confirmed = false;

    /**
     * The table name of this object.
     */
    const TABLE = 'gradeexport_ilp_push_grades';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->record = new stdClass();
        $this->additionaldata = new stdClass();
    }

    // ******* Record Manipulation Methods.
    /**
     * Create a object with the given record.
     *
     * @param int $id ID to load
     * @return saved_grade|false
     */
    public static function get_for_record(stdClass $record) {
        $obj = new static();

        $obj->load_from_record($record);

        return $obj;
    }

    /**
     * Load from a database record.
     *
     * @param stdClass $record The record to load.
     */
    protected function load_from_record($record) {
        $this->record = $record;
        $this->additionaldata = json_decode($record->additional);
    }

    /**
     * Converts this data object into a database record.
     *
     * @return object The object converted to a DB object.
     */
    protected function convert_to_db_object() {
        $obj = new stdClass();

        foreach ($this->dbkeys as $key) {
            if ($key == 'timemodified') {
                $obj->$key = time();
                continue;
            }
            $obj->$key = $this->__get($key);
        }

        return $obj;
    }

    // ******* Database Interaction Methods.
    /**
     * Load the record for a given id.
     *
     * @param int $id ID to load
     * @return saved_grade|false
     */
    public static function get_for_id(int $id) {
        global $DB;

        $record = $DB->get_record(static::TABLE, ['id' => $id]);

        if (empty($record)) {
            return false;
        }

        $obj = new static();

        $obj->load_from_record($record);

        return $obj;
    }

    /**
     * Save this record to the database.
     */
    public function save_to_db() {
        global $DB;

        $new = $this->convert_to_db_object();
        if (empty($this->record->id)) {
            // New record.
            $id = $DB->insert_record(static::TABLE, $new);
            $this->record->id = $id;
        } else {
            // Existing record.
            $DB->update_record(static::TABLE, $new);
        }
    }

    /**
     * Check if the provided object is materially different from this object.
     *
     * @param saved_grade $grade Another object to check against
     * @return bool True if they are different
     */
    public function objects_are_different($grade) {
        foreach ($this->diffkeys as $key) {
            if ($this->__isset($key) !== $grade->__isset($key)) {
                return true;
            }

            if ($this->$key != $grade->$key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an array of saved_grade objects that go with provided user/course combo. Sorted oldest to newest, keyed by revision.
     *
     * @param stdClass $user The user.
     * @param stdClass $course The course.
     * @return saved_grades[]
     */
    public static function get_records_for_user_course(stdClass $user, stdClass $course) {
        global $DB;

        $params = ['studentid' => $user->id, 'courseid' => $course->id];
        if (!$records = $DB->get_records(static::TABLE, $params, 'id ASC')) {
            return false;
        }

        $grades = [];
        foreach ($records as $record) {
            $grade = new static();
            $grade->load_from_record($record);

            $grades[$record->revision] = $grade;
        }

        return $grades;
    }

    // ******* Magic Methods.
    /**
     * Gets (by reference) the passed property.
     *
     * @param string $name Name of property to get
     * @return mixed The property
     */
    public function &__get($name) {
        // First check the DB keys, then additional.
        if (in_array($name, $this->dbkeys)) {
            if ($name == 'additional') {
                // Allows easier interaction with outside scripts of DB modification than serialize.
                $this->record->$name = json_encode($this->additionaldata, JSON_UNESCAPED_UNICODE);
                return $this->record->$name;
            }
            if (!isset($this->record->$name) && isset($this->defaults[$name])) {
                return $this->defaults[$name];
            }
            return $this->record->$name;
        }
        if (!isset($this->additionaldata->$name) && isset($this->defaults[$name])) {
            return $this->defaults[$name];
        }
        return $this->additionaldata->$name;
    }

    /**
     * Set a property, either in the db object, ot the additional data object
     *
     * @param string $name Name of property to set
     * @param string $value The value
     */
    public function __set($name, $value) {
        if (in_array($name, $this->dbkeys)) {
            $this->record->$name = $value;
        } else {
            $this->additionaldata->$name = $value;
        }
    }

    /**
     * Unset the passed property.
     *
     * @param string $name Name of property to unset
     */
    public function __unset($name) {
        if (in_array($name, $this->dbkeys)) {
            unset($this->record->$name);
            return;
        }
        unset($this->additionaldata->$name);
    }

    /**
     * Check if a property is set.
     *
     * @param string $name Name of property to set
     * @return bool True if the property is set
     */
    public function __isset($name) {
        if (isset($this->defaults[$name])) {
            return true;
        }
        if (in_array($name, $this->dbkeys)) {
            return isset($this->record->$name);
        }
        return isset($this->additionaldata->$name);
    }
}


