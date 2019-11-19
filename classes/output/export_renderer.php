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
 * A renderer for the export.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\output;

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/grade/export/lib.php');

use plugin_renderer_base;
use html_writer;
use gradeexport_ilp_push\user_grade_row;
use gradeexport_ilp_push\banner_grades;
use gradeexport_ilp_push\grade_exporter;
use gradeexport_ilp_push\saved_grade;
use stdClass;
use moodle_url;

/**
 * A renderer for the export.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_renderer extends plugin_renderer_base {

    public function render_exporter(grade_exporter $exporter) {
        global $PAGE;

        $dateformat = get_string('strftimedatefullshort', 'langconfig');

        // Need to get dates in a couple formats for JS to use.
        $deadlinedates = banner_grades::get_allowed_last_incomplete_deadline_dates($exporter->get_course(), '%F');
        $userdates = banner_grades::get_allowed_last_incomplete_deadline_dates($exporter->get_course(), $dateformat);

        $deadlinedates->userstart = $userdates->start;
        $deadlinedates->userend = $userdates->end;

        $lastattenddates = banner_grades::get_allowed_last_attend_dates($exporter->get_course(), '%F');
        $userdates = banner_grades::get_allowed_last_attend_dates($exporter->get_course(), $dateformat);

        $lastattenddates->userstart = $userdates->start;
        $lastattenddates->userend = $userdates->end;

        // Sending various data to the page for it to use later.
        $params = [banner_grades::get_failing_grade_ids(),
                   banner_grades::get_incomplete_grade_ids(),
                   banner_grades::get_default_incomplete_grade(),
                   $deadlinedates,
                   $lastattenddates];
        $PAGE->requires->js_call_amd('gradeexport_ilp_push/page_info', 'init', $params);
        $PAGE->requires->js_call_amd('gradeexport_ilp_push/row_control', 'initAll');

        $data = $exporter->export_for_template($this);

        $output = $this->render_options_form($exporter->get_options_form());

        $output .= $this->render_from_template('gradeexport_ilp_push/exporter', $data);

        return $output;
    }

    public function render_select_menu(user_grade_row $userrow) {
        $options = banner_grades::get_possible_grades($userrow);
        $selected = $userrow->get_current_grade_key();

        $attributes = ['class' => 'gradeselect'];
        if ($userrow->should_prevent_editing()) {
            $attributes['disabled'] = true;
        }
        $output = html_writer::select($options, $userrow->get_form_id('grade'), $selected, ['' => 'choosedots'], $attributes);
        // Add an input that indicates what was already selected.
        $attributes = ['type' => 'hidden', 'name' => $userrow->get_form_id('grade-starting'), 'value' => $selected];

        $output .= html_writer::empty_tag('input', $attributes);

        return $output;
    }

    public function render_incomplete_select_menu(user_grade_row $userrow) {
        // TODO - need to make it so if a different one is already selected, that is returned.
        $options = banner_grades::get_possible_grades($userrow);
        $selected = $userrow->get_current_incomplete_grade_key();

        $attributes = ['class' => 'incompletegradeselect'];
        if ($userrow->should_prevent_editing()) {
            $attributes['disabled'] = true;
        }
        $output = html_writer::select($options, $userrow->get_form_id('incompletegrade'), $selected, ['' => 'choosedots'], $attributes);
        $attributes = ['type' => 'hidden', 'name' => $userrow->get_form_id('incompletegrade-starting'), 'value' => $selected];

        $output .= html_writer::empty_tag('input', $attributes);

        return $output;
    }

    public function render_form_error($errorstring) {

        return $this->pix_icon('i/warning', $errorstring);

    }

    public function render_error($string) {
        $message = $this->error_text($string);

        return html_writer::div($message, 'alert alert-warning');
    }

    public function render_status(user_grade_row $userrow) {
        $class = 'statuscontainer';
        if ($statclass = $this->get_status_class($userrow)) {
            $class .= " {$statclass}";
        }

        $contents = $this->render_status_contents($userrow);

        $output = html_writer::div($contents, $class);

        return $output;
    }

    public function render_status_messages(user_grade_row $userrow) {
        if (!$message = $userrow->get_status_messages()) {
            return false;
        }

        $class = 'statusmessagebox';
        if ($statclass = $this->get_status_class($userrow)) {
            $class .= " {$statclass}";
        }

        $output = html_writer::div($message, $class);

        return $output;
    }

    public function render_grade_link($fullname, $userid, $courseid) {
        global $OUTPUT, $CFG;

        $a = new stdClass();
        $a->user = $fullname;
        $strgradesforuser = get_string('gradesforuser', 'grades', $a);
        $url = new moodle_url('/grade/report/'.$CFG->grade_profilereport.'/index.php', ['userid' => $userid, 'id' => $courseid]);
        $text = $OUTPUT->action_icon($url, new \pix_icon('t/grades', $strgradesforuser), null, ['target' => '_blank']);

        return $text;
    }

    protected function render_status_contents(user_grade_row $userrow) {
        $class = '';
        $message = '';

        switch ($userrow->get_current_status()) {
            case (saved_grade::GRADING_STATUS_EDITING):
                return false;
                break;
            case (saved_grade::GRADING_STATUS_PROCESSING):
            case (saved_grade::GRADING_STATUS_SUBMITTED):
            case (saved_grade::GRADING_STATUS_RESUBMIT):
                $class = 'fa-refresh';
                $message = get_string('status_processing', 'gradeexport_ilp_push');
                break;
            case (saved_grade::GRADING_STATUS_PROCESSED):
                $class = 'fa-check';
                $message = get_string('status_success', 'gradeexport_ilp_push');
                break;
            case (saved_grade::GRADING_STATUS_FAILED):
                $class = 'fa-times-circle';
                $message = get_string('status_failed', 'gradeexport_ilp_push');
                break;
            case (saved_grade::GRADING_STATUS_LOCKED):
                $class = 'fa-lock';
                $message = get_string('status_locked', 'gradeexport_ilp_push');
                break;
            default:
                return false;
        }

        // TODO - should use a proper renderer for all this...
        $attr = ['class' => "fa {$class} fa-fw statusicon",
                 'title' => $message,
                 'aria-label' => $message];

        $output = html_writer::tag('i', ' ', $attr);

        $output .= html_writer::div($message, 'statustext');

        return $output;
    }

    protected function get_status_class(user_grade_row $userrow) {
        switch ($userrow->get_current_status()) {
            case (saved_grade::GRADING_STATUS_EDITING):
                return '';
                break;
            case (saved_grade::GRADING_STATUS_PROCESSING):
            case (saved_grade::GRADING_STATUS_SUBMITTED):
            case (saved_grade::GRADING_STATUS_RESUBMIT):
                return 'alert-info';
                break;
            case (saved_grade::GRADING_STATUS_PROCESSED):
                return 'alert-success';
                break;
            case (saved_grade::GRADING_STATUS_FAILED):
                return 'alert-danger';
                break;
            case (saved_grade::GRADING_STATUS_LOCKED):
                return 'alert-info';
                break;
            default:
                return false;
        }
    }

    public function render_options_form($form) {
        global $PAGE;

        $PAGE->requires->js_call_amd('gradeexport_ilp_push/options_form', 'init');

        return $form->render();

    }


}
