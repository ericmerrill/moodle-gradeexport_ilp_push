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
 * Object for coverting for ILP and back.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local\ilp;

defined('MOODLE_INTERNAL') || die();

//use gradeexport_ilp_push\settings;
use gradeexport_ilp_push\grade_exporter;
use gradeexport_ilp_push\saved_grade;
use gradeexport_ilp_push\exception;
use stdClass;

/**
 * Object for coverting for ILP and back.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter {

    protected $grademappings = [grade_exporter::GRADE_TYPE_MIDTERM_1 => 'MidtermGrade1',
                                grade_exporter::GRADE_TYPE_MIDTERM_2 => 'MidtermGrade2',
                                grade_exporter::GRADE_TYPE_MIDTERM_3 => 'MidtermGrade3',
                                grade_exporter::GRADE_TYPE_MIDTERM_4 => 'MidtermGrade4',
                                grade_exporter::GRADE_TYPE_MIDTERM_5 => 'MidtermGrade5',
                                grade_exporter::GRADE_TYPE_MIDTERM_6 => 'MidtermGrade6',
                                grade_exporter::GRADE_TYPE_FINAL => 'FinalGrade'];

    /**
     * Takes a set of saved grades and creates a request JSON for them.
     *
     * They all must have the same submitter ID.
     *
     * @param saved_grade[] $grades An array of saved grades to send.
     * @return string JSON to send
     * @throws exception\exception_submitter_mismatch
     */
    public function create_request_for_saved_grades(array &$grades) {
        $request = new stdClass();

        $firstrow = reset($grades);
        $requestor = $firstrow->submitterilpid;
        $request->ModifiedBy = $requestor;

        $records = [];
        foreach ($grades as $grade) {
            if ($grade->submitterilpid !== $requestor) {
                throw new exception\exception_submitter_mismatch();
            }
            $records[] = $this->create_saved_grade_request($grade);
        }
        $request->StudentGrades = $records;

        $json = $this->convert_to_json($request);

        return $json;
    }

    protected function create_saved_grade_request(saved_grade $grade) {
        $record = new stdClass();
        $record->CourseId = $grade->courseilpid;
        $record->StudentId = $grade->studentilpid;

        if (!isset($this->grademappings[$grade->gradetype])) {
            // TODO - Throw exception.
            return null;
        }
        $gradekey = $this->grademappings[$grade->gradetype];
        $record->$gradekey = $grade->grade;

        if (isset($grade->datelastattended)) {
            $record->LastAttendanceDate = $this->convert_time_to_ilp($grade->datelastattended);
        }

        if (isset($grade->incompletegrade)) {
            $record->DefaultIncompleteGrade = $grade->incompletegrade;
        }

        if (isset($grade->incompletedeadline)) {
            $record->FinalGradeExpirationDate = $this->convert_time_to_ilp($grade->incompletedeadline);
        }

        // TODO - NeverAttended.

        return $record;
    }

    protected function convert_to_json($data) {
        // TODO - check settings.
        return json_encode($data);
    }

    protected function convert_time_to_ilp($timestamp) {
        // TODO - look into this...
        return date('c', $timestamp);
    }
}


