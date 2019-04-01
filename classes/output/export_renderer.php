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
        $data = $exporter->export_for_template($this);

        $output = $this->render_from_template('gradeexport_ilp_push/exporter', $data);

        return $output;
    }

    public function render_select_menu(user_grade_row $userrow) {
        $options = banner_grades::get_possible_grades($userrow);
        $selected = $userrow->get_current_menu_selection();

        $output = html_writer::select($options, $userrow->get_form_id('bannergrade'), $selected);

        return $output;
    }

    public function render_incomplete_select_menu(user_grade_row $userrow) {
        // TODO - need to make it so if a different one is already selected, that is returned.
        $options = banner_grades::get_possible_grades();
        $selected = banner_grades::get_default_incomplete_grade();

        $output = html_writer::select($options, $userrow->get_form_id('incompletegrade'), $selected);

        return $output;
    }


// ***************** Old stuff to delete.

    /**
     * Render the page header for the transcript page.
     */
    public function render_transcript_header() {
        $out = html_writer::tag('h2', get_string('mytscriptheader', 'local_vectscript'));
        $out = html_writer::div($out, 'transcript-header');

        return $out;
    }

    /**
     * Render the entire transcript page.
     *
     * @param transcript $transcript
     */
    public function render_transcript_page($transcript) {
        global $PAGE;

        $PAGE->requires->js_call_amd('local_vectscript/filter_tag', 'init');
        $PAGE->requires->js_call_amd('local_vectscript/tscript_form', 'init');
        $PAGE->requires->js_call_amd('local_vectscript/history_load', 'init');

        $output = $this->render_transcript_header();

        $output .= $this->render_filters($transcript);
        $output .= $this->render_tab_bar($transcript->get_view_type());

        $data = ['content' => $this->render_transcript($transcript)];
        $output .= $this->render_from_template('local_vectscript/transcript_body', $data);

        $output .= $this->render_share_button();

        $output = html_writer::tag('form', $output, ['id' => 'tscript-form']);

        return html_writer::span($output, 'user-transcript');
    }

    public function render_shared_transcript_page($transcript) {
        $output = $this->render_transcript_header();

        $data = ['content' => $this->render_transcript($transcript)];
        $output .= $this->render_from_template('local_vectscript/transcript_body', $data);

        return html_writer::span($output, 'user-transcript');
    }

    /**
     * Render a filter for display.
     *
     * @param filter\base $filter A filter object to render.
     */
    public function render_filter(filter\base $filter) {
        $data = $filter->export_for_template($this);
        return $this->render_from_template($filter->get_template_name(), $data);
    }

    /**
     * Render the filters into a complete bar.
     *
     * @param transcript $transcript
     */
    public function render_filters(transcript $transcript) {
        // Make sure to include the view type we are on.
        $attributes = ['class' => 'vect-data', 'type'=>'hidden', 'name' => 'view', 'value' => $transcript->get_view_type()];
        $output = html_writer::empty_tag('input', $attributes);

        $filters = $transcript->get_filters();
        foreach ($filters as $filter) {
            $filterhtml = $this->render_filter($filter);
            $output .= html_writer::tag('div', $filterhtml, ['class' => 'filter-cell']);
        }

        $output = html_writer::tag('div', $output, ['class' => 'filter-row']);
        return $output;

    }

    public function render_transcript(transcript $transcript) {
        $data = $transcript->export_for_template($this);
        return $this->render_from_template($transcript->get_template_name(), $data);
    }

    public function render_course_status($status) {
        switch ($status) {
            case courses::COMPLETION_COMPLETE:
                $text = get_string('status_complete', 'local_vectscript');
                $class = 'complete';
                break;
            case courses::COMPLETION_IN_PROGRESS:
                $text = get_string('status_inprogress', 'local_vectscript');
                $class = 'inprogress';
                break;
            case courses::COMPLETION_NOT_STARTED:
                $text = get_string('status_notstarted', 'local_vectscript');
                $class = 'notstarted';
                break;
            case courses::COMPLETION_NONE:
            default:
                $text = get_string('status_none', 'local_vectscript');
                $class = 'none';
                break;
        }

        return $this->render_pill($text, $class);
    }

    protected function render_pill($text, $class = '') {
        return html_writer::div($text, 'pill '.$class);
    }

    public function render_tag_string($tagstring) {
        $strmanager = get_string_manager();

        $strlabel = 'tag_'.strtolower($tagstring);

        if ($strmanager->string_exists($strlabel, 'local_vectscript')) {
            return $strmanager->get_string($strlabel, 'local_vectscript');
        } else {
            return $tagstring;
        }
    }

    /**
     * Render a table column header with sort link and arrows.
     *
     * @param string $name The string name that goes with the header. The local_vectscript string "header_$name" will be found.
     * @param bool   $sortable If true, then the link will be clickable to sort.
     * @param bool   $iscurrentsort If true, then this column will get a sort arrow.
     * @param int    $sortorder Determine arrow direction and link param. Uses transcript::SORT_ASC and ::SORT_DESC.
     * @return string
     */
    public function render_header($name, $sortable = true, $iscurrentsort = false, $sortorder = null) {
        global $PAGE;

        $string = get_string('header_'.$name, 'local_vectscript');

        if (!$sortable && $iscurrentsort === false) {
            return $string;
        }

        $params = ['sort' => $name,
                   'sortorder' => (($sortorder == transcript::SORT_ASC) ? transcript::SORT_DESC : transcript::SORT_ASC)];
        $linkparams = ['class' => 'sort-link',
                       'data-local_vectscript-sort' => $params['sort'],
                       'data-local_vectscript-sortorder' => $params['sortorder']];
        if ($sortable) {
            $link = html_writer::link(new \moodle_url('/local/vectscript/view.php', $params), $string, $linkparams);
        } else {
            $link = $string;
        }

        if ($iscurrentsort) {
            if ($sortorder == transcript::SORT_ASC) {
                $link .= $this->pix_icon('t/sort_asc', '', '', array('class' => 'iconsmall sorticon'));
            } else if ($sortorder == transcript::SORT_DESC) {
                $link .= $this->pix_icon('t/sort_desc', '', '', array('class' => 'iconsmall sorticon'));
            }
        }

        return $link;
    }

    public function render_tab_bar($view) {
        $data = new \stdClass();

        $options = [transcript::VIEW_COURSES => get_string('tab_courses', 'local_vectscript'),
                    transcript::VIEW_CERTIFICATES => get_string('tab_certs', 'local_vectscript')];
                    //transcript::VIEW_CEUS => get_string('tab_ceus', 'local_vectscript')

        $tabs = [];
        foreach ($options as $key => $string) {
            $tab = new \stdClass();
            $tab->name = $string;
            if ($key == $view) {
                $tab->selected = true;
            } else {
                $tab->selected = false;
                $tab->href = '?view='.$key;
            }

            $tabs[] = $tab;
        }

        $data->tabs = $tabs;

        return $this->render_from_template('local_vectscript/tab_bar', $data);
    }

    public function render_share_button() {
        return $this->render_from_template('local_vectscript/share_button', []);
    }

    public function render_custom_checkbox($name, $value, $label = '', $classes = '', $checked = true) {
        $attr = ['type' => 'checkbox',
                 'name' => $name,
                 'class' => $classes.' customizecheckbox',
                 'value' => $value];

        if ($checked) {
            $attr['checked'] = true;
        }

        $checkbox = html_writer::empty_tag('input', $attr);
        $checkmark = html_writer::span('', 'checkmark');
        $output = html_writer::tag('label', $label.$checkbox.$checkmark, ['class' => 'customcheckbox customize hidden']);

        return $output;
    }
}
