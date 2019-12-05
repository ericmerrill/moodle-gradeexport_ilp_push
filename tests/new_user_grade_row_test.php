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

use gradeexport_ilp_push\redo\user_grade_row;

global $CFG;
require_once($CFG->dirroot.'/grade/export/ilp_push/tests/helper.php');

class new_user_grade_row_test extends ilp_push_testcase {

    public function test_constructor() {
        $setup = $this->setup_test_course();

        $ugr = new user_grade_row($setup['students'][0], $setup['gradeexporter']);
        $this->assertInstanceOf(user_grade_row::class, $ugr);
    }

    public function test_load_existing_rows() {
        // TODO.

        $setup = $this->setup_test_course();

        $ugr = new user_grade_row($setup['students'][0], $setup['gradeexporter']);
        $this->run_protected_method($ugr, 'load_existing_rows');
    }
}
