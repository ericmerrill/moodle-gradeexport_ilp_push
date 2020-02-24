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
use gradeexport_ilp_push\log;

if ($ADMIN->fulltree) {
    $setting = new admin_setting_configtext('gradeexport_ilp_push/ilpurl', new lang_string('ilpurl', 'gradeexport_ilp_push'),
            new lang_string('ilpurl_help', 'gradeexport_ilp_push'), '', PARAM_URL);
    $settings->add($setting);

    $setting = new admin_setting_configtext('gradeexport_ilp_push/ilpid', new lang_string('ilpid', 'gradeexport_ilp_push'),
            new lang_string('ilpid_help', 'gradeexport_ilp_push'), '');
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask('gradeexport_ilp_push/ilppassword', new lang_string('ilppassword', 'gradeexport_ilp_push'),
            new lang_string('ilppassword_help', 'gradeexport_ilp_push'), '', PARAM_URL);
    $settings->add($setting);

    $settings->add(new admin_setting_configfile('gradeexport_ilp_push/logpath', get_string('logpath', 'gradeexport_ilp_push'),
            get_string('logpath_help', 'gradeexport_ilp_push'), ''));

    $loggingoptions = array(log::ERROR_NONE => get_string('error_all', 'gradeexport_ilp_push'),
                            log::ERROR_NOTICE => get_string('error_notice', 'gradeexport_ilp_push'),
                            log::ERROR_WARN => get_string('error_warn', 'gradeexport_ilp_push'),
                            log::ERROR_MAJOR => get_string('error_major', 'gradeexport_ilp_push'));

    $settings->add(new admin_setting_configselect('gradeexport_ilp_push/logginglevel',
            get_string('logginglevel', 'gradeexport_ilp_push'),
            get_string('logginglevel_help', 'gradeexport_ilp_push'), log::ERROR_NOTICE, $loggingoptions));

    $settings->add(new \gradeexport_ilp_push\output\admin_setting_grademodes());

    //$grademodes = banner_grades::get_all_grade_modes();
//     foreach ($grademodes as $grademode) {
//         $page = new \admin_settingpage('gradeexport_ilp_push_mode_'.$grademode->id, 'TODO: '.$grademode->id, 'moodle/site:config', true);
//
//         $page
//
//         $setting->add($page);
//     }

}

$ADMIN->add('gradeexports', new admin_externalpage('gradeexport_ilp_push_grademode',
        'TODO Grademode Page',
        new moodle_url('/grade/export/ilp_push/settings_grademode.php'), 'moodle/site:config', true));
