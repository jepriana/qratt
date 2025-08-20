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
 * Database upgrade script for QR Attendance plugin
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_qratt_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to convert qratt_statuses from per-instance to master table
    if ($oldversion < 2024063001) {

        // Define table qratt_statuses
        $table = new xmldb_table('qratt_statuses');

        // Remove foreign key constraint if it exists
        $key = new xmldb_key('qrattid', XMLDB_KEY_FOREIGN, array('qrattid'), 'qratt', array('id'));
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Remove index if it exists
        $index = new xmldb_index('qrattstatus', XMLDB_INDEX_UNIQUE, array('qrattid', 'statusvalue'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Remove qrattid field
        $field = new xmldb_field('qrattid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Add new fields
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'visible');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sortorder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add new index for statusvalue
        $index = new xmldb_index('statusvalue', XMLDB_INDEX_UNIQUE, array('statusvalue'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Clear existing status records and create new master records
        $DB->delete_records('qratt_statuses');

        // Define default attendance statuses
        $defaultstatuses = array(
            array(
                'statusvalue' => 1, 
                'description' => 'Present', 
                'grade' => 1.00, 
                'visible' => 1, 
                'sortorder' => 1,
                'timecreated' => time(),
                'timemodified' => time()
            ),
            array(
                'statusvalue' => 2, 
                'description' => 'Absent', 
                'grade' => 0.00, 
                'visible' => 1, 
                'sortorder' => 4,
                'timecreated' => time(),
                'timemodified' => time()
            ),
            array(
                'statusvalue' => 3, 
                'description' => 'Late', 
                'grade' => 0.50, 
                'visible' => 1, 
                'sortorder' => 2,
                'timecreated' => time(),
                'timemodified' => time()
            ),
            array(
                'statusvalue' => 4, 
                'description' => 'Excused', 
                'grade' => 0.00, 
                'visible' => 1, 
                'sortorder' => 3,
                'timecreated' => time(),
                'timemodified' => time()
            )
        );

        // Insert default statuses
        foreach ($defaultstatuses as $status) {
            $DB->insert_record('qratt_statuses', (object)$status);
        }

        // Savepoint reached
        upgrade_mod_savepoint(true, 2024063001, 'qratt');
    }

    return true;
}
