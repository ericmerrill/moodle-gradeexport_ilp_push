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
 * The main export plugin class.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

require_once($CFG->dirroot.'/grade/export/lib.php');

use grade_item;
use grade_helper;
use grade_export_update_buffer;
use graded_users_iterator;
use templatable;
use stdClass;
use gradeexport_ilp_push\local\sis_interface;
use gradeexport_ilp_push\output\options_form;

/**
 * The main export plugin class.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_exporter implements templatable {


    const GRADE_TYPE_MIDTERM_1 = 1;
    const GRADE_TYPE_MIDTERM_2 = 2;
    const GRADE_TYPE_MIDTERM_3 = 3;
    const GRADE_TYPE_MIDTERM_4 = 4;
    const GRADE_TYPE_MIDTERM_5 = 5;
    const GRADE_TYPE_MIDTERM_6 = 6;
    const GRADE_TYPE_FINAL = 9;

    const FILTER_ALL = 0;
    const FILTER_NEEDS_ATTENTION = 1;
    const FILTER_IN_PROGRESS = 2;
    const FILTER_ERROR = 3;
    const FILTER_DONE = 4;

    protected $onlyactive = true; // TODO - Setting.

    protected $gradeitems;

    protected $coursegradeitem;

    protected $currentgradeitem;

    protected $groupid;

    protected $course;

    protected $userrows = null;

    protected $alluserrows = null;

    protected $gradetype = self::GRADE_TYPE_FINAL;

    protected $statusfilter = self::FILTER_NEEDS_ATTENTION;

    protected $grademode = null;


    /**
     * Constructor to set everything up.
     *
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     */
    public function __construct($course, $groupid = null) {
        $this->course = $course;
        $this->groupid = $groupid;

        $this->regrade_if_needed();

        // Get all course grade items and the course grade item.
        $this->gradeitems = grade_item::fetch_all(array('courseid'=>$this->course->id));
        $this->coursegradeitem = grade_item::fetch_course_item($course->id);

        $this->statusfilter = get_user_preferences('gradeexport_ilp_push_status_filter', $this->statusfilter);

        $grademodeid = get_user_preferences('gradeexport_ilp_push_grade_mode-'.$this->course->id);

        $this->grademode = banner_grades::get_grade_mode($grademodeid);

        $this->set_grade_item(get_user_preferences('gradeexport_ilp_push_reference_grade-'.$this->course->id));

    }

    public function regrade_if_needed() {

        $callback = function() {
            global $PAGE;

            return $PAGE->url;
        };

        grade_regrade_final_grades_if_required($this->course, $callback);
    }

    protected function get_grade_columns() {
        return [$this->currentgradeitem->id => $this->currentgradeitem];
    }

    protected function set_grade_item($itemid) {
        if (empty($itemid) || empty($this->gradeitems[$itemid])) {
            $this->currentgradeitem = $this->coursegradeitem;
            return;
        }

        $this->currentgradeitem = $this->gradeitems[$itemid];
    }

    protected function build_user_data() {
        if (!is_null($this->userrows)) {
            return;
        }

        $profilefields = grade_helper::get_user_profile_fields($this->course->id, true);
        $this->displaytype = [GRADE_DISPLAY_TYPE_REAL, GRADE_DISPLAY_TYPE_PERCENTAGE, GRADE_DISPLAY_TYPE_LETTER];

        // $geub = new grade_export_update_buffer();$status = $geub->track($grade);$geub->close(); TODO.
        $gui = new graded_users_iterator($this->course, $this->get_grade_columns(), $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields(true);
        $gui->init();

        $userrows = [];

        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;

            // We only use one grade item, so that is easy...
            $grade = reset($userdata->grades);

            $userrow = new user_grade_row($user, $this, $grade, $this->currentgradeitem, $this->grademode);

            $userrows[] = $userrow;

        }
        $gui->close();


        $this->userrows = $userrows;
    }

    public function get_course() {
        return $this->course;
    }

    public function get_grade_type() {
        return $this->gradetype;
    }

    /**
     * Return all user grade rows, regardless of filter settings.
     *
     * @return user_grade_row[]
     */
    public function get_user_data() {
        $this->build_user_data();

        return $this->userrows;
    }

    /**
     * Return user data rows, but filtered.
     *
     * @return user_grade_row[]
     */
    public function get_filtered_user_data() {
        $output = [];

        foreach ($this->get_user_data() as $row) {
            if ($this->filters_allow_row($row)) {
                $output[] = $row;
            }
        }

        return $output;
    }

    public function export_for_template(\renderer_base $renderer) {
        global $USER;

        $output = new stdClass;

        $rows = [];
        foreach ($this->get_filtered_user_data() as $row) {
            $rows[] = $row->export_for_template($renderer);
        }

        $output->userrows = $rows;

        $output->courseid = $this->course->id;
        $output->groupid = $this->groupid;
        $output->graderid = $USER->id;
        $output->sesskey = sesskey();

        return $output;
    }

    public function process_data(stdClass $data) {
        global $USER;

        if (isset($data->optionsform)) {
            // This is the options form data, not the grading form.
            return;
        }

        // TODO check sesskey.

        // Check that the user id of the grader hasn't changed.
        if ($data->graderid != $USER->id) {
            throw new moodle_exception('exception_user_mismatch', 'gradeexport_ilp_push');
        }

        // Check that the course id is right.
        if ($data->courseid != $this->course->id) {
            throw new moodle_exception('exception_course_mismatch', 'gradeexport_ilp_push');
        }

        $submissions = false;
        foreach ($this->get_user_data() as $row) {
            $submissions = $row->process_data($data) || $submissions;
        }

        task\process_user_course_task::register_task_for_user_course($USER->id, $this->course->id);
    }

    public function get_options_form() {
        // Get all the grade items.
        $seq = new \grade_seq($this->course->id, true, true);
        $selectitems = [];
        foreach ($seq->items as $itemid => $item) {
            $selectitems[$itemid] = $item->get_name();
        }

        $params = ['id' => $this->course->id,
                   'gradeoptions' => $selectitems];
        $class = ['class' => 'gradingoptions'];

        $form = new options_form(null, $params, 'post', '', $class);

        $data = ['statusfilter' => $this->statusfilter,
                 'grademode' => $this->grademode->id,
                 'referencegrade' => $this->currentgradeitem->id];

        $form->set_data($data);

        return $form;
    }

    public function process_options_form() {
        $form = $this->get_options_form();

        if ($data = $form->get_data()) {
            if (isset($data->statusfilter)) {
                set_user_preference('gradeexport_ilp_push_status_filter', $data->statusfilter);
                $this->statusfilter = $data->statusfilter;
            }

            if (isset($data->referencegrade)) {
                $this->set_grade_item($data->referencegrade);
                set_user_preference('gradeexport_ilp_push_reference_grade-'.$this->course->id, $this->currentgradeitem->id);
            }

            if (isset($data->grademode)) {
                set_user_preference('gradeexport_ilp_push_grade_mode-'.$this->course->id, $data->grademode);
            }
        }
    }

    public static function check_grading_allowed($course) {
        global $USER;

        $result = sis_interface\factory::instance()->teacher_allowed_to_grade_course($USER, $course);

        return $result;
    }

    protected function filters_allow_row(user_grade_row $row) {
        $status = $row->get_current_status();

        switch ($this->statusfilter) {
            case (static::FILTER_ALL):
                return true;
                break;
            case (static::FILTER_NEEDS_ATTENTION):
                // For this status, we show grades that need editing or showed an error.
                if ($status === saved_grade::GRADING_STATUS_EDITING
                        || $status === saved_grade::GRADING_STATUS_FAILED
                        || $status === saved_grade::GRADING_STATUS_LOCKED) {
                    return true;
                }
                break;
            case (static::FILTER_IN_PROGRESS):
                if ($row->is_in_progress()) {
                    return true;
                }
                break;
            case (static::FILTER_ERROR):
                // For this status, we show grades that show an error, including locked error.
                // TODO - split internal locked vs error lock.
                if ($status === saved_grade::GRADING_STATUS_FAILED || $status === saved_grade::GRADING_STATUS_LOCKED) {
                    return true;
                }
                break;
            case (static::FILTER_DONE):
                // For this status, we show grades that show an error, including locked error.
                // TODO - split internal locked vs error lock.
                if ($status === saved_grade::GRADING_STATUS_PROCESSED) {
                    return true;
                }
                break;
        }

        return false;
    }

}


