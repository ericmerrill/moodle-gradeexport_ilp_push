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
 * A helper that pretendeds to be ILP for testing purposes.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once '../../../../../config.php';

if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
} else {
    $username = false;
    $password = false;
}

error_log(var_export($username, true).var_export($password, true));

if ($username !== 'foo' || $password !== 'bar') {
    header('WWW-Authenticate: Basic realm="ILP"');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

$input = file_get_contents('php://input');

if (empty($input)) {
    echo "No input received";
    exit;
}

error_log(var_export($input, true));

$data = json_decode($input);

if (empty($data)) {
    echo "Could not decode input";
    exit;
}

error_log(var_export($data, true));

if (empty($data->ModifiedBy)) {
    echo "Missing ModifiedBy";
    exit;
}

if (empty($data->StudentGrades)) {
    echo "Missing StudentGrades";
    exit;
}

$messages = [];
$errors = [];
$failure = false;

foreach ($data->StudentGrades as $grade) {
    $msgs = process_grade($grade);

    $messages = array_merge($messages, $msgs);

    foreach ($msgs as $msg) {
        if ($msg->data->status === 'failure') {
            $failure = true;
        }
    }
}

$output = new stdClass();

$output->status = $failure ? 'failure' : 'success';
$output->messages = $messages;
$output->isConnectivityFailure = false;

error_log(var_export($output, true));

echo json_encode($output);


function process_grade($grade) {
    $messages = [];

    $bangrade = false;
    $isfinal = false;

    if (isset($grade->MidtermGrade1)) {
        $bangrade = $grade->MidtermGrade1;
    } else if (isset($grade->MidtermGrade2)) {
        $bangrade = $grade->MidtermGrade2;
    } else if (isset($grade->MidtermGrade3)) {
        $bangrade = $grade->MidtermGrade3;
    } else if (isset($grade->MidtermGrade4)) {
        $bangrade = $grade->MidtermGrade4;
    } else if (isset($grade->MidtermGrade5)) {
        $bangrade = $grade->MidtermGrade5;
    } else if (isset($grade->FinalGrade)) {
        $bangrade = $grade->FinalGrade;
        $isfinal = true;
    }

    if ($bangrade === false) {
        $messages[] = create_messages('Missing grade', false, 'FinalGrade', $grade);

        return $messages;
    }

    if ($isfinal) {
        if ($bangrade == 'F' || $bangrade == 'Fail') {
            if (isset($grade->LastAttendanceDate)) {
                $timestamp = strtotime($grade->LastAttendanceDate);
                if ($timestamp > time()) {
                    $text = 'Last attendance date cannot be greater than the current date.';
                    $messages[] = create_messages($text, false, 'LastAttendanceDate', $grade);

                    // ILP throws errors for both date out of range and date missing when out of range.
                    unset($grade->LastAttendanceDate);
                } else if ($timestamp < (time() - (3600 * 240)) || $timestamp > (time() + (3600 * 240))) {
                    $text = 'Last attendance date must be between section start date and section end date.';
                    $messages[] = create_messages($text, false, 'LastAttendanceDate', $grade);

                    // ILP throws errors for both date out of range and date missing when out of range.
                    unset($grade->LastAttendanceDate);
                }
            }

            if (!isset($grade->LastAttendanceDate)) {
                $text = 'A last attend date is required for this grade.';
                $messages[] = create_messages($text, false, 'FinalGrade', $grade);

                return $messages;
            }
        }

        if ($bangrade == 'I') {
            if (!isset($grade->DefaultIncompleteGrade)) {
                $text = 'Incomplete Final Grade is required.';
                $messages[] = create_messages($text, false, 'FinalGrade', $grade);

                return $messages;
            } else if ($grade->DefaultIncompleteGrade !== 'F') {
                $text = 'Cannot override default incomplete final grade.';
                $messages[] = create_messages($text, false, 'FinalGrade', $grade);

                return $messages;
            }

            if (isset($grade->FinalGradeExpirationDate)) {
                $timestamp = strtotime($grade->LastAttendanceDate);
                if ($timestamp > (time() + (3600 * 24 * 365))) {
                    $text = 'Cannot lengthen default extension date for incomplete grades.';
                    $messages[] = create_messages($text, false, 'FinalGrade', $grade);

                    return $messages;
                }
            }
        }
    }

    $text = 'Successfully processed grades for Student ID '.$grade->StudentId.' (GE00)';
    $messages[] = create_messages($text, true, null, $grade);

    return $messages;
}

function create_messages($messagetxt, $success, $property, $grade) {
    $data = new stdClass();
    $data->targetSis = 'BANNER';
    $data->studentId = $grade->StudentId;
    $data->status = $success ? 'success' : 'failure';
    $data->property = $property;
    $message = new stdClass();
    $message->message = $messagetxt;
    $message->data = $data;

    return $message;
}



