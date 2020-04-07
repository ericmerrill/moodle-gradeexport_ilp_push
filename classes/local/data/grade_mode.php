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
 * A class that represents a grading mode.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push\local\data;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * A class that represents a grading mode.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_mode extends base {
    /** @var array Array of keys that go in the database object */
    protected $dbkeys = ['id', 'name', 'enabled', 'sortorder', 'additional', 'timecreated', 'timemodified', 'usermodified'];

    /** @var array An array of default property->value pairs */
    protected $defaults = ['enabled' => 1, 'usermodified' => 0, 'sortorder' => 0, 'usermodified' => 0];

    /** @var array Array of keys will be used to see if two objects are the same. */
    protected $diffkeys = ['name', 'enabled', 'additional'];

    /**
     * The table name of this object.
     */
    const TABLE = 'gradeexport_ilp_push_grmodes';

    /** @var grade_mode_option[] Array of grade mode options. */
    protected $gradeoptions = null;

    public function get_current_grade_options() {
        $options = $this->get_all_grade_options();

        $output = [];
        foreach ($options as $option) {
            if ($option->mostcurrent && $option->enabled) {
                $output[] = $option;
            }
        }

        return $output;
    }

    public function get_all_grade_options() {
        if (empty($this->gradeoptions)) {
            $this->gradeoptions = grade_mode_option::get_for_params(['modeid' => $this->id], 'sortorder ASC');
        }

        return $this->gradeoptions;
    }

    public function get_grade($id) {
        $options = $this->get_all_grade_options();

        if (isset($options[$id])) {
            return $options[$id];
        }

        return false;
    }

    public function grade_id_is_incomplete($id) {
        $options = $this->get_all_grade_options();

        if (isset($options[$id]) && !empty($options[$id]->isincomplete)) {
            return true;
        }

        return false;
    }

    public function grade_id_is_failing($id) {
        $options = $this->get_all_grade_options();

        if (isset($options[$id]) && !empty($options[$id]->requirelastdate)) {
            return true;
        }

        return false;
    }

    public function get_grade_for_string($value) {
        $options = $this->get_all_grade_options();

        foreach ($options as $option) {
            if (isset($option->matchvalue)) {
                $match = $option->matchvalue;
            } else {
                $match = $option->bannervalue;
            }

            if ($match === $value) {
                return $option;
            }
        }

        return false;
    }

    public function get_grade_menu_options() {
        $gradeoptions = $this->get_current_grade_options();

        $options = [];
        foreach ($gradeoptions as $option) {
            $options[$option->id] = $option->get_display_name();
        }

        return $options;
    }
}


