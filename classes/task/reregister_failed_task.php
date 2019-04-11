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
 * A task that looks for grades that are in the Resubmit state, resets them, and registers an ad_hoc task.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\task;

defined('MOODLE_INTERNAL') || die();

use core\task;
use gradeexport_ilp_push\log;
use gradeexport_ilp_push\saved_grade;


/**
 * A task that looks for grades that are in the Resubmit state, resets them, and registers an ad_hoc task.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reregister_failed_task extends task\scheduled_task {
    /**
     * Look for grades that need reprocessing.
     */
    public function execute() {
        global $DB;

        log::instance()->start_message("Executing cron task looking for resubmissions.");

        $select = "status = :status
                    AND timemodified < :modtime";

        $sql = "SELECT courseid, submitterid, COUNT(*) AS cnt FROM {".saved_grade::TABLE."}
                 WHERE {$select}
              GROUP BY courseid, submitterid";

        $params = ['status' => saved_grade::GRADING_STATUS_RESUBMIT,
                   'modtime' => (time() - saved_grade::RESUBMIT_TIME)];
        $records = $DB->get_recordset_sql($sql, $params);

        foreach($records as $record) {
            $text = "Resubmitting {$record->cnt} grades for course {$record->courseid} and user {$record->submitterid}.";
            log::instance()->log_line($text);

            // We are now going to mark them back to submitted.
            $params['submitterid'] = $record->submitterid;
            $params['courseid'] = $record->courseid;

            $DB->set_field_select(saved_grade::TABLE, 'status', saved_grade::GRADING_STATUS_SUBMITTED, $select, $params);

            // And tell the adhoc task to reregister itself.
            process_user_course_task::register_task_for_user_course($record->submitterid, $record->courseid);
        }

        $records->close();

        log::instance()->end_message();
    }

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('reregister_failed_task', 'gradeexport_ilp_push');
    }
}


