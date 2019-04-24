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
 * Main view
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use gradeexport_ilp_push\grade_exporter;
use gradeexport_ilp_push\event;

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/ilp_push/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/ilp_push:view', $context);

$gradingallowed = grade_exporter::check_grading_allowed($COURSE);

if ($gradingallowed === true) {
    $ilp = new grade_exporter($COURSE, 0, null);

    if ($formdata = data_submitted()) {
        $ilp->process_options_form();
        $ilp->process_data($formdata);
        // We redirect to prevent the reprocessing for form data on reload.
        redirect($PAGE->url);
    }
}

print_grade_page_head($COURSE->id, 'export', 'ilp_push', get_string('export_page_header', 'gradeexport_ilp_push'));

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/txt:publish', $context);
}

$renderer = $PAGE->get_renderer('gradeexport_ilp_push', 'export');



if ($gradingallowed === true) {


    echo $renderer->render_exporter($ilp);

    $event = event\grades_viewed::create(['context' => $context]);
    $event->trigger();

} else {
    echo $renderer->render_error($gradingallowed);
}



//$data = $ilp->get_user_data();

//print $renderer->render_user_rows($data);


//print "<pre>";var_export();print "</pre>";


echo $OUTPUT->footer();

