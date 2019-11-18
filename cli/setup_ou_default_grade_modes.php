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
 * Process records through to ILP.
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
    $options = ['Standard Letter' =>
                   [1 => 'A',
                    2 => 'A-',
                    3 => 'B+',
                    4 => 'B',
                    5 => 'B-',
                    6 => 'C+',
                    7 => 'C',
                    8 => 'C-',
                    9 => 'D+',
                    10 => 'D',
                    11 => 'F',
                    12 => 'I'],
                'Standard Letter with Progress (R)' =>
                   [1 => 'A',
                    2 => 'A-',
                    3 => 'B+',
                    4 => 'B',
                    5 => 'B-',
                    6 => 'C+',
                    7 => 'C',
                    8 => 'C-',
                    9 => 'D+',
                    10 => 'D',
                    11 => 'F',
                    12 => 'I',
                    13 => 'I.',
                    14 => 'P'],
                'Satisfactory/Unsatisfactory (P)' =>
                   [1 => 'S',
                    2 => 'U',
                    3 => 'I'],
                'Satisfactory/Unsatisfactory with Progress(O))' =>
                   [1 => 'S',
                    2 => 'U',
                    3 => 'I',
                    4 => 'I.',
                    5 => 'P'],
                'Dissertation, Thesis Research (D)' =>
                   [1 => 'S',
                    2 => 'SP',
                    3 => 'NP',
                    4 => 'U']];


    foreach ($options as $name => $opts) {
        $mode = new data\grade_mode();
        $mode->name = $name;
        $mode->save_to_db();

        foreach ($opts as $value) {
            $opt = new data\grade_mode_option();
            $opt->modeid = $mode->id;
            $opt->displayname = $value;
            $opt->bannervalue = $value;
            $opt->save_to_db();

        }
    }
}
