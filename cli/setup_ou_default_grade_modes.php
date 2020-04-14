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
 * Create a bunch of default grade modes.
 *
 * @package    gradeexport
 * @subpackage ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use gradeexport_ilp_push\local\controller;
use gradeexport_ilp_push\local\data;

define('CLI_SCRIPT', true);

require_once '../../../config.php';
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('run' => false,
                                                     'help' => false),
                                               array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Script for post processing after a bulk import.
Reports on, and removes, excess enrollments.

Options:
--run                   Do it!
-h, --help              Print out this help



Example:
\$sudo -u www-data /usr/bin/php grade/export/ilp_push/cli/setup_ou_default_grade_modes.php -h\"
";

    echo $help;
    die;
}

if (isset($options['run'])) {
    $options = ['Standard Letter with Progress (R)' =>
                   ['A' => [],
                    'A-' => [],
                    'B+' => [],
                    'B' => [],
                    'B-' => [],
                    'C+' => [],
                    'C' => [],
                    'C-' => [],
                    'D+' => [],
                    'D' => [],
                    'F' => ['requirelastdate' => 1],
                    'I' => ['isincomplete' => 1],
                    'I.' => [],
                    'P' => []],
                'Satisfactory/Unsatisfactory (P)' =>
                   ['S' => [],
                    'U' => [],
                    'I' => ['isincomplete' => 1]],
                'Satisfactory/Unsatisfactory with Progress(O)' =>
                   ['S' => [],
                    'U' => [],
                    'I' => ['isincomplete' => 1],
                    'I.' => [],
                    'P' => []],
                'Dissertation, Thesis Research (D)' =>
                   ['S' => [],
                    'SP' => [],
                    'NP' => [],
                    'U' => []]];

    $modesort = 2;
    foreach ($options as $name => $opts) {
        $mode = new data\grade_mode();
        $mode->name = $name;
        $mode->sortorder = $modesort++;
        $mode->save_to_db();

        $optsort = 1;
        foreach ($opts as $value => $settings) {
            $opt = new data\grade_mode_option();
            $opt->modeid = $mode->id;
            $opt->displayname = $value;
            $opt->bannervalue = $value;
            $opt->sortorder = $optsort++;
            if (!empty($settings)) {
                foreach ($settings as $key => $val) {
                    $opt->$key = $val;
                }
            }
            $opt->save_to_db();

        }
    }
}
