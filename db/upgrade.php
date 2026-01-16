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
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
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
        if ($dbman->find_key_name($table, $key)) {
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

    // Add location and activeduration fields to qratt_meetings table
    if ($oldversion < 2024063008) {
        $table = new xmldb_table('qratt_meetings');

        // Add location field
        $field = new xmldb_field('location', XMLDB_TYPE_TEXT, null, null, null, null, null, 'endtime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add activeduration field with default 30 minutes (1800 seconds)
        $field = new xmldb_field('activeduration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1800', 'location');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint reached
        upgrade_mod_savepoint(true, 2024063008, 'qratt');
    }

    // Add course information fields to qratt table
    if ($oldversion < 2024063010) {
        $table = new xmldb_table('qratt');

        // Add semester field
        $field = new xmldb_field('semester', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add department field (Jurusan)
        $field = new xmldb_field('department', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'semester');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add study program field (Program Studi)
        $field = new xmldb_field('studyprogram', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'department');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add subject field (Mata Kuliah)
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'studyprogram');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add credits field (SKS)
        $field = new xmldb_field('credits', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'subject');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add class name field (Kelas)
        $field = new xmldb_field('classname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'credits');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add lecturer field (Dosen)
        $field = new xmldb_field('lecturer', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'classname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add day of week field (Hari)
        $field = new xmldb_field('dayofweek', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'lecturer');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add schedule time field (Pukul)
        $field = new xmldb_field('scheduletime', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'dayofweek');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add room field (Ruang)
        $field = new xmldb_field('room', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'scheduletime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint reached
        upgrade_mod_savepoint(true, 2024063010, 'qratt');
    }

    // Add teacherid field to qratt_meetings table
    if ($oldversion < 2025101602) {
        $table = new xmldb_table('qratt_meetings');

        // Add teacherid field
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'activeduration');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add foreign key for teacherid
        $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, array('teacherid'), 'user', array('id'));
        if (!$dbman->find_key_name($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Savepoint reached
        upgrade_mod_savepoint(true, 2025101602, 'qratt');
    }

    return true;
}
