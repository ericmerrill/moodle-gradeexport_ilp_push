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
 * A admin setting for listing grade modes.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

//use moodleform;
//use gradeexport_ilp_push\grade_exporter;
use gradeexport_ilp_push\banner_grades;
use \html_table;
use \html_table_row;
use \html_writer;
use \moodle_url;
use \admin_setting;

/**
 * A admin setting for listing grade modes.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_grademodes extends admin_setting {
    /**
     * Calls parent::__construct with specific arguments
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('gradeexport_ilp_push_grademodes', 'TODO Grade Modes', '', '');
    }

    /**
     * Always returns true, does nothing
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true, does nothing
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Always returns '', does not write anything
     *
     * @return string Always returns ''
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Checks if $query is one of the available editors
     *
     * @param string $query The string to search for
     * @return bool Returns true if found, false if not
     */
    public function is_related($query) {
        // TODO - Should check list of grade modes.

        return false;
    }

    /**
     * Builds the XHTML to display the control
     *
     * @param string $data Unused
     * @param string $query
     * @return string
     */
    public function output_html($data, $query='') {
        global $CFG, $OUTPUT;

        $txt = get_strings(array('administration', 'edit', 'name', 'enable', 'disable',
            'up', 'down', 'none'));

        $txt->updown = "$txt->up/$txt->down";

        $grademodes = banner_grades::get_all_grade_modes();

        $return = $OUTPUT->heading('TOTO Grade Modes', 3, 'main', true);
        $return .= $OUTPUT->box_start('generalbox gradeexport_ilp_push_grademodes');

        $table = new html_table();
        $table->head  = array($txt->name, $txt->enable, $txt->updown, $txt->edit);
        $table->colclasses = array('leftalign', 'centeralign', 'centeralign', 'centeralign');
        $table->id = 'gradeexport_ilp_push_grademodes_table';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = array();

        $url = new moodle_url('/grade/export/ilp_push/settings_grademode.php', ['sesskey' => sesskey()]);

        $active = [];
        $inactive = [];
        foreach ($grademodes as $grademode) {
            if ($grademode->enabled) {
                $active[] = $grademode;
            } else {
                $inactive[] = $grademode;
            }
        }

        $grademodes = array_merge($active, $inactive);

        $arrowcount = 1;
        foreach ($grademodes as $grademode) {
            $grademodeid = $grademode->id;
            $enabled = (bool)$grademode->enabled;

            $rowitems = [];

            $rowitems[] = $grademode->name;

            // Enable/Disable setting.
            if ($enabled) {
                $hideshow = "<a href=\"$url&amp;action=disable&amp;id=$grademodeid\">";
                $hideshow .= $OUTPUT->pix_icon('t/hide', get_string('disable')) . '</a>';
                $enabled = true;
                $class = '';
            } else {
                $hideshow = "<a href=\"$url&amp;action=enable&amp;id=$grademodeid\">";
                $hideshow .= $OUTPUT->pix_icon('t/show', get_string('enable')) . '</a>';
                $enabled = false;
                $class = 'dimmed_text';
            }
            $rowitems[] = $hideshow;

            // Up/down arrows.
            $updown = '';
            if ($enabled && count($active) > 1) {
                if ($arrowcount > 1) {
                    $updown .= "<a href=\"$url&amp;action=up&amp;id=$grademodeid\">";
                    $updown .= $OUTPUT->pix_icon('t/up', get_string('moveup')) . '</a>&nbsp;';
                } else {
                    $updown .= $OUTPUT->spacer() . '&nbsp;';
                }
                if ($arrowcount < count($active)) {
                    $updown .= "<a href=\"$url&amp;action=down&amp;id=$grademodeid\">";
                    $updown .= $OUTPUT->pix_icon('t/down', get_string('movedown')) . '</a>&nbsp;';
                } else {
                    $updown .= $OUTPUT->spacer() . '&nbsp;';
                }
                ++ $arrowcount;

            }
            $rowitems[] = $updown;

            // Edit URL.
            $editurl = new moodle_url('/grade/export/ilp_push/settings_grademode.php', array('id' => $grademodeid));
            $edithtml = "<a href='$editurl'>{$txt->edit}</a>";
            $rowitems[] = $edithtml;

            // Create the row and add it to the table.
            $row = new html_table_row($rowitems);
            if ($class) {
                $row->attributes['class'] = $class;
            }
            $table->data[] = $row;
        }

        $return .= html_writer::table($table);
        $return .= "TODO Add new";

        $return .= $OUTPUT->box_end();

        return highlight($query, $return);
    }
}
