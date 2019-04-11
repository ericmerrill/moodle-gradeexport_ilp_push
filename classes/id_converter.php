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
 * A class that is used to provide ILP IDs to use.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

use \core_user;

/**
 * A class that is used to provide ILP IDs to use.
 *
 * Override this to change how users and courses are ID'ed in ILP.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class id_converter {

    protected static $courseenrolids = [];

    public static function get_user_id($user) {
        if (empty($user->idnumber)) {
            return null;
        }

        return $user->idnumber;
    }

    public static function get_user_id_for_userid($userid) {
        $user = core_user::get_user($userid);

        return static::get_user_id($user);
    }

    public static function get_course_id_for_user($course, $user) {



        if (!isset(static::$courseenrolids[$course->id])) {
            static::load_course_user_mappings($course);
        }

        if (!empty(static::$courseenrolids[$course->id][$user->id])) {
            return static::$courseenrolids[$course->id][$user->id];
        }

        if (empty($course->idnumber)) {
            return null;
        }

        // TODO - Crosslists.
        return $course->idnumber;
    }

    protected static function load_course_user_mappings($course) {
        global $DB;

        // This is currently set to do the magic way that LMB NXT works. TODO - generalize.
        $sql = "SELECT ue.id, ue.userid, e.customchar1 FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = :courseid";

        $params = ['courseid' => $course->id];

        $records = $DB->get_recordset_sql($sql, $params);

        static::$courseenrolids[$course->id] = [];
        $map = [];
        foreach ($records as $record) {
            if (isset($map[$record->userid])) {
                $text = "User {$record->userid} has more than one enrolments in {$course->id}.";
                log::instance()->log_line($text, log::ERROR_WARN);
                if (empty($map[$record->userid])) {

                    $map[$record->userid] = $record->customchar1;
                }
            } else {
                $map[$record->userid] = $record->customchar1;
            }
        }
        static::$courseenrolids[$course->id] = $map;
        $records->close();
    }
}


