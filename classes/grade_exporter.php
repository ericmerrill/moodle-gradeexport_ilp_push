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

    /** @var grade_item[] Grade items in this course. */
    protected $gradeitems;

    /** @var grade_item The course grade item in this course. */
    protected $coursegradeitem;

    /** @var grade_item The selected 'reference' grade item for this course. */
    protected $currentgradeitem;

    /** @var object The course object. */
    protected $course;

    /** @var user_grade_row[] The grade rows that we are using. */
    protected $userrows = null;

    /** @var int Grade type of this export (final/midterm). */
    protected $gradetype = self::GRADE_TYPE_FINAL;

    /** @var grade_mode The default grade mode object for this export. */
    protected $grademode = null;

    /** @var int The status filter to apply. */
    protected $statusfilter = self::FILTER_NEEDS_ATTENTION;

    /** @var int Filter by this group id. */
    protected $groupid;

    /** @var string|int The section id to filter to. */
    protected $sectionid;

    /**
     * Constructor to set everything up.
     *
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     */
    public function __construct($course, $groupid = null) {
        $this->course = $course;
        //$this->groupid = $groupid;

        $this->regrade_if_needed();

        // Get all course grade items and the course grade item.
        $this->gradeitems = grade_item::fetch_all(array('courseid'=>$this->course->id));
        $this->coursegradeitem = grade_item::fetch_course_item($course->id);

        $this->statusfilter = get_user_preferences('gradeexport_ilp_push_status_filter-'.$this->course->id, $this->statusfilter);

        $this->groupid = get_user_preferences('gradeexport_ilp_push_group_filter-'.$this->course->id);

        $this->sectionid = get_user_preferences('gradeexport_ilp_push_section_filter-'.$this->course->id);

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

    public function get_user_grade_row($userid) {
        // TODO - Probably a more efficient way to do this. Currently load all grades.
        $this->build_user_data();

        foreach ($this->userrows as $userrow) {
            if ($userrow->get_user()->id == $userid) {
                return $userrow;
            }
        }

        return false;
    }

    protected function build_user_data() {
        if (!is_null($this->userrows)) {
            return;
        }

        $sis = sis_interface\factory::instance();

        $gui = new graded_users_iterator($this->course, $this->get_grade_columns(), $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields(true);
        $gui->init();

        $userrows = [];

        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;

            if (empty($sis->is_gradable_user_in_course($user, $this->course))) {
                continue;
            }

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

    public function get_grade_mode() {
        return $this->grademode;
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

        foreach ($this->get_user_data() as $row) {
            $row->process_data($data);
        }

        task\process_user_course_task::register_task_for_user_course($USER->id, $this->course->id);
    }

    /**
     * Build and return the options form.
     *
     * @return options_form
     */
    public function get_options_form() {
        // Get all the grade items.
        $seq = new \grade_seq($this->course->id, true, true);
        $selectitems = [];
        foreach ($seq->items as $itemid => $item) {
            $selectitems[$itemid] = $item->get_name();
        }

        $params = ['id' => $this->course->id,
                   'gradeoptions' => $selectitems,
                   'course' => $this->course];
        $class = ['class' => 'gradingoptions'];

        $form = new options_form(null, $params, 'post', '', $class);

        $data = ['statusfilter' => $this->statusfilter,
                 'groupfilter' => $this->groupid,
                 'sectionfilter' => $this->sectionid,
                 'grademode' => $this->grademode->id,
                 'referencegrade' => $this->currentgradeitem->id];

        $form->set_data($data);

        return $form;
    }

    /**
     * Process data out of the options form.
     */
    public function process_options_form() {
        $form = $this->get_options_form();

        if ($data = $form->get_data()) {
            if (isset($data->statusfilter)) {
                set_user_preference('gradeexport_ilp_push_status_filter-'.$this->course->id, $data->statusfilter);
                $this->statusfilter = $data->statusfilter;
            }

            if (isset($data->groupfilter)) {
                $this->groupid = $data->groupfilter;
                set_user_preference('gradeexport_ilp_push_group_filter-'.$this->course->id, $data->groupfilter);
            }

            if (isset($data->sectionfilter)) {
                $this->sectionid = $data->sectionfilter;
                set_user_preference('gradeexport_ilp_push_section_filter-'.$this->course->id, $data->sectionfilter);
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

        $allowed = false;
        switch ($this->statusfilter) {
            case (static::FILTER_ALL):
                $allowed = true;
                break;
            case (static::FILTER_NEEDS_ATTENTION):
                // For this status, we show grades that need editing or showed an error.
                if ($status === saved_grade::GRADING_STATUS_EDITING
                        || $status === saved_grade::GRADING_STATUS_FAILED
                        || $status === saved_grade::GRADING_STATUS_LOCKED) {
                    $allowed = true;
                }
                break;
            case (static::FILTER_IN_PROGRESS):
                if ($row->is_in_progress()) {
                    $allowed = true;
                }
                break;
            case (static::FILTER_ERROR):
                // For this status, we show grades that show an error, including locked error.
                // TODO - split internal locked vs error lock.
                if ($status === saved_grade::GRADING_STATUS_FAILED || $status === saved_grade::GRADING_STATUS_LOCKED) {
                    $allowed = true;
                }
                break;
            case (static::FILTER_DONE):
                // For this status, we show grades that show an error, including locked error.
                // TODO - split internal locked vs error lock.
                if ($status === saved_grade::GRADING_STATUS_PROCESSED) {
                    $allowed = true;
                }
                break;
        }

        if ($allowed && !empty($this->sectionid) && $this->sectionid != static::FILTER_ALL) {
            if (!sis_interface\factory::instance()->user_in_filter_section_id($row->get_course(),
                    $row->get_user(), $this->sectionid)) {
                $allowed = false;
            }
        }

        return $allowed;
    }

}
