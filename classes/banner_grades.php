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
 * Deals with the interaction of banner grading.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

/**
 * Deals with the interaction of banner grading.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class banner_grades {

    protected static $failing = ['F', 'Fail'];
    protected static $failingids = null;

    protected static $incomplete = ['I'];
    protected static $incompleteids = null;

    public static function get_possible_grades() {
        // TODO - Grade options.
        $options = [1 => 'A',
                    2 => 'A-',
                    3 => 'B+',
                    4 => 'B',
                    5 => 'B-',
                    6 => 'C+',
                    7 => 'C',
                    8 => 'C-',
                    9 => 'D+',
                    10 => 'D',
                    11 => 'F',
                    12 => 'I',
                    13 => 'Pass',
                    14 => 'Fail'];

        return $options;
    }

    public static function find_key_for_letter($letter) {
        $options = self::get_possible_grades();
        $key = array_search($letter, $options, true);

        if ($key === false) {
            return false;
        }

        return $key;
    }

    public static function get_banner_equivilant_grade($userrow) {
        // TODO - Better options here...

        $letter = $userrow->get_formatted_grade(GRADE_DISPLAY_TYPE_LETTER);

        return self::find_key_for_letter($letter);
    }

    public static function get_default_incomplete_grade() {
        // TODO - need to find the logic behind this better.

        return self::find_key_for_letter('F');
    }

    public static function get_failing_grade_ids() {
        // TODO - need to find the logic behind this better.
        if (!is_null(self::$failingids)) {
            return self::$failingids;
        }

        $ids = [];
        foreach (self::$failing as $grade) {
            $ids[] = self::find_key_for_letter($grade);
        }

        self::$failingids = $ids;

        return self::$failingids;
    }

    public static function get_incomplete_grade_ids() {
        // TODO - need to find the logic behind this better.
        if (!is_null(self::$incompleteids)) {
            return self::$incompleteids;
        }

        $ids = [];
        foreach (self::$incomplete as $grade) {
            $ids[] = self::find_key_for_letter($grade);
        }

        self::$incompleteids = $ids;

        return self::$incompleteids;
    }
}


