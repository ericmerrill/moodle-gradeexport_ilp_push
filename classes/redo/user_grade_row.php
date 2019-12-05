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

namespace gradeexport_ilp_push\redo;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->libdir . '/form/dateselector.php');

use stdClass;
use gradeexport_ilp_push\grade_exporter; // TODO - Remove when renamed.
//use templatable;
//use html_writer;
//use moodle_url;
use gradeexport_ilp_push\local\sis_interface;
use gradeexport_ilp_push\saved_grade; // TODO - Remove when renamed.
//use gradeexport_ilp_push\banner_grades;

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
class user_grade_row { //implements templatable {
    // TODO - Idea saved_grade temp flag?

    /** @var saved_grade The most recent saved grade record. */
    protected $currentsavedgrade = null;

    /** @var saved_grade[] All the past saved grades for this row. */
    protected $pastsavedgrades = [];

    /** @var stdClass The user record this row is for. */
    protected $user = null;

    /** @var stdClass The course this row is for. */
    protected $course = null;

    /** @var grade_exporter The exporter that instantiated this grade row. */
    protected $exporter = null;

    /** @var sis_interface The interface to the SIS in use. */
    protected $sis = null;

    // ****************** Loading.
    /**
     * Basic constructor.
     */
    public function __construct(stdClass $user, grade_exporter $exporter) {
        $this->user = $user;
        $this->exporter = $exporter;
        $this->course = $exporter->get_course();
        $this->sis = sis_interface\factory::instance();

        $this->load_existing_rows();
    }

    /**
     * Load the existing saved grades for this row.
     */
    protected function load_existing_rows() {
        if (!$savedgrades = saved_grade::get_records_for_user_course($this->user, $this->course)) {
            return;
        }

        $this->currentsavedgrade = end($savedgrades);
        reset($savedgrades); // Send the pointer back to the start.
        $this->pastsavedgrades = $savedgrades;

        // Get the current grade mode;
        //$grademodeid = $this->currentsavedgrade->grademodeid;
        //$this->grademode = banner_grades::get_grade_mode($grademodeid);
    }


    // ****************** Accessors.

    public function get_current_grade_row() {
        if (!is_null($this->currentsavedgrade)) {
            return $this->currentsavedgrade;
        }

        $grade = $this->create_new_saved_grade();

        $this->currentsavedgrade = $grade;
        $this->pastsavedgrades[$grade->revision] = $grade;
    }

//     public function __isset($name) {
//         var_export($name);
//         if (isset($this->$name)) {
//             return true;
//         }
//         return false;
//     }
//
//     public function __get($name) {
//         var_export($name);
//         if (!isset($this->$name)) {
//             return null;
//         }
//         return $this->$name;
//     }



    // ****************** Processing.

    // ****************** Processing - Saved grades.
    protected function create_new_saved_grade() {
        $grade = new saved_grade();
        $grade->studentid = $this->user->id;
        $grade->studentilpid = $this->sis->get_user_id($this->user);
        $grade->courseid = $this->course->id;
        $grade->courseilpid = $this->sis->get_course_id_for_user($this->course, $this->user);

        $grade->revision = $this->get_next_revision_number();

        return $grade;
    }

    protected function get_next_revision_number() {
        if ($this->currentsavedgrade) {
            return $this->currentsavedgrade->revision + 1;
        }

        return 0;
    }

    // ****************** Processing - Moodle grades.

    // ****************** Template Render.

}
