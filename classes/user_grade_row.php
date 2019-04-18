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
    public function __construct($user, $exporter, $grade, $gradeitem) {
        $this->user = $user;
        $this->exporter = $exporter;
        $this->course = $exporter->get_course();
        $this->grade = $grade;
        $this->gradeitem = $gradeitem;
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
        $letter = null;
        if ($this->currentsavedgrade) {
            // TODO - May need to refine how this works later...
            $letter = $this->currentsavedgrade->grade;
        }

        if (is_null($letter)) {
            return $this->get_moodle_grade_key();
        }

        return banner_grades::find_key_for_letter($letter);
    }

    public function get_moodle_grade_key() {
        $letter = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);

        return banner_grades::find_key_for_letter($letter);
    }

    public function get_current_incomplete_grade_key() {
        $letter = null;
        if ($this->currentsavedgrade) {
            // TODO - May need to refine how this works later...
            $letter = $this->currentsavedgrade->incompletegrade;
        }

        if (is_null($letter)) {
            return banner_grades::get_default_incomplete_grade();
        }

        return banner_grades::find_key_for_letter($letter);
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





        foreach ($this->currenterrors as $id => $string) {
            $key = $id.'error';
            $output->$key = $renderer->render_form_error($string);
        }

        $currentkey = $this->get_current_grade_key();
        $output->showincomplete = banner_grades::grade_key_is_incomplete($currentkey);
        $output->showfailing = banner_grades::grade_key_is_failing($currentkey);

        $moodlekey = $this->get_moodle_grade_key();
        $output->truegradekey = $moodlekey;
        if ($moodlekey == $currentkey) {
            $output->equal = true;
        }

        $output->statusmessage = $renderer->render_status_messages($this);
        $output->status = $renderer->render_status($this);




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
        return $this->currentsavedgrade->status;
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

        $grade->grade = $this->get_ilp_grade_from_data($data, 'grade');
        $key = $this->get_form_id('grade');
        if (is_null($grade->grade)) {
            // If the grade resolved to null (not a valid banner grade), we are also going to null the data, for latter use.
            $data->$key = null;
        }
        $grade->gradekey = $data->$key;

        // Check if the grade changed, so we know if we should unconditionally store it.
        if ($this->check_grade_changed($data, 'grade')) {
            $savetodb = true;
        }

        // Stuff only for incomplete grades.
        if (banner_grades::grade_key_is_incomplete($grade->gradekey)) {
            $grade->incompletegrade = $this->get_ilp_grade_from_data($data, 'incompletegrade');
            $key = $this->get_form_id('incompletegrade');
            if (is_null($grade->incompletegrade)) {
                // If the grade resolved to null (not a valid banner grade), we are also going to null the data, for latter use.
                $data->$key = null;
            }

            $grade->incompletegradekey = $data->$key;
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
        if (banner_grades::grade_key_is_failing($grade->gradekey)) {
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
        $datakey = $this->get_form_id($formkey);

        if (!isset($data->$datakey)) {
            return null;
        }

        return banner_grades::get_ilp_grade_for_key($data->$datakey);
    }

    protected function get_timestamp_from_data($data, $formkey) {
        // TODO - move to moodle date picker.
        $datakey = $this->get_form_id($formkey);

        if (!isset($data->$datakey)) {
            return null;
        }

        $date = trim($data->$datakey);

        // HTML5 date field is always expected to return YYYY-MM-DD format data.
        if (!preg_match('|^(\d{2,4})[-\/](\d{1,2})[-\/](\d{1,2})$|', $date, $matches)) {
            return null;
        }

        $timestamp = make_timestamp($matches[1], $matches[2], $matches[3], 0, 0, 0, 99, true);

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


