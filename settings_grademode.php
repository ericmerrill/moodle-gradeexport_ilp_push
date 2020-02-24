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
 * Version details
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \gradeexport_ilp_push\banner_grades;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('gradeexport_ilp_push_grademode');

$pageurl = new moodle_url('/grade/export/ilp_push/settings_grademode.php');

$action = optional_param('action', false, PARAM_ALPHA);
$grademodeid = optional_param('id', false, PARAM_INT);

$returnurl = new moodle_url('/admin/settings.php', ['section' => 'gradeexportilp_push']);
if ($action) {
    if (!confirm_sesskey()) {
        redirect($returnurl);
    }

    $grademode = banner_grades::get_grade_mode($grademodeid);
    if (empty($grademode) || $grademode->id != $grademodeid) {
        throw new moodle_exception('grademode_not_found');
    }

    switch ($action) {
        case 'up':
            // TODO.
            break;
        case 'down':
            // TODO.
            break;
        case 'enable':
            $grademode->enabled = 1;
            $grademode->save_to_db();
            break;
        case 'disable':
            $grademode->enabled = 0;
            $grademode->save_to_db();
            break;
    }

    redirect($returnurl);
}

echo $OUTPUT->header();


echo $OUTPUT->footer();
