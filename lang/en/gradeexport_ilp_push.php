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
 * Strings for the plugin, in English.
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['eventgradeexported'] = 'Banner grade exported';
$string['pluginname'] = 'Banner Grades';
$string['privacy:metadata'] = 'The Banner grade export TODO Privacy.';
//$string['timeexported'] = 'Last downloaded from this course';
$string['ilp_push:publish'] = 'Publish Banner grade export';
$string['ilp_push:view'] = 'Use Banner grade export';

// Rule validation.
$string['invalid_datelastattended_early'] = 'Date last attended cannot be before the start of the course.';
$string['invalid_datelastattended_late'] = 'Date last attended cannot be after the end of the course.';
$string['invalid_datelastattended_missing'] = 'Date last attended must be entered for a failing student.';

$string['invalid_grade'] = 'A valid grade must be selected.';

$string['invalid_incomplete_date_early'] = 'Incomplete deadline cannot be before the end of the course.';
$string['invalid_incomplete_date_late'] = 'Incomplete deadline cannot be more than a year after the course ends.';
$string['invalid_incomplete_date_missing'] = 'Incomplete deadline must be entered for an incomplete grade.';
$string['invalid_incomplete_grade_missing'] = 'A default incomplete grade must be selected.';
$string['invalid_incomplete_grade_wrong'] = 'Default incomplete grade cannot be changed from the default.';


// Settings.
$string['ilpid'] = 'ILP Connection ID';
$string['ilpid_help'] = 'The connection ID setup in the ILP admin.';
$string['ilppassword'] = 'ILP Connection Password';
$string['ilppassword_help'] = 'The connection password setup in the ILP admin.';
$string['ilpurl'] = 'ILP URL';
$string['ilpurl_help'] = 'The base URL of the ILP server being used';
