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
require_once($CFG->libdir . '/csvlib.class.php');

use grade_item;
use grade_helper;
use grade_export_update_buffer;
use graded_users_iterator;

/**
 * The main export plugin class.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_exporter {

    protected $decimalpoints = 2; // TODO - Setting?

    protected $onlyactive = true; // TODO - Setting.

    protected $gradeitems;

    protected $coursegradeitem;

    protected $currentgradeitem;

    protected $groupid;

    protected $course;

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
    }

    protected function get_grade_columns() {
        return [$this->currentgradeitem->id => $this->currentgradeitem];
    }

    public function get_user_data() {
        $profilefields = grade_helper::get_user_profile_fields($this->course->id, true);
        $this->displaytype = [GRADE_DISPLAY_TYPE_REAL, GRADE_DISPLAY_TYPE_PERCENTAGE, GRADE_DISPLAY_TYPE_LETTER];

        // $geub = new grade_export_update_buffer();$status = $geub->track($grade);$geub->close(); TODO.
        $gui = new graded_users_iterator($this->course, $this->get_grade_columns(), $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields(true);
        $gui->init();

        $output = [];
        $userrows = [];

        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;

            // We only use one grade item, so that is easy...
            $grade = reset($userdata->grades);

            $userrow = new user_grade_row($user, $grade, $this->currentgradeitem);

            $userrows[] = $userrow;


        }
        $gui->close();


        return $userrows;
    }



    /**
     * Returns string representation of final grade
     * @param object $grade instance of grade_grade class
     * @param integer $gradedisplayconst grade display type constant.
     * @return string
     */
    public function format_grade($grade, $gradedisplayconst = null) {
        $displaytype = $this->displaytype;
        if (is_array($this->displaytype) && !is_null($gradedisplayconst)) {
            $displaytype = $gradedisplayconst;
        }

        $gradeitem = $this->gradeitems[$grade->itemid];

        // We are going to store the min and max so that we can "reset" the grade_item for later.
        $grademax = $gradeitem->grademax;
        $grademin = $gradeitem->grademin;

        // Updating grade_item with this grade_grades min and max.
        $gradeitem->grademax = $grade->get_grade_max();
        $gradeitem->grademin = $grade->get_grade_min();

        $formattedgrade = grade_format_gradevalue($grade->finalgrade, $gradeitem, false, $displaytype, $this->decimalpoints);

        // Resetting the grade item in case it is reused.
        $gradeitem->grademax = $grademax;
        $gradeitem->grademin = $grademin;

        return $formattedgrade;
    }
}


