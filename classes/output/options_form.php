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
 * A form for display options.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use moodleform;
use gradeexport_ilp_push\grade_exporter;

/**
 * A form for display options.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $data = $this->_customdata;
        $dirtyclass = ['class' => 'ignoredirty'];

        $options = array(grade_exporter::FILTER_ALL => get_string('filter_all', 'gradeexport_ilp_push'),
                         grade_exporter::FILTER_NEEDS_ATTENTION => get_string('filter_attention', 'gradeexport_ilp_push'),
                         grade_exporter::FILTER_IN_PROGRESS => get_string('filter_in_progress', 'gradeexport_ilp_push'),
                         grade_exporter::FILTER_ERROR => get_string('filter_error', 'gradeexport_ilp_push'),
                         grade_exporter::FILTER_DONE => get_string('filter_done', 'gradeexport_ilp_push'));

        $mform->addElement('select', 'statusfilter', get_string('status_filter', 'gradeexport_ilp_push'), $options, $dirtyclass);

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'optionsform', 1);
        $mform->setType('optionsform', PARAM_INT);
    }
}
