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
use gradeexport_ilp_push\local\sis_interface;
use gradeexport_ilp_push\event;
use gradeexport_ilp_push\notifications;
use stdClass;
use core_user;

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

        $submitterilpid = sis_interface\factory::instance()->get_user_id_for_userid($userid);

        if (empty($submitterilpid)) {
            log::instance()->end_message("Could not get submitting user's ILP ID. Exiting.", log::ERROR_WARN);
            return;
        }

        $submitteruser = core_user::get_user($userid);

        foreach ($ilpids as $courseilpid) {
            $successes = 0;
            $errors = 0;
            log::instance()->start_message("Processing grades for ILP course {$courseilpid} and submitter {$submitterilpid}.");
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
            $results = $this->process_grade_request($grades);

            // Send the message.
            $data = new stdClass();
            $data->url = new \moodle_url('/grade/export/ilp_push/index.php', ['id' => $courseid]);
            $data->crn = $courseilpid;
            if ($results['errors']) {
                $data->errorcount = $results['errors'];
                $data->successcount = $results['successes'];
                notifications::send_error($data, $courseid, $submitteruser);
            } else if ($results['successes']) {
                $data->count = $results['successes'];
                notifications::send_success($data, $courseid, $submitteruser);
            }

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

    protected function process_grade_request($grades) {
        $results = ['successes' => 0, 'errors' => 0, 'resubmits' => 0, 'errormsgs' => []];

        $converter = new ilp\converter();
        // TODO - exception handling?
        $request = $converter->create_request_for_saved_grades($grades);

        $conn = new ilp\connector();

        try {
            log::instance()->end_message("Request: ".var_export($request, true), log::ERROR_NONE);
            $response = $conn->send_request('grades', $request);
            log::instance()->end_message("Response: ".var_export($response, true), log::ERROR_NONE);
        } catch (exception\connector_exception $e) {
            // In cases where we have a connection erorr, we probably just want to reset the grades back to the waiting state.
            $str = get_string('ilp_connection_error', 'gradeexport_ilp_push');
            $this->set_grades_status($grades, saved_grade::GRADING_STATUS_RESUBMIT, true, $str);

            log::instance()->log_exception($e);

            $results['resubmits'] = count($grades);
            return $results;
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
                $str = get_string('ilp_no_response', 'gradeexport_ilp_push');
                $this->set_grades_status($grades, saved_grade::GRADING_STATUS_RESUBMIT, true, $str);
                $results['resubmits'] = count($grades);
                return $results;
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
            $success = false;

            $currgrade = null;
            foreach ($grades as $grade) {
                if ($grade->studentilpid === $message->data->studentId) {
                    $currgrade = $grade;
                    break;
                }
            }

            if (is_null($currgrade)) {
                log::instance()->log_line('Could not find grade to go with message.'.var_export($message, true), log::ERROR_WARN);
                continue;
            }

            if ($message->data->status === 'success') {
                $currgrade->status = saved_grade::GRADING_STATUS_PROCESSED;
                $success = true;
            } else if ($message->data->status === 'failure') {
                $currgrade->status = saved_grade::GRADING_STATUS_FAILED;
                $currgrade->mark_failure();
            } else {
                log::instance()->log_line('Unknown status '.$message->data->status.var_export($message, true), log::ERROR_WARN);
                $currgrade->status = saved_grade::GRADING_STATUS_FAILED;
                $currgrade->mark_failure(get_string('ilp_unknown_status', 'gradeexport_ilp_push', $message->data->status));
            }

            if (!empty($message->message)) {
                $currgrade->add_status_message($message->message);

                if (!$success) {
                    if (isset($results['errormsgs'][$message->message])) {
                        $results['errormsgs'][$message->message]++;
                    } else {
                        $results['errormsgs'][$message->message] = 1;
                    }
                }

                if (stripos($message->message, 'GE09') !== false) {
                    // GE09 indicates a rolled grade.
                    $currgrade->status = saved_grade::GRADING_STATUS_LOCKED;
                }
            }

            if ($success) {
                $results['successes']++;
            } else {
                $results['errors']++;
            }

            $currgrade->save_to_db();

            // Remove it from our tracking array.
            unset($tracking[$currgrade->id]);
        }

        // Anything left in the tracking array didn't have a response from ILP.
        $missingmsg = get_string('ilp_response_missing', 'gradeexport_ilp_push');
        foreach ($tracking as $missing) {
            log::instance()->log_line('Grade didn\'t receive a response message.', log::ERROR_WARN, $missing);

            if ($connectionerror) {
                $missing->status = saved_grade::GRADING_STATUS_RESUBMIT;
                $results['resubmits']++;
            } else {
                $missing->status = saved_grade::GRADING_STATUS_FAILED;
                $results['errors']++;
                if (isset($results['errormsgs'][$message->message])) {
                    $results['errormsgs'][$missingmsg]++;
                } else {
                    $results['errormsgs'][$missingmsg] = 1;
                }
            }
            $missing->mark_failure($missingmsg);
            $missing->save_to_db();
        }

        // Fire events for the log about processing.
        foreach ($grades as $grade) {
            if (!$grade->get_is_current_failure() && $grade->status == saved_grade::GRADING_STATUS_PROCESSED) {
                $event = event\grade_sent_success::create_from_saved_grade($grade);
            } else {
                $event = event\grade_sent_error::create_from_saved_grade($grade);
            }
            $event->trigger();
        }

        return $results;
    }

    protected function set_grades_status($grades, $status, $failure = false, $message = false) {
        foreach ($grades as $grade) {
            $grade->status = $status;
            if ($failure) {
                $grade->mark_failure();
            }
            if ($message !== false) {
                $grade->add_status_message($message);
            }
            $grade->save_to_db();
        }
    }
}


