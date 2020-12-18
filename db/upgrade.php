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

require_once($CFG->dirroot.'/grade/export/ilp_push/upgradelib.php');

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

    if ($oldversion < 2019040400) {

        // Changing nullability of field grade on table gradeexport_ilp_push_grades to null.
        $table = new xmldb_table('gradeexport_ilp_push_grades');
        $field = new xmldb_field('grade', XMLDB_TYPE_CHAR, '127', null, null, null, null, 'studentilpid');

        // Launch change of nullability for field grade.
        $dbman->change_field_notnull($table, $field);

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019040400, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019040900) {

        // Define index courseid-studentid-revision (unique) to be dropped form gradeexport_ilp_push_grades.
        $table = new xmldb_table('gradeexport_ilp_push_grades');
        $index = new xmldb_index('courseid-studentid-revision', XMLDB_INDEX_UNIQUE, array('courseid', 'studentid', 'revision'));

        // Conditionally launch drop index courseid-studentid-revision.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('courseid-studentid-gradetype-revision', XMLDB_INDEX_UNIQUE, array('courseid', 'studentid', 'gradetype', 'revision'));

        // Conditionally launch add index courseid-studentid-gradetype-revision.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019040900, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019041600) {

        // Rename field additionaldata on table gradeexport_ilp_push_grades to additional.
        $table = new xmldb_table('gradeexport_ilp_push_grades');
        $field = new xmldb_field('additionaldata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'resultstatus');

        // Launch rename field additionaldata.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'additional');
        }

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019041600, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019111800) {

        // Define table gradeexport_ilp_push_grmodes to be created.
        $table = new xmldb_table('gradeexport_ilp_push_grmodes');

        // Adding fields to table gradeexport_ilp_push_grmodes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table gradeexport_ilp_push_grmodes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for gradeexport_ilp_push_grmodes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table gradeexport_ilp_push_modeopt to be created.
        $table = new xmldb_table('gradeexport_ilp_push_modeopt');

        // Adding fields to table gradeexport_ilp_push_modeopt.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('displayname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('bannervalue', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mostcurrent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table gradeexport_ilp_push_modeopt.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('modeid', XMLDB_KEY_FOREIGN, ['modeid'], 'gradeexport_ilp_push_grmodes', ['id']);

        // Conditionally launch create table for gradeexport_ilp_push_modeopt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019111800, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019111801) {

        // Define field sortorder to be added to gradeexport_ilp_push_modeopt.
        $table = new xmldb_table('gradeexport_ilp_push_modeopt');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'mostcurrent');

        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field sortorder to be added to gradeexport_ilp_push_grmodes.
        $table = new xmldb_table('gradeexport_ilp_push_grmodes');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'enabled');

        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019111801, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019121000) {
        // Define field gradeoptid to be added to gradeexport_ilp_push_grades.
        $table = new xmldb_table('gradeexport_ilp_push_grades');
        $field = new xmldb_field('gradeoptid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'studentilpid');

        // Conditionally launch add field gradeoptid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key gradeoptid (foreign) to be added to gradeexport_ilp_push_grades.
        $table = new xmldb_table('gradeexport_ilp_push_grades');
        $key = new xmldb_key('gradeoptid', XMLDB_KEY_FOREIGN, ['gradeoptid'], 'gradeexport_ilp_push_modeopt', ['id']);

        // Launch add key gradeoptid.
        $dbman->add_key($table, $key);

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019121000, 'gradeexport', 'ilp_push');
    }

    if ($oldversion < 2019121001) {
        // Upgrade the base grade mode.
        gradeexport_ilp_push_upgrade_create_default_grade_mode();

        // Ilp_push savepoint reached.
        upgrade_plugin_savepoint(true, 2019121001, 'gradeexport', 'ilp_push');
    }


    return true;

}
