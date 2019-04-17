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
 * Event for XYZ.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\event;

defined('MOODLE_INTERNAL') || die();

use gradeexport_ilp_push\saved_grade;

/**
 * Event for XYZ.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_sent_success extends \core\event\base {

    /** @var user_grade $grade */
    protected $grade;

    protected function init() {
        $this->data['objecttable'] = saved_grade::TABLE;
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function create_from_saved_grade(saved_grade $grade) {
        if (empty($grade->statusmessages)) {
            $message = false;
        } else {
            $message = $grade->statusmessages;
        }

        $event = self::create([
            'objectid'      => $grade->id,
            'context'       => \context_course::instance($grade->courseid),
            'relateduserid' => $grade->studentid,
            'other'         => [
                'courseilpid'   => $grade->courseilpid,
                'grade'         => $grade->grade,
                'message'       => $message],
        ]);

        $event->grade = $grade;
        return $event;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = '/grade/export/ilp_push/export.php';
        return new \moodle_url($url, array('id' => $this->courseid));
    }

    public static function get_name() {
        return get_string('event_grade_sent_success', 'gradeexport_ilp_push');
    }

    public function get_description() {
        $courseilpid = $this->other['courseilpid'];

        if (!empty($this->other['message'])) {
            $message = " Message: '".$this->other['message']."'.";
        } else {
            $message = " No message reported.";
        }

        return "Successfully submitted the grade id '$this->objectid' for the user with id '$this->relateduserid' ".
                "for ILP course '$courseilpid' to Banner.".$message;
    }
}
