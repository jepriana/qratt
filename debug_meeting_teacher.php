<?php
// Debug script to check meeting teacher data
// Run this from Moodle root directory with: php mod/qratt/debug_meeting_teacher.php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

// Only run if debugging is enabled
if (!debugging()) {
    echo "Enable debugging in Moodle to run this script.\n";
    exit;
}

require_login();

echo "=== QR Attendance Meeting Teacher Debug ===\n\n";

// Check if teacherid field exists in qratt_meetings table
$dbman = $DB->get_manager();
$table = new xmldb_table('qratt_meetings');
$field = new xmldb_field('teacherid');

if ($dbman->field_exists($table, $field)) {
    echo "✓ teacherid field exists in qratt_meetings table\n";
    
    // Get field info
    $columns = $DB->get_columns('qratt_meetings');
    if (isset($columns['teacherid'])) {
        $fieldinfo = $columns['teacherid'];
        echo "  Field type: " . $fieldinfo->type . "\n";
        echo "  Max length: " . $fieldinfo->max_length . "\n";
        echo "  Not null: " . ($fieldinfo->not_null ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "✗ teacherid field does NOT exist in qratt_meetings table\n";
    echo "Run the upgrade script to add the field.\n";
    exit;
}

echo "\n";

// Check current meetings and their teacher data
$meetings = $DB->get_records('qratt_meetings', array(), 'id ASC', 'id, qrattid, meetingnumber, topic, teacherid');

if (empty($meetings)) {
    echo "No meetings found in database.\n";
} else {
    echo "=== Current Meetings ===\n";
    foreach ($meetings as $meeting) {
        echo "Meeting ID: {$meeting->id}, Number: {$meeting->meetingnumber}, Topic: {$meeting->topic}\n";
        echo "  Teacher ID: " . ($meeting->teacherid ? $meeting->teacherid : 'NULL') . "\n";
        
        if ($meeting->teacherid) {
            $teacher = $DB->get_record('user', array('id' => $meeting->teacherid), 'id, firstname, lastname');
            if ($teacher) {
                echo "  Teacher Name: " . fullname($teacher) . "\n";
            } else {
                echo "  Teacher Name: User not found (ID: {$meeting->teacherid})\n";
            }
        }
        echo "\n";
    }
}

// Check version
$plugin = new stdClass();
require(dirname(__FILE__).'/version.php');
echo "=== Plugin Version Info ===\n";
echo "Current version: {$plugin->version}\n";
echo "Expected version for teacherid field: 2024063011\n";

if ($plugin->version >= 2024063011) {
    echo "✓ Version supports teacherid field\n";
} else {
    echo "✗ Version does not support teacherid field - upgrade needed\n";
}

echo "\nDebug complete.\n";