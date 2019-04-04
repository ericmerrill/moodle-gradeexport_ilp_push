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

    protected $decimalpoints = 2; // TODO - Setting?

    protected $onlyactive = true; // TODO - Setting.

    protected $gradeitems;

    protected $coursegradeitem;

    protected $currentgradeitem;

    protected $groupid;

    protected $course;

    protected $userrows;

    protected $gradetype = self::GRADE_TYPE_FINAL;


    /**
     * Constructor to set everything up.
     *
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     */
    public function __construct($course, $groupid = null) {
        $this->course = $course;
        $this->groupid = $groupid;

        // Get all course grade items and the course grade item.
        $this->gradeitems = grade_item::fetch_all(array('courseid'=>$this->course->id));
        $this->coursegradeitem = grade_item::fetch_course_item($course->id);

        $this->currentgradeitem = $this->coursegradeitem; // TODO.

        $this->build_user_data();
    }

    protected function get_grade_columns() {
        return [$this->currentgradeitem->id => $this->currentgradeitem];
    }

    protected function build_user_data() {
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

            $userrow = new user_grade_row($user, $this, $grade, $this->currentgradeitem);

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

    public function get_user_data() {
        return $this->userrows;
    }

    public function export_for_template(\renderer_base $renderer) {
        global $USER;

        $output = new stdClass;

        $rows = [];
        foreach ($this->userrows as $row) {
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
        // TODO check sesskey.
        // TODO check grader id.
        // TODO check courseid.

        foreach ($this->userrows as $row) {
            $row->process_data($data);
        }
    }
}


