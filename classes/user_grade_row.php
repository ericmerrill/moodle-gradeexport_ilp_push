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
 * A object that represents a row of users.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->libdir . '/form/dateselector.php');

use stdClass;
use templatable;
use html_writer;
use moodle_url;
use gradeexport_ilp_push\local\sis_interface;

/**
 * A object that represents a row of users.
 *
 * This object is used to track all the data about a row. This can include form submission data, saved grade rows, and current
 * grade records.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_grade_row implements templatable {


    /** @var saved_grade The most recent saved grade record. */
    protected $currentsavedgrade;

    protected $pastsavedgrades = [];

    protected $newgradesave;

    protected $grademode;

    protected $coursegrademode;

    protected $user;

    protected $course;

    protected $exporter;

    protected $grade;

    protected $gradeitem;

    protected $currenterrors = [];

    protected $sis = null;



    /**
     * Basic constructor.
     */
    public function __construct($user, $exporter, $grade, $gradeitem, $grademode) {
        $this->user = $user;
        $this->exporter = $exporter;
        $this->course = $exporter->get_course();
        $this->grade = $grade;
        $this->gradeitem = $gradeitem;
        $this->grademode = $grademode;
        $this->coursegrademode = $grademode;
        $this->sis = sis_interface\factory::instance();
        $this->fetch_existing_rows();
    }

    /**
     * Load the existing saved grades for this row.
     */
    public function fetch_existing_rows() {
        if (!$savedgrades = saved_grade::get_records_for_user_course($this->user, $this->course)) {
            // We will create a default saved_grade object if there are none existing.
            $grade = $this->create_new_saved_grade();

            $this->currentsavedgrade = $grade;
            $this->pastsavedgrades[$grade->revision] = $grade;
            return;
        }

        $this->currentsavedgrade = end($savedgrades);
        reset($savedgrades);
        $this->pastsavedgrades = $savedgrades;

        // Get the current grade mode;
        $grademodeid = $this->currentsavedgrade->grademodeid;
        $this->grademode = banner_grades::get_grade_mode($grademodeid);
    }

    /**
     * Get the user object for the user this row represents.
     *
     * @return object
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Get the course object that goes with this row.
     *
     * @return object
     */
    public function get_course() {
        return $this->course;
    }

    public function set_grade_mode($grademode) {
        $this->grademode = $grademode;
    }

    public function get_form_id($prefix = false) {
        global $COURSE;

        if (empty($prefix)) {
            $prefix = '';
        } else {
            $prefix .= '-';
        }

        return $prefix.$COURSE->id.'-'.$this->user->id;
    }

    public function get_current_grade_key() {
        if ($this->currentsavedgrade && isset($this->currentsavedgrade->gradeoptid)) {
            return $this->currentsavedgrade->gradeoptid;
        }

        return $this->get_moodle_grade_key();
    }

    public function get_current_grade_mode() {
        return $this->grademode;
    }

    public function get_moodle_grade_key() {
        $letter = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);

        $grade = $this->grademode->get_grade_for_string($letter);

        if (!empty($grade)) {
            return $grade->id;
        }

        return false;
    }

    public function get_current_incomplete_grade_key() {
        if ($this->currentsavedgrade && isset($this->currentsavedgrade->incompletegradeid)) {
            return $this->currentsavedgrade->incompletegradeid;
        }

        return banner_grades::get_default_incomplete_grade();
    }

    /**
     * Returns string representation of a grade.
     *
     * TODO - Possibly need to switch between different grade values, not just grade->finalgrade.
     *
     * @param grade_grade   $grade Instance of grade_grade class
     * @return string
     */
    public function get_formatted_grade($format) {
        $grade = $this->grade;
        $item = $this->gradeitem;

        // We are going to store the min and max so that we can "reset" the grade_item for later.
        $grademax = $item->grademax;
        $grademin = $item->grademin;

        // Updating grade_item with this grade_grades min and max.
        $item->grademax = $grade->get_grade_max();
        $item->grademin = $grade->get_grade_min();

        $formatted = grade_format_gradevalue($grade->finalgrade, $item, true, $format);

        // Resetting the grade item in case it is reused.
        $item->grademax = $grademax;
        $item->grademin = $grademin;

        if ($format == GRADE_DISPLAY_TYPE_REAL && $item->gradetype != GRADE_TYPE_SCALE) {
            $formatted .= ' / '.format_float($grademax, $item->get_decimals(), true);
        }

        return $formatted;
    }

    public function export_history_for_template(\renderer_base $renderer) {
        $rows = [];

        if (!empty($this->pastsavedgrades)) {
            foreach ($this->pastsavedgrades as $savedgrade) {
                $row = new stdClass();
                if (empty($savedgrade->id)) {
                    // Not saved to DB, so ignoring for purposes of history.
                    continue;
                }
                if (isset($savedgrade->usersubmittime)) {
                    $date = $savedgrade->usersubmittime;
                } else {
                    $date = $savedgrade->timecreated;
                }
                $row->date = userdate($date);
                $row->grademodename = $savedgrade->get_grade_mode()->name;
                $grade = $savedgrade->get_grade();
                if ($grade) {
                    $row->grade = $grade->get_display_name();
                } else {
                    $row->grade = '-';
                }

                if (isset($savedgrade->datelastattended)) {
                    $row->datelastattended = userdate($savedgrade->datelastattended, get_string('strftimedate', 'langconfig'));
                } else {
                    $row->datelastattended = false;
                }

                if (isset($savedgrade->incompletedeadline)) {
                    $row->incompletedeadline = userdate($savedgrade->incompletedeadline, get_string('strftimedate', 'langconfig'));
                    if (isset($savedgrade->incompletegradeid)) {
                        $grade = $savedgrade->get_grade_mode()->get_grade($savedgrade->incompletegradeid);
                        $row->incompletegrade = $grade->get_display_name();
                    } else {
                        $row->incompletegrade = '-';
                    }
                } else {
                    $row->incompletedeadline = false;
                }

                $row->statusmessage = $renderer->render_status_messages($savedgrade);
                $row->status = $renderer->render_status($savedgrade);

                $rows[] = $row;
            }
        }

        $output = new stdClass();
        $output->historyrows = array_reverse($rows);

        return $output;
    }

    public function export_for_template(\renderer_base $renderer) {
        global $OUTPUT;

        $grade = $this->currentsavedgrade;

        $output = new stdClass();

        $fullname = fullname($this->user);
        $output->userimage = $OUTPUT->user_picture($this->user, ['visibletoscreenreaders' => false]);
        $output->fullname = $fullname;
        $output->username = $this->user->username;
        $output->userid = $this->user->id;
        $output->formid = $this->get_form_id();
        $output->userrowspan = 1;

        $output->gradelink = $renderer->render_grade_link($fullname, $this->user->id, $this->course->id) ;

        $output->courseilpid = $grade->courseilpid;

        $output->userdisplayid = $this->sis->get_user_display_id($this->user);

        $output->locked = $this->should_prevent_editing();

        $params = ['id' => $this->user->id, 'course' => $this->course->id];
        $output->fullnamelink = html_writer::link(new moodle_url('/user/view.php', $params), $fullname);

        $lettergrade = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);
        $output->gradeletter = $lettergrade;
        $output->gradereal = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_REAL);
        $output->gradepercent = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_PERCENTAGE);

        $output->grademodename = $this->get_current_grade_mode()->name;
        $output->grademodeselect = $renderer->render_row_grade_mode_select($this);

        $output->gradeselect = $renderer->render_select_menu($this);

        $output->incompletegradeselect = $renderer->render_incomplete_select_menu($this);

        if ($grade->datelastattended) {
            $output->datelastattended = date_format_string($grade->datelastattended, '%F');
        } else {
            $output->datelastattended = false;
        }
        if ($grade->incompletedeadline) {
            $output->incompletedeadline = date_format_string($grade->incompletedeadline, '%F');
        } else {
            $output->incompletedeadline = false;
        }

        $output->grademodeid = $this->grademode->id;

        // Get the history with this grade row.
        $history = $this->export_history_for_template($renderer);
        $output->historycount = count($history->historyrows);
        if (!empty($history->historyrows)) {
            $output->historyrows = $history->historyrows;
            $output->userrowspan++;
        }


        foreach ($this->currenterrors as $id => $string) {
            $key = $id.'error';
            $output->$key = $renderer->render_form_error($string);
        }

        $currentkey = $this->get_current_grade_key();
        $output->showincomplete = $this->grademode->grade_id_is_incomplete($currentkey);
        $output->showfailing = $this->grademode->grade_id_is_failing($currentkey);

        $moodlekey = $this->get_moodle_grade_key();
        $output->truegradekey = $moodlekey;
        if ($moodlekey == $currentkey) {
            $output->equal = true;
        }

        $output->statusmessage = $renderer->render_status_messages($grade);
        $output->status = $renderer->render_status($grade);

        if ($output->statusmessage) {
            $output->userrowspan++;
        }


        return $output;
    }

    protected function get_next_revision_number() {
        if ($this->currentsavedgrade) {
            return $this->currentsavedgrade->revision + 1;
        }

        return 0;
    }

    /**
     * If this returns true, then editing for this record is currently not allowed.
     *
     * @return bool True if this row should be locked, false if not.
     */
    public function should_prevent_editing() {
        $status = $this->currentsavedgrade->status;

        switch ($this->currentsavedgrade->status) {
            case (saved_grade::GRADING_STATUS_LOCKED):
            case (saved_grade::GRADING_STATUS_PROCESSING):
            case (saved_grade::GRADING_STATUS_SUBMITTED):
            case (saved_grade::GRADING_STATUS_RESUBMIT):
                return true;
                break;
            default:
                return false;
        }
    }



    public function get_current_status() {
        return (int)$this->currentsavedgrade->status;
    }

    public function is_in_progress() {
        $status = $this->get_current_status();

        if ($status === saved_grade::GRADING_STATUS_SUBMITTED || $status === saved_grade::GRADING_STATUS_RESUBMIT
                || $status === saved_grade::GRADING_STATUS_PROCESSING) {
            return true;
        }

        if ($status === saved_grade::GRADING_STATUS_EDITING) {
            if ($this->currentsavedgrade->revision === 0 && empty($this->currentsavedgrade->id)) {
                return false;
            }
            return true;
        }

        return false;
    }

    public function get_status_messages() {
        return $this->currentsavedgrade->statusmessages;
    }

    public function process_data(stdClass $data) {
        global $USER;

        // Track if we should unconditionally save to the db.
        $savetodb = false;

        if ($this->should_prevent_editing()) {
            // TODO - should show a error message if an edit attempt is made while locked.
            return;
        }

        if ($this->currentsavedgrade && $this->currentsavedgrade->status == saved_grade::GRADING_STATUS_EDITING) {
            $grade = $this->currentsavedgrade;
        } else {
            $grade = $this->create_new_saved_grade();
        }

        // Confirm is a special data element. It is not saved between data loads.
        // If it is true later, then it was for sure clicked this time around.
        $key = $this->get_form_id('confirm');
        if (!empty($data->$key)) {
            $grade->confirmed = true;
        }

        $grade->submitterid = $USER->id;
        $grade->submitterilpid = $this->sis->get_user_id($USER);
        $key = $this->get_form_id('grademodeid');
        $grade->grademodeid = $data->$key;
        $grademode = banner_grades::get_grade_mode($grade->grademodeid);

        if (empty($grademode)) {
            throw new exception\grade_mode_missing();
        }

        $gradeobj = $this->get_ilp_grade_from_data($data, 'grade');
        if (is_null($gradeobj)) {
            // If the grade resolved to null (not a valid banner grade), we are also going to null the data, for latter use.
            $key = $this->get_form_id('grade');
            $data->$key = null;
            $grade->grade = null;
            $grade->gradeoptid = null;
        } else {
            $grade->grade = $gradeobj->bannervalue;
            $grade->gradeoptid = $gradeobj->id;
        }

        // Check if the grade changed, so we know if we should unconditionally store it.
        if ($this->check_grade_changed($data, 'grade')) {
            $savetodb = true;
        }

        // Stuff only for incomplete grades.
        if ($grademode->grade_id_is_incomplete($grade->gradeoptid)) {
            $gradeobj = $this->get_ilp_grade_from_data($data, 'incompletegrade');
            if (is_null($gradeobj)) {
                // If the grade resolved to null (not a valid banner grade), we are also going to null the data, for latter use.
                $key = $this->get_form_id('incompletegrade');
                $data->$key = null;
                $grade->incompletegrade = null;
                $grade->incompletegradeid = null;
            } else {
                $grade->incompletegrade = $gradeobj->bannervalue;
                $grade->incompletegradeid = $gradeobj->id;
            }

            if ($this->check_grade_changed($data, 'incompletegrade') || $grade->confirmed) {
                $savetodb = true;
            }

            $deadline = $this->get_timestamp_from_data($data, 'incompletedeadline');
            $currentvalue = $this->currentsavedgrade->incompletedeadline;
            $grade->incompletedeadline = $deadline;
            if ($currentvalue != $deadline) {
                $savetodb = true;
            }
        }

        // Stuff only for failing grades.
        if ($grademode->grade_id_is_failing($grade->gradeoptid)) {
            $lastattended = $this->get_timestamp_from_data($data, 'datelastattended');
            $currentvalue = $this->currentsavedgrade->datelastattended;
            $grade->datelastattended = $lastattended;
            if ($currentvalue != $lastattended) {
                $savetodb = true;
            }

        }

        $grade->timecreated = time();

        $grade->gradetype = $this->exporter->get_grade_type();

        if (!$savetodb && !$grade->confirmed
                && $this->currentsavedgrade !== $grade && !$this->currentsavedgrade->objects_are_different($grade)) {
            // In this case, we don't need to actually use this object to do anything.
            return;
        }

        // Get validation results.
        $validation = rule_validator::validate_row($grade, $this->exporter);

        // If there are no errors, and they confirmed on this submit.
        if (empty($validation['errors']) && $grade->confirmed) {
            // Then we can move the status up.
            $grade->status = saved_grade::GRADING_STATUS_SUBMITTED;
            $grade->usersubmittime = time();
            $grade->save_to_db();

            $event = event\grade_modified::create_from_saved_grade($grade);
            $event->trigger();
        } else {
            if (!empty($validation['errors'])) {
                $this->currenterrors = $validation['errors'];
            }

            if ($savetodb) {
                $grade->save_to_db();
                $event = event\grade_modified::create_from_saved_grade($grade);
                $event->trigger();
            }

        }

        if ($this->currentsavedgrade !== $grade) {
            $this->currentsavedgrade = $grade;
            $this->pastsavedgrades[$grade->revision] = $grade;
        }
    }

    protected function check_grade_changed($data, $formkey) {
        $startkey = $this->get_form_id($formkey.'-starting');
        if (empty($data->$startkey)) {
            $start = null;
        } else {
            $start = $data->$startkey;
        }

        $submitkey = $this->get_form_id($formkey);
        if (empty($data->$submitkey)) {
            $submitted = null;
        } else {
            $submitted = $data->$submitkey;
        }

        if ($start == $submitted) {
            // It did not change.
            return false;
        } else {
            return true;
        }

    }

    protected function get_ilp_grade_from_data($data, $formkey) {
        $key = $this->get_form_id('grademodeid');
        $grademodeid = $data->$key;
        $grademode = banner_grades::get_grade_mode($grademodeid);

        if (empty($grademode)) {
            throw new exception\grade_mode_missing();
        }

        $datakey = $this->get_form_id($formkey);

        if (empty($data->$datakey)) {
            return null;
        }

        $grade = $grademode->get_grade($data->$datakey);

        if (empty($grade)) {
            throw new exception\grade_mode_mismatch();
        }

        return $grade;
    }

    protected function get_timestamp_from_data($data, $formkey) {
        // TODO - move to moodle date picker.
        $datakey = $this->get_form_id($formkey);

        if (!isset($data->$datakey)) {
            return null;
        }

        $date = trim($data->$datakey);

        // Users may enter MM/DD/YYYY format...
        if (preg_match('|^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$|', $date, $matches)
                && (1 <= $matches[1]) && ($matches[1] <= 12)
                && (1 <= $matches[2]) && ($matches[2] <= 31)) {
            $year = $matches[3];
            $month = $matches[1];
            $day = $matches[2];
        } else if (preg_match('|^(\d{2,4})[-\/](\d{1,2})[-\/](\d{1,2})$|', $date, $matches)) {
            // HTML5 date field is always expected to return YYYY-MM-DD format data.
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
        } else {
            return null;
        }

        if (strlen($year) == 2) {
            $year += 2000;
        }

        $timestamp = make_timestamp($year, $month, $day, 0, 0, 0, 99, true);

        if ($timestamp === false) {
            return null;
        }

        return $timestamp;
    }


    protected function create_new_saved_grade() {
        $grade = new saved_grade();
        $grade->studentid = $this->user->id;
        $grade->studentilpid = $this->sis->get_user_id($this->user);
        $grade->courseid = $this->course->id;
        $grade->courseilpid = $this->sis->get_course_id_for_user($this->course, $this->user);

        $grade->revision = $this->get_next_revision_number();

        return $grade;
    }
}


