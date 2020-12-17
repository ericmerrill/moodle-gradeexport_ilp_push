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
 * An interface for getting information from the SIS.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local\sis_interface;

defined('MOODLE_INTERNAL') || die();

use core_user;

/**
 * An interface for getting information from the SIS.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    public function get_user_id($user) {
        if (empty($user->idnumber)) {
            return null;
        }

        return $user->idnumber;
    }

    public function get_user_display_id($user) {
        if (isset($user->gid)) {
            return $user->gid;
        } else if (!empty($user->idnumber)) {
            return $user->idnumber;
        } else {
            return false;
        }
    }

    public function get_user_id_for_userid($userid) {
        $user = core_user::get_user($userid);

        return $this->get_user_id($user);
    }

    public function get_course_id_for_user($course, $user) {
        if (empty($course->idnumber)) {
            return null;
        }

        return $course->idnumber;
    }

    /**
     * Check if a particular user is allowed to grade a given course.
     *
     * @param
     * @return true|string True if allowed, or an error string if not.
     */
    public function teacher_allowed_to_grade_course($user, $course) {
        global $DB;

        if (is_null($this->get_user_id($user))) {
            return get_string('grader_no_id', 'gradeexport_ilp_push');
        }

        if (empty($course->idnumber)) {
            return get_string('course_no_id', 'gradeexport_ilp_push');
        }

        return true;
    }

    /**
     * Check if a particular user is a gradable user in the course.
     *
     * @param object $user The student user to check
     * @param object $course The course to check the student in
     * @return true|string True if allowed, or an error string if not.
     */
    public function is_gradable_user_in_course($user, $course) {
        if (empty($this->get_user_id($user))) {
            return false;
        }

        if (empty($this->get_course_id_for_user($course, $user))) {
            return false;
        }

        return true;
    }

}


