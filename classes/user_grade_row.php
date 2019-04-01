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

use stdClass;
use templatable;
use html_writer;
use moodle_url;

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




    /**
     * Basic constructor.
     */
    public function __construct($user, $exporter, $grade, $gradeitem) {
        $this->user = $user;
        $this->exporter = $exporter;
        $this->course = $exporter->get_course();
        $this->grade = $grade;
        $this->gradeitem = $gradeitem;
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

    public function get_current_menu_selection() {
        $letter = null;
        if ($this->currentsavedgrade) {
            // TODO - May need to refine how this works later...
            $letter = $this->currentsavedgrade->grade;
        }

        if (is_null($letter)) {
            $letter = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);
        }

        return banner_grades::find_key_for_letter($letter);
    }

    public function get_current_incomplete_menu_selection() {
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

        return $formatted;
    }

    public function export_for_template(\renderer_base $renderer) {
        global $OUTPUT, $COURSE;
        //$source = $this->get_data_source();

        //$output = $source->export_for_template($renderer);
        $output = new stdClass();

        $fullname = fullname($this->user);
        $output->userimage = $OUTPUT->user_picture($this->user, ['visibletoscreenreaders' => false]);
        $output->fullname = $fullname;
        $output->username = $this->user->username;
        $output->userid = $this->user->id;
        $output->formid = $this->get_form_id();

        $params = ['id' => $this->user->id, 'course' => $COURSE->id];
        $output->fullnamelink = html_writer::link(new moodle_url('/user/view.php', $params), $fullname);

        $lettergrade = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);
        $output->gradeletter = $lettergrade;
        $output->gradereal = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_REAL);
        $output->gradepercent = $this->get_formatted_grade(GRADE_DISPLAY_TYPE_PERCENTAGE);

        $output->gradeselect = $renderer->render_select_menu($this);

        $output->incompletegradeselect = $renderer->render_incomplete_select_menu($this);

        $currentkey = banner_grades::find_key_for_letter($output->gradeletter);
        $output->showincomplete = (bool)in_array($currentkey, banner_grades::get_incomplete_grade_ids());
        $output->showfailing = (bool)in_array($currentkey, banner_grades::get_failing_grade_ids());

        return $output;
    }

    protected function get_next_revision_number() {
        if ($this->currentsavedgrade) {
            return $this->currentsavedgrade->revision + 1;
        }

        return 0;
    }

    public function process_data(stdClass $data, grade_exporter $exporter) {
        global $USER;

        $save = new saved_grade();

        $save->studentid = $this->user->id;
        $save->studentilpid = $this->user->idnumber; // TODO - This can be sourced elsewhere.
        $save->courseid = $this->course->id;
        $save->courseilpid = $this->course->idnumber; // TODO - Won't work with crosslists...
        $save->submitterid = $USER->id;
        $save->submitterilpid = $USER->idnumber; // TODO - This can be sourced elsewhere.

        $save->revision = $this->get_next_revision_number();


        print "<pre>";print_r($save);print "</pre>";

    }

}


