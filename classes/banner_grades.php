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
use gradeexport_ilp_push\local\data\grade_mode;

/**
 * Deals with the interaction of banner grading.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class banner_grades {

    protected static $failingids = null;

    protected static $incompleteids = null;

    protected static $grademodes = null;

    protected static function load_grade_modes() {
        if (!is_null(self::$grademodes)) {
            return self::$grademodes;
        }
        self::$grademodes = grade_mode::get_for_params([], 'sortorder ASC');

        self::$failingids = [];
        self::$incompleteids = [];
        foreach (self::$grademodes as $grademode) {
            $options = $grademode->get_all_grade_options();
            foreach ($options as $option) {
                if (!empty($option->isincomplete)) {
                    self::$incompleteids[$option->id] = $option->id;
                }
                if (!empty($option->requirelastdate)) {
                    self::$failingids[$option->id] = $option->id;
                }
            }
        }
    }

    public static function get_grade_modes_menu() {
        self::load_grade_modes();

        $output = [];
        foreach (self::$grademodes as $mode) {
            if (empty($mode->enabled)) {
                continue;
            }
            $output[$mode->id] = $mode->name;
        }

        return $output;
    }

    public static function get_grade_mode($grademodeid) {
        self::load_grade_modes();

        if (isset(self::$grademodes[$grademodeid])) {
            return self::$grademodes[$grademodeid];
        }

        return reset(self::$grademodes);
    }

    public static function get_possible_grade_modes() {

    }

    public static function get_possible_grades($userrow) {
        $grademode = $userrow->get_current_grade_mode();


        $gradeoptions = $grademode->get_current_grade_options();

        $options = [];
        foreach ($gradeoptions as $option) {
            if (isset($option->displayname)) {
                $value = $option->displayname;
            } else {
                $value = $option->bannervalue;
            }
            $options[$option->id] = $value;
        }

        return $options;

        // TODO - Fallback to these defaults for old submissions.
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
        // TODO - Redo/remove.

        return 0;

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
        // TODO - Redo.
        return 0;
        return self::find_key_for_letter('F');
    }

    public static function get_failing_grade_ids() {
        self::load_grade_modes();

        return self::$failingids;
    }

    public static function get_incomplete_grade_ids() {
        self::load_grade_modes();

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


