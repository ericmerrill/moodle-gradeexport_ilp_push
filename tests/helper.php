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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use gradeexport_ilp_push\grade_exporter;

/**
 * A testcase that contains some extra tools.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ilp_push_testcase extends xml_helper {
    /**
     * Setup a course to test grading with.
     */
    protected function setup_test_course() {
        global $DB, $PAGE;
        $output = [];

        $this->resetAfterTest();

        $term = $this->create_lmb_term(['sdid' => '201940'], true);
        $section = $this->create_lmb_section(['termsdid' => '201940'], true);

        $output['course'] = $DB->get_record('course', ['idnumber' => $section->sdid]);

        $students = [];
        for ($i = 0; $i < 5; $i++) {
            $student = $this->create_lmb_person(null, true);
            $this->create_lmb_enrol($section, $student, ['roletype' => '01'], true);
            $output['students'][$i] = $DB->get_record('user', ['idnumber' => $student->sdid]);
        }
        $teachers = [];
        for ($i = 0; $i < 5; $i++) {
            $teacher = $this->create_lmb_person(null, true);
            $this->create_lmb_enrol($section, $teacher, ['roletype' => '02'], true);
            $output['teachers'][$i] = $DB->get_record('user', ['idnumber' => $teacher->sdid]);
        }

        // Just need to set any random URL to surpress error, sigh...
        $PAGE->set_url(new \moodle_url('/'));

        $output['gradeexporter'] = new grade_exporter($output['course']);

        return $output;
    }



}
