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

use stdClass;

/**
 * Deals with the interaction of banner grading.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class banner_grades {

    protected static $failing = ['F'];
    protected static $failingids = null;

    protected static $incomplete = ['I'];
    protected static $incompleteids = null;

    public static function get_possible_grades($userrow = null) {
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
                    12 => 'I'];

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

    public static function get_ilp_grade_for_key($key) {
        $options = self::get_possible_grades();

        if (!isset($options[$key])) {
            return null;
        }

        return $options[$key];
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

    public static function grade_key_is_failing($key) {
        $keys = static::get_failing_grade_ids();

        if (isset($keys[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public static function grade_key_is_incomplete($key) {
        $keys = static::get_incomplete_grade_ids();

        if (isset($keys[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_failing_grade_ids() {
        // TODO - need to find the logic behind this better.
        if (!is_null(self::$failingids)) {
            return self::$failingids;
        }

        $ids = [];
        foreach (self::$failing as $grade) {
            $key = self::find_key_for_letter($grade);
            $ids[$key] = $key;
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
            $key = self::find_key_for_letter($grade);
            $ids[$key] = $key;
        }

        self::$incompleteids = $ids;

        return self::$incompleteids;
    }

    public static function get_allowed_last_attend_dates($course, $format = false, $tz = 99) {
        $dates = new stdClass();

        $dates->start = $course->startdate;

        if (empty($course->enddate)) {
            // We just have to guess. TODO better.
            $end = $course->startdate + (3600 * 24 * 7 * 16);
        } else {
            $end = $course->enddate;
        }

        if ($end > time()) {
            $end = time();
        }

        $dates->end = $end;

        if (!$format) {
            return $dates;
        }

        // Convert to a format.
        $dates->start = date_format_string($dates->start, $format, $tz);
        $dates->end = date_format_string($dates->end, $format, $tz);

        return $dates;
    }

    public static function get_allowed_last_incomplete_deadline_dates($course, $format = false, $tz = 99) {
        $dates = new stdClass();

        if (empty($course->enddate)) {
            // We just have to guess. TODO better.
            $courseend = $course->startdate + (3600 * 24 * 7 * 16);
        } else {
            $courseend = $course->enddate;
        }

        // The day after the end of the course is the first allowed date (I think TODO).
        $courseend += 3600 * 24;

        $dates->start = $courseend;

        $dates->end = $courseend + (3600 * 24 * 380);

        if (!$format) {
            return $dates;
        }

        // Convert to a format.
        $dates->start = date_format_string($dates->start, $format, $tz);
        $dates->end = date_format_string($dates->end, $format, $tz);

        return $dates;
    }
}


