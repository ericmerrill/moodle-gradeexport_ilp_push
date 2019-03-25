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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade file.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_gradeexport_ilp_push_upgrade($oldversion=0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019032500) {

        // Define table gradeexport_ilp_push_grades to be created.
        $table = new xmldb_table('gradeexport_ilp_push_grades');

        // Adding fields to table gradeexport_ilp_push_grades.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gradetype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('revision', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseilpid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('submitterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('submitterilpid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentilpid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('incompletegrade', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('incompletedeadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('datelastattended', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('resultstatus', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usersubmittime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ilpsendtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table gradeexport_ilp_push_grades.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table gradeexport_ilp_push_grades.
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));
        $table->add_index('courseid-submitterilpid-status', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'submitterilpid', 'status'));
        $table->add_index('courseid-studentid-revision', XMLDB_INDEX_UNIQUE, array('courseid', 'studentid', 'revision'));

        // Conditionally launch create table for gradeexport_ilp_push_grades.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019032500, 'gradeexport', 'ilp_push');
    }


}
