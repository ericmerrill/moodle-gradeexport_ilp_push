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
 * A class that checks to see if valid data is entered for a grade.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

/**
 * A class that checks to see if valid data is entered for a grade.
 *
 * Override this to change rules.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_validator {


    public static function validate_row(saved_grade $grade, grade_exporter $exporter) {
        $course = $exporter->get_course();

        $results = ['errors' => []];
        // Must have a valid grade.
        if ($grade->confirmed) {
            if (is_null($grade->grade)) {
                $results['errors']['grade'] = get_string('invalid_grade', 'gradeexport_ilp_push');
            }
        }

        // TODO - use banner_grades or SIS and settings to get dates and rules.

        // If the grade is failing, there must be a date last attended.
        if (banner_grades::grade_key_is_failing($grade->gradekey)) {
            if (is_null($grade->datelastattended)) {
                $results['errors']['datelastattended'] = get_string('invalid_datelastattended_missing', 'gradeexport_ilp_push');
            } else if ($grade->datelastattended > time()) {
                // Cannot be later than today.
                // TODO do time/date check better.
                $results['errors']['datelastattended'] = get_string('invalid_datelastattended_today', 'gradeexport_ilp_push');
            } else if ($grade->datelastattended < $course->startdate) {
                // The date last attended must be withing a certain date range.
                // TODO better date ranges and error messages...
                $results['errors']['datelastattended'] = get_string('invalid_datelastattended_early', 'gradeexport_ilp_push');
            } else if ($grade->datelastattended > $course->enddate) {
                $results['errors']['datelastattended'] = get_string('invalid_datelastattended_late', 'gradeexport_ilp_push');
            }
        }


        if (banner_grades::grade_key_is_incomplete($grade->gradekey)) {
            if (is_null($grade->incompletegrade)) {
                // If the grade is incomplete, there must be an incomplete grade.
                $results['errors']['incompletegrade'] = get_string('invalid_incomplete_grade_missing', 'gradeexport_ilp_push');
            } else if ($grade->incompletegradekey != banner_grades::get_default_incomplete_grade()) {
                // If present, the incomplete grade must be the default (settings dependent).
                // TODO make setting dependent. Probably move to overridable class.
                $results['errors']['incompletegrade'] = get_string('invalid_incomplete_grade_wrong', 'gradeexport_ilp_push');
            }

            // If the grade is incomplete, there must be a deadline date.
            if (is_null($grade->incompletedeadline)) {
                // TODO - this might not be required...
                $results['errors']['incompletedeadline'] = get_string('invalid_incomplete_date_missing', 'gradeexport_ilp_push');
            } else if ($grade->incompletedeadline < $course->enddate) {
                // If present, the incomplete date must be within a certain timeline (settings dependent).
                // TODO better date ranges and error messages...
                $results['errors']['incompletedeadline'] = get_string('invalid_incomplete_date_early', 'gradeexport_ilp_push');
            } else if ($grade->incompletedeadline > ($course->enddate + (366 * 3600 * 24))) {
                $results['errors']['incompletedeadline'] = get_string('invalid_incomplete_date_late', 'gradeexport_ilp_push');
            }
        }

        // TODO - more rules.








        return $results;
    }
}


