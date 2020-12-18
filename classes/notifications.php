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
 * Object to handle notifications to users.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

use \core_user;

/**
 * Object to handle notifications to users.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2020 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {

    /**
     * Sends a success notification to the specified user.
     *
     * $data must have:
     *  - 'count' - The count of successes
     *  - 'crn' - The CRN of the course
     *  - 'url' - A moodle_url of the grade export page for the course.
     *
     * @param array $data An array, as described above.
     * @param int $courseid The course id of the course we are in.
     * @param object $userto Object of the user the message is going to.
     */
    public static function send_success($data, $courseid, $userto) {
        $eventdata = new \core\message\message();
        $eventdata->courseid          = $courseid;
        $eventdata->component         = 'gradeexport_ilp_push';
        $eventdata->name              = 'publish_success';
        $eventdata->notification      = 1;

        $eventdata->userfrom          = core_user::get_noreply_user();
        $eventdata->userto            = $userto;

        $eventdata->subject           = get_string('message_success_subject', 'gradeexport_ilp_push', $data);
        $eventdata->fullmessage       = get_string('message_success_body', 'gradeexport_ilp_push', $data);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = get_string('message_success_short', 'gradeexport_ilp_push', $data);

        $eventdata->contexturl        = $data->url;
        $eventdata->contexturlname    = get_string('message_url_text', 'gradeexport_ilp_push', $data);

        message_send($eventdata);
    }

        /**
     * Sends a success notification to the specified user.
     *
     * $data must have:
     *  - 'errorcount' - The count of error
     *  - 'successcount' - The count of successes
     *  - 'crn' - The CRN of the course
     *  - 'url' - A moodle_url of the grade export page for the course.
     *
     * @param array $data An array, as described above.
     * @param int $courseid The course id of the course we are in.
     * @param object $userto Object of the user the message is going to.
     */
    public static function send_error($data, $courseid, $userto) {
        $data->urlstr = $data->url->out(true);

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $courseid;
        $eventdata->component         = 'gradeexport_ilp_push';
        $eventdata->name              = 'publish_error';
        $eventdata->notification      = 1;

        $eventdata->userfrom          = core_user::get_noreply_user();
        $eventdata->userto            = $userto;

        $eventdata->subject           = get_string('message_error_subject', 'gradeexport_ilp_push', $data);
        $eventdata->fullmessage       = get_string('message_error_body', 'gradeexport_ilp_push', $data);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = get_string('message_error_short', 'gradeexport_ilp_push', $data);

        $eventdata->contexturl        = $data->url;
        $eventdata->contexturlname    = get_string('message_url_text', 'gradeexport_ilp_push', $data);

        message_send($eventdata);
    }
}


