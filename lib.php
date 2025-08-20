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
 * Library of interface functions and constants for module QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Attendance status constants
define('QRATT_STATUS_PRESENT', 1);
define('QRATT_STATUS_ABSENT', 2);
define('QRATT_STATUS_LATE', 3);
define('QRATT_STATUS_EXCUSED', 4);

// Meeting status constants
define('QRATT_MEETING_INACTIVE', 0);
define('QRATT_MEETING_ACTIVE', 1);
define('QRATT_MEETING_ENDED', 2);

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return mixed True if the feature is supported, null otherwise.
 */
function qratt_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPS:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the qratt into the database
 *
 * @param stdClass $qratt Submitted data from the form
 * @param mod_qratt_mod_form $mform The form instance
 * @return int The id of the newly inserted qratt record
 */
function qratt_add_instance(stdClass $qratt, mod_qratt_mod_form $mform = null) {
    global $DB;

    $qratt->timecreated = time();
    $qratt->timemodified = time();

    $qratt->id = $DB->insert_record('qratt', $qratt);

    return $qratt->id;
}

/**
 * Updates an instance of the qratt in the database
 *
 * @param stdClass $qratt An object from the form
 * @param mod_qratt_mod_form $mform The form instance
 * @return boolean Success/Fail
 */
function qratt_update_instance(stdClass $qratt, mod_qratt_mod_form $mform = null) {
    global $DB;

    $qratt->timemodified = time();
    $qratt->id = $qratt->instance;

    return $DB->update_record('qratt', $qratt);
}

/**
 * Removes an instance of the qratt from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function qratt_delete_instance($id) {
    global $DB;

    if (!$qratt = $DB->get_record('qratt', array('id' => $id))) {
        return false;
    }

    // Delete all related records
    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $id));
    foreach ($meetings as $meeting) {
        $DB->delete_records('qratt_attendance', array('meetingid' => $meeting->id));
    }
    $DB->delete_records('qratt_meetings', array('qrattid' => $id));
    
    $DB->delete_records('qratt', array('id' => $id));

    return true;
}

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function qratt_user_outline($course, $user, $mod, $qratt) {
    global $DB;

    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id));
    $attendancecount = 0;
    $totalcount = count($meetings);

    foreach ($meetings as $meeting) {
        $attendance = $DB->get_record('qratt_attendance', 
            array('meetingid' => $meeting->id, 'userid' => $user->id));
        if ($attendance && $attendance->status == QRATT_STATUS_PRESENT) {
            $attendancecount++;
        }
    }

    $result = new stdClass();
    $result->info = get_string('attendancerecord', 'qratt', 
        array('present' => $attendancecount, 'total' => $totalcount));
    $result->time = time();

    return $result;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $qratt the module instance record
 * @return void, is supposed to echo directly
 */
function qratt_user_complete($course, $user, $mod, $qratt) {
    global $DB;

    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingdate ASC');
    
    if (!$meetings) {
        echo get_string('nomeetings', 'qratt');
        return;
    }

    $table = new html_table();
    $table->head = array(
        get_string('meetingnumber', 'qratt'),
        get_string('topic', 'qratt'),
        get_string('date', 'qratt'),
        get_string('status', 'qratt')
    );

    foreach ($meetings as $meeting) {
        $attendance = $DB->get_record('qratt_attendance', 
            array('meetingid' => $meeting->id, 'userid' => $user->id));
        
        $status = get_string('absent', 'qratt');
        if ($attendance) {
            switch ($attendance->status) {
                case QRATT_STATUS_PRESENT:
                    $status = get_string('present', 'qratt');
                    break;
                case QRATT_STATUS_LATE:
                    $status = get_string('late', 'qratt');
                    break;
                case QRATT_STATUS_EXCUSED:
                    $status = get_string('excused', 'qratt');
                    break;
            }
        }

        $table->data[] = array(
            $meeting->meetingnumber,
            $meeting->topic,
            userdate($meeting->meetingdate),
            $status
        );
    }

    echo html_writer::table($table);
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in qratt activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart All activity since this time
 * @return boolean
 */
function qratt_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function qratt_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see qratt_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@see get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 * @return void
 */
function qratt_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}


/**
 * Generate a QR code for a meeting
 *
 * @param int $meetingid The meeting id
 * @param int $expiry The expiry timestamp for this QR code
 * @return string The QR code content
 */
function qratt_generate_qr_code($meetingid, $expiry) {
    global $CFG;
    
    // Use a fallback salt if passwordsaltmain is not available
    $salt = isset($CFG->passwordsaltmain) ? $CFG->passwordsaltmain : 'qratt_default_salt';
    $token = md5($meetingid . $expiry . $salt);
    return $CFG->wwwroot . '/mod/qratt/scan.php?token=' . $token . '&meeting=' . $meetingid;
}

/**
 * Get attendance statistics for a user
 *
 * @param int $qrattid The qratt instance id
 * @param int $userid The user id
 * @return array Statistics array
 */
function qratt_get_user_statistics($qrattid, $userid) {
    global $DB;

    $sql = "SELECT COUNT(*) as total FROM {qratt_meetings} WHERE qrattid = ?";
    $totalmeetings = $DB->count_records_sql($sql, array($qrattid));

    $sql = "SELECT COUNT(*) as present 
            FROM {qratt_attendance} a
            JOIN {qratt_meetings} m ON a.meetingid = m.id
            WHERE m.qrattid = ? AND a.userid = ? AND a.status = ?";
    $present = $DB->count_records_sql($sql, array($qrattid, $userid, QRATT_STATUS_PRESENT));

    $sql = "SELECT COUNT(*) as late 
            FROM {qratt_attendance} a
            JOIN {qratt_meetings} m ON a.meetingid = m.id
            WHERE m.qrattid = ? AND a.userid = ? AND a.status = ?";
    $late = $DB->count_records_sql($sql, array($qrattid, $userid, QRATT_STATUS_LATE));

    $sql = "SELECT COUNT(*) as excused 
            FROM {qratt_attendance} a
            JOIN {qratt_meetings} m ON a.meetingid = m.id
            WHERE m.qrattid = ? AND a.userid = ? AND a.status = ?";
    $excused = $DB->count_records_sql($sql, array($qrattid, $userid, QRATT_STATUS_EXCUSED));

    $absent = $totalmeetings - $present - $late - $excused;

    return array(
        'total' => $totalmeetings,
        'present' => $present,
        'late' => $late,
        'excused' => $excused,
        'absent' => $absent,
        'percentage' => $totalmeetings > 0 ? round(($present / $totalmeetings) * 100, 2) : 0
    );
}
