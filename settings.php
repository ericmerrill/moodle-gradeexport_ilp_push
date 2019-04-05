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
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


use gradeexport_ilp_push\settings;


if ($ADMIN->fulltree) {
    $setting = new admin_setting_configtext('ilpurl', new lang_string('ilpurl', 'gradeexport_ilp_push'),
            new lang_string('ilpurl_help', 'gradeexport_ilp_push'), '', PARAM_URL);
    $settings->add($setting);

    $setting = new admin_setting_configtext('ilpid', new lang_string('ilpid', 'gradeexport_ilp_push'),
            new lang_string('ilpid_help', 'gradeexport_ilp_push'), '');
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask('ilppassword', new lang_string('ilppassword', 'gradeexport_ilp_push'),
            new lang_string('ilppassword_help', 'gradeexport_ilp_push'), '', PARAM_URL);
    $settings->add($setting);

}

