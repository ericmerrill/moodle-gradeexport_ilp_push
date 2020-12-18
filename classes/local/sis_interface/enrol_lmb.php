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
 * An interface for getting information from the SIS using the LMB NXT plugin.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local\sis_interface;

defined('MOODLE_INTERNAL') || die();

use gradeexport_ilp_push\log;

/**
 * An interface for getting information from the SIS using the LMB NXT plugin.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_lmb extends base {

    protected $courseenrolids = [];

    protected $courseidmappings = [];

    protected $userdisplayids = [];

    protected $profileid = null;


    public function get_course_id_for_user($course, $user) {

        if (!isset($this->courseenrolids[$course->id])) {
            $this->load_course_user_mappings($course);
        }

        if (!empty($this->courseenrolids[$course->id][$user->id])) {
            return $this->courseenrolids[$course->id][$user->id];
        }

        if (empty($course->idnumber)) {
            return null;
        }

        return $course->idnumber;
    }

    public function get_user_display_id($user) {
        global $DB;

        if (isset($user->gid)) {
            return $user->gid;
        }

        if (is_null($this->profileid)) {
            $this->profileid = $DB->get_field('user_info_field', 'id', ['shortname' => 'gid']);
        }

        if (!empty($this->profileid)) {
            $field = $DB->get_field('user_info_data', 'data', ['fieldid' => $this->profileid, 'userid' => $user->id]);
            $this->userdisplayids[$user->id] = $field;
        }

        if (!empty($this->userdisplayids[$user->id])) {
            return $this->userdisplayids[$user->id];
        }

        return parent::get_user_display_id($user);
    }

    public function get_section_filters_for_course($course) {
        global $DB;

        if (isset($this->courseidmappings[$course->id])) {
            return $this->courseidmappings[$course->id];
        }

        $sql = "SELECT xlsmem.id, xlsmem.sdid FROM {enrol_lmb_crosslists} xls
                  JOIN {enrol_lmb_crosslist_members} xlsmem
                        ON xls.id = xlsmem.crosslistid
                        AND status = 1
                 WHERE xls.sdid = ?
              ORDER BY xlsmem.sdid ASC";

        $records = $DB->get_records_sql($sql, [$course->idnumber]);

        if (empty($records)) {
            $this->courseidmappings[$course->id] = [];
            return [];
        }

        $output = [];
        foreach ($records as $record) {
            $output[$record->sdid] = $record->sdid;
        }

        $this->courseidmappings[$course->id] = $output;
        return $output;
    }

    public function user_in_filter_section_id($course, $user, $sectionid) {
        if ($this->get_course_id_for_user($course, $user) === $sectionid) {
            return true;
        }

        return false;
    }

    protected function load_course_user_mappings($course) {
        global $DB;

        // This is currently set to do the magic way that LMB NXT works. TODO - generalize.
        $sql = "SELECT ue.id, ue.userid, e.customchar1 FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = :courseid";

        $params = ['courseid' => $course->id];

        $records = $DB->get_recordset_sql($sql, $params);

        $this->courseenrolids[$course->id] = [];
        $map = [];
        foreach ($records as $record) {
            if (isset($map[$record->userid])) {
                $text = "User {$record->userid} has more than one enrolments in {$course->id}.";
                log::instance()->log_line($text, log::ERROR_WARN);
                if (empty($map[$record->userid])) {

                    $map[$record->userid] = $record->customchar1;
                }
            } else {
                $map[$record->userid] = $record->customchar1;
            }
        }
        $this->courseenrolids[$course->id] = $map;
        $records->close();
    }

    /**
     * Check if a particular user is allowed to grade a given course.
     *
     * @param
     * @return true|string True if allowed, or an error string if not.
     */
    public function teacher_allowed_to_grade_course($user, $course) {
        // TODO - check LMB enrolled.
        return parent::teacher_allowed_to_grade_course($user, $course);
    }

}


