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
use gradeexport_ilp_push\local\data\base;

/**
 * A data record for the database.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class saved_grade extends base {
    // Intentionally leaving gaps, incase we need more statuses.
    const GRADING_STATUS_EDITING = 5;
    const GRADING_STATUS_SUBMITTED = 10;
    const GRADING_STATUS_RESUBMIT = 12;
    const GRADING_STATUS_PROCESSING = 15;
    const GRADING_STATUS_PROCESSED = 20;
    const GRADING_STATUS_FAILED = 25;
    const GRADING_STATUS_LOCKED = 30;

    // The minimum delay time before resubmitting a failed grade. TODO - setting?
    const RESUBMIT_TIME = 300;

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = ['id', 'status', 'gradetype', 'revision', 'courseid', 'courseilpid', 'submitterid', 'submitterilpid',
                         'studentid', 'studentilpid', 'gradeoptid', 'grade', 'incompletegrade', 'incompletedeadline',
                         'datelastattended', 'resultstatus', 'additional', 'usersubmittime', 'ilpsendtime', 'timecreated',
                         'timemodified'];

    /** @var array An array of default property->value pairs */
    protected $defaults = ['status' => self::GRADING_STATUS_EDITING];

    /** @var array Array of keys will be used to see if two objects are the same. */
    protected $diffkeys = ['gradetype', 'courseid', 'courseilpid', 'studentid', 'studentilpid', 'grade', 'incompletegrade',
                           'incompletedeadline', 'datelastattended'];

    /** @var bool Intentionally public key, this will not be saved, only used transiently. */
    public $confirmed = false;

    protected $currentmessages = false;

    protected $currentfailure = false;

    /**
     * The table name of this object.
     */
    const TABLE = 'gradeexport_ilp_push_grades';



    // ******* Use Specific Methods.
    public function mark_failure($message = false) {
        if (!$this->currentfailure) {
            $this->currentfailure = true;
            if ($this->__isset('failcount')) {
                $this->failcount += 1;
            } else {
                $this->failcount = 1;
            }
        }
        if ($message !== false) {
            $this->add_status_message($message);
        }
    }

    public function add_status_message($message) {
        if ($this->currentmessages === false) {
            if (!empty($this->statusmessages)) {
                $this->previousmessages = $this->statusmessages;
            }
        }
        $this->currentmessages[] = $message;

        $this->statusmessages = implode("\n", $this->currentmessages);
    }

    public function get_is_current_failure() {
        return $this->currentfailure;
    }

    public function get_grade_mode() {
        return banner_grades::get_grade_mode($this->grademodeid);
    }

    public function get_grade() {
        return banner_grades::get_grade_mode($this->grademodeid)->get_grade($this->gradeoptid);
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
        if (!$records = $DB->get_recordset(static::TABLE, $params, 'id ASC')) {
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

    /**
     * Get an array of saved_grade records for a give submitterilp id and $courseilpid
     *
     * @param string $submitterilp The submitter's ILP id.
     * @param string $courseilp The course's ILP id.
     * @return
     */
    public static function get_for_submitter_course(string $submitterilp, string $courseilp, $status = null) {
        $params = ['submitterilpid' => $submitterilp, 'courseilpid' => $courseilp];

        if (!is_null($status)) {
            $params['status'] = $status;
        }

        return static::get_for_params($params);
    }



}


