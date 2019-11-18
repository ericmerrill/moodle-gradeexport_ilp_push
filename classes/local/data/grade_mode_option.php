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
 * A class that represents a grading mode grade option.
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
 * A class that represents a grading mode grade option.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_mode_option extends base {
    /** @var array Array of keys that go in the database object */
    protected $dbkeys = ['id', 'modeid', 'displayname', 'bannervalue', 'enabled', 'sortorder', 'version', 'mostcurrent',
                         'additional', 'timecreated', 'timemodified', 'usermodified'];

    /** @var array An array of default property->value pairs */
    protected $defaults = ['enabled' => 1, 'version' => 0, 'mostcurrent' => 1, 'sortorder' => 0, 'usermodified' => 0];

    /** @var array Array of keys will be used to see if two objects are the same. */
    protected $diffkeys = ['modeid', 'displayname', 'bannervalue', 'enabled', 'sortorder', 'additional'];

    /**
     * The table name of this object.
     */
    const TABLE = 'gradeexport_ilp_push_modeopt';

}


