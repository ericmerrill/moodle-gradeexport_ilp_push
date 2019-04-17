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
 * Strings for the plugin, in English.
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['eventgradeexported'] = 'Banner grade exported';
$string['pluginname'] = 'Banner Grades';
$string['privacy:metadata'] = 'The Banner grade export TODO Privacy.';
//$string['timeexported'] = 'Last downloaded from this course';
$string['ilp_push:publish'] = 'Publish Banner grade export';
$string['ilp_push:view'] = 'Use Banner grade export';

$string['ilp_response_missing'] = 'No response message received from banner.';
$string['reregister_failed_task'] = 'Resubmit temp failed submissions.';
$string['ilp_connection_error'] = 'There was an error connecting to ILP.';
$string['ilp_no_response'] = 'No responses received from ILP.';
$string['ilp_unknown_status'] = 'ILP responded with an unknown status of "{$a}"';

$string['status_editing'] = 'Editing';
$string['status_processing'] = 'Processing';
$string['status_success'] = 'Succeeded';
$string['status_failed'] = 'Failed';
$string['status_locked'] = 'Locked';

// Rule validation.
$string['invalid_datelastattended_early'] = 'Date last attended cannot be before the start of the course.';
$string['invalid_datelastattended_late'] = 'Date last attended cannot be after the end of the course.';
$string['invalid_datelastattended_missing'] = 'Date last attended must be entered for a failing student.';
$string['invalid_datelastattended_today'] = 'Date last attended cannot be later than today.';

$string['invalid_grade'] = 'A valid grade must be selected.';

$string['invalid_incomplete_date_early'] = 'Incomplete deadline cannot be before the end of the course.';
$string['invalid_incomplete_date_late'] = 'Incomplete deadline cannot be more than a year after the course ends.';
$string['invalid_incomplete_date_missing'] = 'Incomplete deadline must be entered for an incomplete grade.';
$string['invalid_incomplete_grade_missing'] = 'A default incomplete grade must be selected.';
$string['invalid_incomplete_grade_wrong'] = 'Default incomplete grade cannot be changed from the default.';

$string['invalid_incomplete_grade'] = 'Default incomplete grade must be set to "{$a}".';
$string['invalid_incomplete_date'] = 'Incomplete deadline must be between "{$a->start}" and "{$a->end}".';
$string['invalid_datelastattended'] = 'Date last attended must be between "{$a->start}" and "{$a->end}".';

$string['grader_no_id'] = 'The current user has no SIS ID, and so cannot export grades to Banner.';
$string['course_no_id'] = 'The current course has no SIS ID, and so cannot be exported to Banner.';


// Settings.
$string['ilpid'] = 'ILP Connection ID';
$string['ilpid_help'] = 'The connection ID setup in the ILP admin.';
$string['ilppassword'] = 'ILP Connection Password';
$string['ilppassword_help'] = 'The connection password setup in the ILP admin.';
$string['ilpurl'] = 'ILP URL';
$string['ilpurl_help'] = 'The base URL of the ILP server being used';

$string['logginglevel'] = 'Error logging';
$string['logginglevel_help'] = 'Log messages at this level or higher will be logged. TODO.';
$string['error_all'] = 'All';
$string['error_notice'] = 'Notices';
$string['error_warn'] = 'Warnings';
$string['error_major'] = 'Major Errors';
$string['logpath'] = 'Log file location';
$string['logpath_help'] = 'This is the location you would like the log file to be saved to. This should be an absolute path on the server. The file specified should already exist, and needs to be writable by the webserver process.';

// Exceptions.
$string['exception_submitter_mismatch'] = 'Not all saved grades have the same submitter ILP id.';
$string['exception_connector_failure'] = 'A connector failure has occurred.';
$string['exception_unknown_endpoint'] = 'Endpoint {$a} is unknown.';
$string['exception_curl_failure'] = 'Curl execution failed. Error: {$a}.';
$string['exception_bad_response'] = 'Bad response from ILP. {$a}';
$string['exception_course_mismatch'] = 'Course submitted does match page course.';
$string['exception_user_mismatch'] = 'Submitting user does not match data in form.';

// Events.
$string['eventgradeexported'] = 'Grade exported to Banner';
$string['event_grade_modified'] = 'Banner export modified by teacher';
$string['event_grades_viewed'] = 'Banner export grades viewed';
$string['event_grade_sent_error'] = 'Grade failure when sent to banner';
$string['event_grade_sent_success'] = 'Grade success when sent to banner';
