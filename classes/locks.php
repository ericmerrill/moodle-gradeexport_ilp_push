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
 * A class for dealing with locks.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradeexport_ilp_push;

defined('MOODLE_INTERNAL') || die();

use core\lock;

/**
 * A class for dealing with locks.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locks {

    protected static $factory = [];

    protected static function get_factory($type = 'gradeexport_ilp_push') {
        if (!isset(static::$factory[$type])) {
            static::$factory[$type] = lock\lock_config::get_lock_factory($type);
        }

        return static::$factory[$type];
    }

    public static function get_course_submitter_lock($courseid, $submitterid, $timeout = null, $maxlifetime = null) {
        $factory = static::get_factory();

        // TODO - Probably from settings...
        if (is_null($timeout)) {
            $timeout = 10;
        }
        if (is_null($maxlifetime)) {
            $maxlifetime = 600;
        }

        $key = 'gradeexport_ilp_push-c-'.$courseid.'-s-'.$submitterid;

        $lock = $factory->get_lock($key, $timeout, $maxlifetime);

        return $lock;
    }

    public static function get_course_lock($courseid, $timeout = null, $maxlifetime = null) {
        $factory = static::get_factory();

        // TODO - Probably from settings...
        if (is_null($timeout)) {
            $timeout = 10;
        }
        if (is_null($maxlifetime)) {
            $maxlifetime = 600;
        }

        $key = 'gradeexport_ilp_push-c-'.$courseid;

        $lock = $factory->get_lock($key, $timeout, $maxlifetime);

        return $lock;
    }
}


