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
 * External ILP Grade Push API
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use gradeexport_ilp_push\grade_exporter;
use gradeexport_ilp_push\banner_grades;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/assign/locallib.php");

/**
 * Assign functions
 * @copyright 2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeexport_ilp_push_external extends external_api {


    public static function update_row_grade_mode_parameters() {
        return new external_function_parameters(
            [
                'rowid' => new external_value(PARAM_TEXT, 'The id of the row being changed'),
                'grademodeid' => new external_value(PARAM_INT, 'The id of the new Grade Mode'),
                'studentid' => new external_value(PARAM_INT, 'The id of the user being changed'),
                'courseid' => new external_value(PARAM_INT, 'The id of the course the grade is in')
            ]
        );
    }

    public static function update_row_grade_mode($rowid, $grademodeid, $studentid, $courseid) {
        global $DB, $PAGE;

        $output = ['rowhtml' => '', 'warnings' => []];

        // TODO - verify user, course, student...

        $context = context_course::instance($courseid);
        self::validate_context($context);

        $course = $DB->get_record('course', ['id' => $courseid]);

        $grademode = banner_grades::get_grade_mode($grademodeid, false);
        if (empty($grademode) || !$grademode->enabled) {
            $message = "Could not get grade mode {$grademodeid}, or it is disabled.";
            $output['warnings'][] = self::generate_row_warning($rowid, 'nogrademode', $message);

            return $output;
        }

        $exporter = new grade_exporter($course, 0, null);
        $graderow = $exporter->get_user_grade_row($studentid);

        $graderow->set_grade_mode($grademode);

        if (empty($graderow)) {
            $message = "Could not get row for user {$studentid} in course {$courseid}.";
            $output['warnings'][] = self::generate_row_warning($rowid, 'nouserrow', $message);

            return $output;
        }

        $renderer = $PAGE->get_renderer('gradeexport_ilp_push', 'export');

        $data = $graderow->export_for_template($renderer);

        $output['rowhtml'] = $renderer->render_from_template('gradeexport_ilp_push/user_row', $data);

        error_log(var_export($output, true));

        return $output;
    }

    public static function update_row_grade_mode_returns() {
        return new external_single_structure(
            [
                'rowhtml' => new external_value(PARAM_RAW, 'Raw HTML to replace the row with'),
                'warnings' => new external_warnings()
            ]
        );
    }

    private static function generate_row_warning($rowid, $warningcode, $detail) {
        $warningmessages = [
            'nouserrow' => 'Could not load user row.',
            'nogrademode' => 'Grade mode missing or disabled'
        ];

        $message = $warningmessages[$warningcode];
        if (empty($message)) {
            $message = 'Unknown warning type.';
        }

        return array('item' => s($detail),
                     'itemid' => $assignmentid,
                     'warningcode' => $warningcode,
                     'message' => $message);
    }

}
