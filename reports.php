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
 * Reports page for QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'overview', PARAM_ALPHA);
$meetingid = optional_param('meetingid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('qratt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$qratt = $DB->get_record('qratt', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qratt:viewreports', $context);

$PAGE->set_url('/mod/qratt/reports.php', array('id' => $cm->id, 'action' => $action));
$PAGE->set_title(format_string($qratt->name));
$PAGE->set_heading(format_string($course->fullname));

// Handle downloads
if ($download == 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    
    $filename = clean_filename($qratt->name . '_attendance_' . date('Y-m-d'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    
    // Get all meetings and enrolled users
    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');
    $enrolledusers = get_enrolled_users($context, 'mod/qratt:canbelisted', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname, u.firstname');
    
    // Build CSV headers
    $headers = array(get_string('firstname'), get_string('lastname'), get_string('email'));
    foreach ($meetings as $meeting) {
        $headers[] = get_string('meeting', 'qratt') . ' ' . $meeting->meetingnumber;
    }
    $headers[] = get_string('totalpresent', 'qratt');
    $headers[] = get_string('totalabsent', 'qratt');
    $headers[] = get_string('percentage', 'qratt');
    
    $csvexport->add_data($headers);
    
    // Build CSV data
    foreach ($enrolledusers as $user) {
        $row = array($user->firstname, $user->lastname, $user->email);
        
        $totalpresent = 0;
        $totalexcused = 0;
        
        foreach ($meetings as $meeting) {
            $attendance = $DB->get_record('qratt_attendance', 
                array('meetingid' => $meeting->id, 'userid' => $user->id));
            
            if ($attendance) {
                switch ($attendance->status) {
                    case QRATT_STATUS_PRESENT:
                        $row[] = get_string('present', 'qratt');
                        $totalpresent++;
                        break;
                    case QRATT_STATUS_LATE:
                        $row[] = get_string('late', 'qratt');
                        $totalpresent++;
                        break;
                    case QRATT_STATUS_EXCUSED:
                        $row[] = get_string('excused', 'qratt');
                        $totalexcused++;
                        break;
                    default:
                        $row[] = get_string('absent', 'qratt');
                }
            } else {
                $row[] = get_string('absent', 'qratt');
            }
        }
        
        $totalmeetings = count($meetings);
        // Correctly calculate total absent count
        $totalabsent = $totalmeetings - $totalpresent - $totalexcused;
        // Base percentage on meetings that were not excused
        $attendable_meetings = $totalmeetings - $totalexcused;
        $percentage = $attendable_meetings > 0 ? round(($totalpresent / $attendable_meetings) * 100, 2) : 0;
        
        $row[] = $totalpresent;
        $row[] = $totalabsent;
        $row[] = $percentage . '%';
        
        $csvexport->add_data($row);
    }
    
    $csvexport->download_file();
    exit;
}

// Output starts here
echo $OUTPUT->header();

// Display navigation tabs
$tabs = array();
$tabs[] = new tabobject('view', new moodle_url('/mod/qratt/view.php', array('id' => $cm->id)), 
                        get_string('overview', 'qratt'));
$tabs[] = new tabobject('meetings', new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
                        get_string('meetings', 'qratt'));
$tabs[] = new tabobject('reports', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id)), 
                        get_string('reports', 'qratt'));

echo $OUTPUT->tabtree($tabs, 'reports');

// Sub-navigation for reports
$reporttabs = array();
$reporttabs[] = new tabobject('overview', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id, 'action' => 'overview')), 
                             get_string('overview', 'qratt'));
$reporttabs[] = new tabobject('bymeeting', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id, 'action' => 'bymeeting')), 
                             get_string('bymeeting', 'qratt'));
$reporttabs[] = new tabobject('bystudent', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id, 'action' => 'bystudent')), 
                             get_string('bystudent', 'qratt'));

echo $OUTPUT->tabtree($reporttabs, $action, null, null, true);

switch ($action) {
    case 'bymeeting':
        // Report by meeting
        echo $OUTPUT->heading(get_string('reportbymeeting', 'qratt'), 2);
        
        $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');
        
        if (!$meetings) {
            echo $OUTPUT->notification(get_string('nomeetings', 'qratt'), 'notifymessage');
            break;
        }
        
        foreach ($meetings as $meeting) {
            echo $OUTPUT->heading(get_string('meeting', 'qratt') . ' ' . $meeting->meetingnumber . ': ' . $meeting->topic, 3);
            
            // Get attendance for this meeting
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, a.status, a.scantime
                    FROM {user} u
                    LEFT JOIN {qratt_attendance} a ON (a.userid = u.id AND a.meetingid = ?)
                    WHERE u.id IN (
                        SELECT DISTINCT eu.userid 
                        FROM {enrol} e 
                        JOIN {user_enrolments} eu ON e.id = eu.enrolid 
                        WHERE e.courseid = ? AND e.status = 0 AND eu.status = 0
                    )
                    ORDER BY u.lastname, u.firstname";
            
            $attendees = $DB->get_records_sql($sql, array($meeting->id, $course->id));
            
            if ($attendees) {
                $table = new html_table();
                $table->head = array(
                    get_string('student', 'qratt'),
                    get_string('email'),
                    get_string('status', 'qratt'),
                    get_string('scantime', 'qratt')
                );
                
                $present = 0;
                $late = 0;
                $excused = 0;
                $absent = 0;
                
                foreach ($attendees as $attendee) {
                    $statustext = get_string('absent', 'qratt');
                    $scantime = '-';
                    
                    if ($attendee->status !== null) {
                        switch ($attendee->status) {
                            case QRATT_STATUS_PRESENT:
                                $statustext = get_string('present', 'qratt');
                                $present++;
                                break;
                            case QRATT_STATUS_LATE:
                                $statustext = get_string('late', 'qratt');
                                $late++;
                                break;
                            case QRATT_STATUS_EXCUSED:
                                $statustext = get_string('excused', 'qratt');
                                $excused++;
                                break;
                            default:
                                $absent++;
                        }
                        
                        if ($attendee->scantime) {
                            $scantime = userdate($attendee->scantime, get_string('strftimedatetimeshort'));
                        }
                    } else {
                        $absent++;
                    }
                    
                    $table->data[] = array(
                        $attendee->firstname . ' ' . $attendee->lastname,
                        $attendee->email,
                        $statustext,
                        $scantime
                    );
                }
                
                echo html_writer::table($table);
                
                // Summary
                $total = count($attendees);
                echo html_writer::div(
                    html_writer::tag('strong', get_string('summary', 'qratt') . ': ') .
                    get_string('present', 'qratt') . ': ' . $present . ' | ' .
                    get_string('late', 'qratt') . ': ' . $late . ' | ' .
                    get_string('excused', 'qratt') . ': ' . $excused . ' | ' .
                    get_string('absent', 'qratt') . ': ' . $absent . ' | ' .
                    get_string('total') . ': ' . $total,
                    'meeting-summary mb-4 p-2 bg-light'
                );
            }
        }
        break;
        
    case 'bystudent':
        // Report by student
        echo $OUTPUT->heading(get_string('reportbystudent', 'qratt'), 2);
        
        $enrolledusers = get_enrolled_users($context, 'mod/qratt:canbelisted', 0, 'u.id, u.firstname, u.lastname, u.email', 'u.lastname, u.firstname');
        $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');
        
        if (!$enrolledusers) {
            echo $OUTPUT->notification(get_string('nousers', 'qratt'), 'notifymessage');
            break;
        }
        
        if (!$meetings) {
            echo $OUTPUT->notification(get_string('nomeetings', 'qratt'), 'notifymessage');
            break;
        }
        
        $table = new html_table();
        $table->head = array(
            get_string('student', 'qratt'),
            get_string('email')
        );
        
        // Add meeting columns
        foreach ($meetings as $meeting) {
            $table->head[] = get_string('meeting', 'qratt') . ' ' . $meeting->meetingnumber;
        }
        
        $table->head[] = get_string('totalpresent', 'qratt');
        $table->head[] = get_string('percentage', 'qratt');
        
        foreach ($enrolledusers as $user) {
            $row = array(
                $user->firstname . ' ' . $user->lastname,
                $user->email
            );
            
            $totalpresent = 0;
            
            foreach ($meetings as $meeting) {
                $attendance = $DB->get_record('qratt_attendance', 
                    array('meetingid' => $meeting->id, 'userid' => $user->id));
                
                if ($attendance) {
                    switch ($attendance->status) {
                        case QRATT_STATUS_PRESENT:
                            $row[] = html_writer::span(get_string('present', 'qratt'), 'badge badge-success');
                            $totalpresent++;
                            break;
                        case QRATT_STATUS_LATE:
                            $row[] = html_writer::span(get_string('late', 'qratt'), 'badge badge-warning');
                            $totalpresent++;
                            break;
                        case QRATT_STATUS_EXCUSED:
                            $row[] = html_writer::span(get_string('excused', 'qratt'), 'badge badge-info');
                            break;
                        default:
                            $row[] = html_writer::span(get_string('absent', 'qratt'), 'badge badge-danger');
                    }
                } else {
                    $row[] = html_writer::span(get_string('absent', 'qratt'), 'badge badge-danger');
                }
            }
            
            $totalmeetings = count($meetings);
            $percentage = $totalmeetings > 0 ? round(($totalpresent / $totalmeetings) * 100, 2) : 0;
            
            $row[] = $totalpresent . '/' . $totalmeetings;
            $row[] = $percentage . '%';
            
            $table->data[] = $row;
        }
        
        echo html_writer::table($table);
        break;
        
    default:
        // Overview report
        echo $OUTPUT->heading(get_string('attendanceoverview', 'qratt'), 2);
        
        $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id));
        $enrolledusers = get_enrolled_users($context, 'mod/qratt:canbelisted');
        
        $totalmeetings = count($meetings);
        $totalstudents = count($enrolledusers);
        
        if ($totalmeetings == 0 || $totalstudents == 0) {
            echo $OUTPUT->notification(get_string('nodata', 'qratt'), 'notifymessage');
            break;
        }
        
        // Overall statistics
        $sql = "SELECT 
                    COUNT(CASE WHEN a.status = ? THEN 1 END) as present,
                    COUNT(CASE WHEN a.status = ? THEN 1 END) as late,
                    COUNT(CASE WHEN a.status = ? THEN 1 END) as excused,
                    COUNT(CASE WHEN a.status = ? OR a.status IS NULL THEN 1 END) as absent
                FROM {qratt_meetings} m
                LEFT JOIN {qratt_attendance} a ON m.id = a.meetingid
                WHERE m.qrattid = ?";
        
        $stats = $DB->get_record_sql($sql, array(
            QRATT_STATUS_PRESENT, 
            QRATT_STATUS_LATE, 
            QRATT_STATUS_EXCUSED, 
            QRATT_STATUS_ABSENT, 
            $qratt->id
        ));
        
        // Calculate totals including non-recorded attendance
        $totalexpected = $totalmeetings * $totalstudents;
        $totalrecorded = $stats->present + $stats->late + $stats->excused;
        $totalabsent = $totalexpected - $totalrecorded;
        
        $overallpercentage = $totalexpected > 0 ? round((($stats->present + $stats->late) / $totalexpected) * 100, 2) : 0;
        
        // Display statistics
        echo html_writer::div(
            html_writer::tag('div',
                html_writer::tag('h4', get_string('overallstatistics', 'qratt')) .
                html_writer::tag('p', get_string('totalmeetings', 'qratt') . ': ' . $totalmeetings) .
                html_writer::tag('p', get_string('totalstudents', 'qratt') . ': ' . $totalstudents) .
                html_writer::tag('p', get_string('overallattendance', 'qratt') . ': ' . $overallpercentage . '%'),
                array('class' => 'col-md-4')
            ) .
            html_writer::tag('div',
                html_writer::tag('h4', get_string('attendancebreakdown', 'qratt')) .
                html_writer::tag('p', get_string('present', 'qratt') . ': ' . $stats->present) .
                html_writer::tag('p', get_string('late', 'qratt') . ': ' . $stats->late) .
                html_writer::tag('p', get_string('excused', 'qratt') . ': ' . $stats->excused) .
                html_writer::tag('p', get_string('absent', 'qratt') . ': ' . $totalabsent),
                array('class' => 'col-md-4')
            ),
            'row statistics-overview mb-4'
        );
        
        // Meeting-wise summary
        echo $OUTPUT->heading(get_string('meetingwisesummary', 'qratt'), 3);
        
        $table = new html_table();
        $table->head = array(
            get_string('meetingnumber', 'qratt'),
            get_string('topic', 'qratt'),
            get_string('date', 'qratt'),
            get_string('present', 'qratt'),
            get_string('late', 'qratt'),
            get_string('excused', 'qratt'),
            get_string('absent', 'qratt'),
            get_string('percentage', 'qratt')
        );
        
        foreach ($meetings as $meeting) {
            $sql = "SELECT 
                        COUNT(CASE WHEN a.status = ? THEN 1 END) as present,
                        COUNT(CASE WHEN a.status = ? THEN 1 END) as late,
                        COUNT(CASE WHEN a.status = ? THEN 1 END) as excused
                    FROM {qratt_attendance} a
                    WHERE a.meetingid = ?";
            
            $meetingstats = $DB->get_record_sql($sql, array(
                QRATT_STATUS_PRESENT,
                QRATT_STATUS_LATE,
                QRATT_STATUS_EXCUSED,
                $meeting->id
            ));
            
            $meetingabsent = $totalstudents - ($meetingstats->present + $meetingstats->late + $meetingstats->excused);
            $meetingpercentage = $totalstudents > 0 ? round((($meetingstats->present + $meetingstats->late) / $totalstudents) * 100, 2) : 0;
            
            $table->data[] = array(
                $meeting->meetingnumber,
                $meeting->topic,
                userdate($meeting->meetingdate, get_string('strftimedaydate')),
                $meetingstats->present,
                $meetingstats->late,
                $meetingstats->excused,
                $meetingabsent,
                $meetingpercentage . '%'
            );
        }
        
        echo html_writer::table($table);
        break;
}

// Download button
echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id, 'download' => 'csv')),
        get_string('downloadcsv', 'qratt'),
        'get'
    ),
    'download-section mt-4'
);

echo $OUTPUT->footer();
