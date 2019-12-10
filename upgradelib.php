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
 * An upgrade lib.
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A function that inserts the default grade mode during an upgrade.
 *
 * This intentionally doesn't use any of the standard API/classes because they may not
 * match the DB at this point in the upgrade.
 */
function gradeexport_ilp_push_upgrade_create_default_grade_mode() {
    global $DB;

    if ($DB->get_records('gradeexport_ilp_push_grmodes')) {
        // There is already at least one grade mode, so we are going to skip this...
        return;
    }

    $mode = new stdClass();
    $mode->name = 'Standard Letter';
    $mode->enabled = 1;
    $mode->sortorder = 1;
    $mode->additional = '{}';
    $mode->timecreated = time();
    $mode->timemodified = time();
    $mode->usermodified = 0;

    $mode->id = $DB->insert_record('gradeexport_ilp_push_grmodes', $mode);

    // An array of all the options to insert.
    $options = [
        'A' => [],
        'A-' => [],
        'B+' => [],
        'B' => [],
        'B-' => [],
        'C+' => [],
        'C' => [],
        'C-' => [],
        'D+' => [],
        'D' => [],
        'F' => ['requirelastdate' => 1],
        'I' => ['isincomplete' => 1]
    ];

    $opt = new stdClass();
    $opt->modeid = $mode->id;
    $opt->enabled = 1;
    $opt->version = 0;
    $opt->mostcurrent = 1;
    $opt->usermodified = 0;

    $sort = 1;
    $optids = [];
    foreach ($options as $letter => $additional) {
        $opt->displayname = $letter;
        $opt->bannervalue = $letter;
        $opt->sortorder = $sort;
        $opt->additional = json_encode($additional, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
        $opt->timecreated = time();
        $opt->timemodified = time();

        $optids[$letter] = $DB->insert_record('gradeexport_ilp_push_modeopt', $opt);

        $sort++;
    }

    // Now we need to upgrade any existing saved grades.
    $rs = $DB->get_recordset('gradeexport_ilp_push_grades');

    $newrec = new stdClass();
    foreach ($rs as $record) {
    echo "$record->id";
        $newrec->id = $record->id;
        if (isset($optids[$record->grade])) {
            $newrec->gradeoptid = $optids[$record->grade];
        } else {
            $newrec->gradeoptid = null;
        }

        $data = [];
        if (!empty($record->additional)) {
            $data = json_decode($record->additional);
            $data->gradeoptidupgrade =  time();
        }
        $newrec->additional = json_encode($data, JSON_UNESCAPED_UNICODE);

        $DB->update_record('gradeexport_ilp_push_grades', $newrec);
    }

    $rs->close();
}
