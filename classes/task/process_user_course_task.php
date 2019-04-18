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
 * An adhoc task for processing a submitted teacher/course combo.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\task;

defined('MOODLE_INTERNAL') || die();

use core\task;
use gradeexport_ilp_push\local\controller;
use gradeexport_ilp_push\log;
use gradeexport_ilp_push\saved_grade;
use stdClass;


/**
 * An adhoc task for processing a submitted teacher/course combo.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_user_course_task extends task\adhoc_task {
    /**
     * Process grades for this course and user.
     */
    public function execute() {
        log::instance()->start_message("Executing adhoc user-course task.");

        $data = $this->get_custom_data();

        if (empty($data) || empty($data->userid) || empty($data->courseid)) {
            log::instance()->end_message("Adhoc data did not contain required fields. Exiting.", log::WARN);
            return;
        }

        $controller = new controller();

        $controller->process_course_user($data->courseid, $data->userid);

        log::instance()->end_message();
    }

    /**
     * Register an adhoc task for the given userid and courseid combo, if needed.
     *
     * @param int $userid The database id of the user
     * @param int $courseid The database id of the course
     */
    public static function register_task_for_user_course($userid, $courseid) {
        global $DB;

        $params = ['submitterid' => $userid,
                   'courseid' => $courseid,
                   'status' => saved_grade::GRADING_STATUS_SUBMITTED];
        if (!$DB->record_exists(saved_grade::TABLE, $params)) {
            // None to register.
            return;
        }

        // TODO - Try to check for existing tasks.

        log::instance()->log_line("Registering adhoc task for user {$userid}, course {$courseid}.");

        $data = new stdClass();
        $data->userid = $userid;
        $data->courseid = $courseid;

        $task = new static();
        $task->set_custom_data($data);
        $task->set_userid($userid);
        $task->set_component('gradeexport_ilp_push');
        task\manager::queue_adhoc_task($task);
    }
}


