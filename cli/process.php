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

define('CLI_SCRIPT', true);

require_once '../../../config.php';
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('submitter' => false,
                                                     'course' => false,
                                                     'termid' => false,
                                                     'source' => false,
                                                     'process' => false,
                                                     'coursetimes' => false,
                                                     'help' => false),
                                               array('s' => 'submitter',
                                                     'c' => 'course',
                                                     't' => 'termid',
                                                     'h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Script for post processing after a bulk import.
Reports on, and removes, excess enrollments.

Options:
-s, --submitter         The user ID of the submitter to process.
-c, --course            The ID of the course to process
-h, --help              Print out this help



Example:
\$sudo -u www-data /usr/bin/php grade/export/ilp_push/cli/process.php -h\"
";

    echo $help;
    die;
}

if (isset($options['submitter']) && isset($options['course'])) {
    $controller = new controller();

    $controller->process_course_user($options['course'], $options['submitter']);
}
