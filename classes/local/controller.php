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
 * Controller for managing ILP exchanges.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local;

defined('MOODLE_INTERNAL') || die();

//use gradeexport_ilp_push\settings;
use gradeexport_ilp_push\saved_grade;
use gradeexport_ilp_push\locks;
use gradeexport_ilp_push\log;
use gradeexport_ilp_push\id_converter;

/**
 * Controller for managing ILP exchanges.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {

    public function process_course_user($courseid, $userid) {
        log::instance()->start_message("Processing grades for course {$courseid} and user {$userid}.");

        // We are going to process each course ILP id seperately, just in case.
        $ilpids = $this->get_pending_course_ilp_ids($courseid, $userid);
        if (empty($ilpids)) {
            // Nothing to do.
            log::instance()->end_message("No records to process. Exiting.", log::ERROR_NONE);
            return;
        }

        $submitterilpid = id_converter::get_user_id_for_userid($userid);

        if (empty($submitterilpid)) {
            log::instance()->end_message("Could not get submitting user's ILP ID. Exiting.", log::ERROR_WARN);
            return;
        }

        foreach ($ilpids as $courseilpid) {
            log::instance()->start_message("Processing grades for ILP course {$courseilpid} and submitter {submitterilpid}.");
            // Grab a lock to make sure nobody else is working on this right now.
            if (!$lock = locks::get_course_lock($courseilpid)) {
                log::instance()->end_message("Could not get course lock. Exiting.", log::ERROR_WARN);
                log::instance()->end_message();
                return;
            }

            // TODO - chunk into small(er) groups.
            $grades = saved_grade::get_for_submitter_course($submitterilpid, $courseilpid, saved_grade::GRADING_STATUS_SUBMITTED);

            if (empty($grades)) {
                // Nothing to do.
                $lock->release();
                log::instance()->end_message("No grades to process");
                break;
            }

            $this->set_grades_status($grades, saved_grade::GRADING_STATUS_PROCESSING);

            // Now that we have marked these as in processing, we can release the lock.
            $lock->release();

            log::instance()->log_line("Processing ".count($grades)." grades.");
            $this->process_grade_request($grades);

            log::instance()->end_message("Done");
        }

        log::instance()->end_message();
    }

    protected function get_pending_course_ilp_ids($courseid, $userid) {
        global $DB;

        $sql = 'SELECT courseilpid FROM {'.saved_grade::TABLE.'}
                 WHERE courseid = :courseid
                   AND submitterid = :userid
                   AND status = :status
              GROUP BY courseilpid
              ORDER BY MIN(usersubmittime)';

        $params = ['courseid' => $courseid, 'userid' => $userid, 'status' => saved_grade::GRADING_STATUS_SUBMITTED];

        $ilpids = $DB->get_fieldset_sql($sql, $params);
        if (empty($ilpids)) {
            return false;
        }

        return $ilpids;
    }

    /**
     * Depreciate. TODO.
     *
     * @param
     * @return
     */
    public function process_course_submitter($courseilp, $submitterilp) {
        debugging("process_course_submitter depreciated");
        // Grab a lock to make sure nobody else is working on this right now.
//         if (!$lock = locks::get_course_submitter_lock($courseilp, $submitterilp)) {
//             // Couldn't get lock on course. TODO - Log.
//             return;
//         }
//
//         // TODO - chunk into small(er) groups.
//         $grades = saved_grade::get_for_submitter_course($submitterilp, $courseilp, saved_grade::GRADING_STATUS_SUBMITTED);
//
//         if (empty($grades)) {
//             // Nothing to do.
//             $lock->release();
//             return;
//         }
//
//         $this->set_grades_status($grades, saved_grade::GRADING_STATUS_PROCESSING);
//
//         // Now that we have marked these as in processing, we can release the lock.
//         $lock->release();
//
//         // TODO - for most exceptions we probably want to put the grade back to submitted status so we try again later.
//
//         $this->process_grade_request($grades);
    }

    protected function process_grade_request($grades) {
        $converter = new ilp\converter();
        // TODO - exception handling?
        $request = $converter->create_request_for_saved_grades($grades);

        $conn = new ilp\connector();

        // TODO - Catch exceptions.
        try {
            $response = $conn->send_request('grades', $request);
        } catch (exception\connector_exception $e) {
            // In cases where we have a connection erorr, we probably just want to reset the grades back to the waiting state.
            $this->set_grades_status($grades, saved_grade::GRADING_STATUS_RESUBMIT, true);

            log::instance()->log_exception($e);

            return;
        }

        $connectionerror = false;
        if (isset($response->isConnectivityFailure) && $response->isConnectivityFailure === true) {
            log::instance()->log_line('ILP reported connectivity failure.', log::ERROR_WARN);
            $connectionerror = true;
        }

        if (!isset($response->messages)) {
            // For a connection error, we'll reset them.
            log::instance()->log_line('No response messages received from ILP.', log::ERROR_WARN);
            if ($connectionerror || !isset($response->isConnectivityFailure)) {
                // We are going to just reset them all since ILP reported a connection failure, or reported no status.
                $this->set_grades_status($grades, saved_grade::GRADING_STATUS_RESUBMIT, true);
                return;
            }
            // We are going to just place an empty array here, so the rest can do it's thing.
            $response->messages = [];
        }

        // Setup an array we will use to see which grades we have processed responses for.
        $tracking = [];
        foreach ($grades as $grade) {
            $tracking[$grade->id] = $grade;
        }

        foreach ($response->messages as $message) {
            $currgrade = null;
            foreach ($grades as $grade) {
                if ($grade->studentilpid === $message->data->studentId) {
                    $currgrade = $grade;
                    break;
                }
            }

            if (is_null($currgrade)) {
                // TODO - log. Could not find record to go with message.
                error_log('Could not find grade to go with message.'.var_export($message, true));
                break;
            }

            if ($message->data->status === 'success') {
                $currgrade->status = saved_grade::GRADING_STATUS_PROCESSED;
            } else if ($message->data->status === 'failure') {
                $currgrade->status = saved_grade::GRADING_STATUS_FAILED;
                $currgrade->mark_failure();
            } else {
                // TODO - log unknown status.
                error_log('Unknown status '.$message->data->status.var_export($message, true));
                $currgrade->status = saved_grade::GRADING_STATUS_FAILED;
                $currgrade->mark_failure();
            }

            if (!empty($message->message)) {
                if (isset($currgrade->ilpmessage)) {
                    $currgrade->ilpmessage .= "\n".$message->message;
                } else {
                    $currgrade->ilpmessage = $message->message;
                }

                if (stripos($message->message, 'GE09') !== false) {
                    // GE09 indicates a rolled grade.
                    $currgrade->status = saved_grade::GRADING_STATUS_LOCKED;
                }
            }

            $currgrade->save_to_db();

            // Remove it from our tracking array.
            unset($tracking[$currgrade->id]);
        }

        // Anything left in the tracking array didn't have a response from ILP.
        foreach ($tracking as $missing) {
            log::instance()->log_line('Grade didn\'t receive a response message.', log::ERROR_WARN, $missing);

            if ($connectionerror) {
                $missing->status = saved_grade::GRADING_STATUS_RESUBMIT;
            } else {
                $missing->status = saved_grade::GRADING_STATUS_FAILED;
            }
            $missing->mark_failure();
            $missing->ilpmessage = get_string('ilp_response_missing', 'gradeexport_ilp_push');
            $missing->save_to_db();
        }

        return;
    }

    protected function set_grades_status($grades, $status, $failure = false) {
        foreach ($grades as $grade) {
            $grade->status = $status;
            if ($failure) {
                $grade->mark_failure();
            }
            $grade->save_to_db();
        }
    }
}


