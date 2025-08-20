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
 * Prints a particular instance of qratt
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // qratt instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('qratt', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $qratt      = $DB->get_record('qratt', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $qratt      = $DB->get_record('qratt', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $qratt->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('qratt', $qratt->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check if user has manage capability - if so, redirect to meetings overview
$canmanage = has_capability('mod/qratt:manage', $context);
if ($canmanage) {
    redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)));
}

// Print the page header.
$PAGE->set_url('/mod/qratt/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($qratt->name));
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($qratt->intro) {
    echo $OUTPUT->box(format_module_intro('qratt', $qratt, $cm->id), 'generalbox mod_introbox', 'qrattintro');
}

// Check if user has manage capability
$canmanage = has_capability('mod/qratt:manage', $context);
$canview = has_capability('mod/qratt:view', $context);

if (!$canview) {
    notice(get_string('nopermissions', 'error', 'view'), $CFG->wwwroot.'/course/view.php?id='.$course->id);
}

// Display navigation tabs
$tabs = array();
$tabs[] = new tabobject('view', new moodle_url('/mod/qratt/view.php', array('id' => $cm->id)), 
                        get_string('overview', 'qratt'));

if ($canmanage) {
    $tabs[] = new tabobject('meetings', new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
                            get_string('meetings', 'qratt'));
    $tabs[] = new tabobject('attendance', new moodle_url('/mod/qratt/attendance.php', array('id' => $cm->id)), 
                            get_string('manualattendance', 'qratt'));
    $tabs[] = new tabobject('reports', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id)), 
                            get_string('reports', 'qratt'));
}

if (count($tabs) > 1) {
    echo $OUTPUT->tabtree($tabs, 'view');
}

// Display overview content
echo $OUTPUT->heading(get_string('overview', 'qratt'), 2);

// Get meetings for this QR Attendance instance
$meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingdate DESC');

if (!$meetings) {
    echo $OUTPUT->notification(get_string('nomeetings', 'qratt'), 'notifymessage');
    
    if ($canmanage) {
        echo html_writer::tag('p', get_string('nomeetingsinfo', 'qratt'));
        echo $OUTPUT->single_button(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'add')), 
                                   get_string('addmeeting', 'qratt'), 'get');
    }
} else {
    // Display meetings table
    $table = new html_table();
    $table->head = array(
        get_string('meetingnumber', 'qratt'),
        get_string('topic', 'qratt'),
        get_string('date', 'qratt'),
        get_string('status', 'qratt')
    );

    if ($canmanage) {
        $table->head[] = get_string('actions', 'qratt');
    } else {
        $table->head[] = get_string('yourstatus', 'qratt');
    }

    foreach ($meetings as $meeting) {
        $statustext = get_string('inactive', 'qratt');
        $statusclass = 'inactive';
        
        switch ($meeting->status) {
            case QRATT_MEETING_ACTIVE:
                $statustext = get_string('active', 'qratt');
                $statusclass = 'active';
                break;
            case QRATT_MEETING_ENDED:
                $statustext = get_string('ended', 'qratt');
                $statusclass = 'ended';
                break;
        }

        $row = array(
            $meeting->meetingnumber,
            $meeting->topic,
            userdate($meeting->meetingdate),
            html_writer::span($statustext, 'meeting-status ' . $statusclass)
        );

        if ($canmanage) {
            $actions = array();
            if ($meeting->status == QRATT_MEETING_INACTIVE) {
                $actions[] = html_writer::link(
                    new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'activate', 'meetingid' => $meeting->id, 'sesskey' => sesskey())),
                    get_string('activate', 'qratt'),
                    array('class' => 'btn btn-primary btn-sm')
                );
            } else if ($meeting->status == QRATT_MEETING_ACTIVE) {
                $actions[] = html_writer::link(
                    new moodle_url('/mod/qratt/qrcode.php', array('id' => $cm->id, 'meetingid' => $meeting->id)),
                    get_string('showqr', 'qratt'),
                    array('class' => 'btn btn-success btn-sm', 'target' => '_blank')
                );
                $actions[] = html_writer::link(
                    new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'end', 'meetingid' => $meeting->id, 'sesskey' => sesskey())),
                    get_string('endmeeting', 'qratt'),
                    array('class' => 'btn btn-warning btn-sm')
                );
            }
            
            $actions[] = html_writer::link(
                new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'edit', 'meetingid' => $meeting->id)),
                get_string('edit', 'moodle'),
                array('class' => 'btn btn-secondary btn-sm')
            );
            
            $row[] = implode(' ', $actions);
        } else {
            // Show student's attendance status
            $attendance = $DB->get_record('qratt_attendance', array('meetingid' => $meeting->id, 'userid' => $USER->id));
            $userstatus = get_string('absent', 'qratt');
            
            if ($attendance) {
                switch ($attendance->status) {
                    case QRATT_STATUS_PRESENT:
                        $userstatus = get_string('present', 'qratt');
                        break;
                    case QRATT_STATUS_LATE:
                        $userstatus = get_string('late', 'qratt');
                        break;
                    case QRATT_STATUS_EXCUSED:
                        $userstatus = get_string('excused', 'qratt');
                        break;
                }
            }
            
            $row[] = $userstatus;
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Show attendance summary for students
    if (!$canmanage) {
        // Check if there are any active meetings
        $activemeetings = $DB->get_records('qratt_meetings', 
            array('qrattid' => $qratt->id, 'status' => QRATT_MEETING_ACTIVE));
        
        if (!empty($activemeetings)) {
            echo $OUTPUT->notification(get_string('activemeetingfound', 'qratt'), 'notifysuccess');
            echo html_writer::div(
                $OUTPUT->single_button(
                    new moodle_url('/mod/qratt/scanner.php', array('id' => $cm->id)),
                    get_string('scanqrcode', 'qratt'),
                    'get',
                    array('class' => 'btn-lg btn-success')
                ),
                'scan-button text-center mb-4'
            );
        }
        
        echo $OUTPUT->heading(get_string('attendancesummary', 'qratt'), 3);
        $stats = qratt_get_user_statistics($qratt->id, $USER->id);
        
        $summarydata = array();
        $summarydata[] = array(get_string('totalmeetings', 'qratt'), $stats['total']);
        $summarydata[] = array(get_string('present', 'qratt'), $stats['present']);
        $summarydata[] = array(get_string('late', 'qratt'), $stats['late']);
        $summarydata[] = array(get_string('excused', 'qratt'), $stats['excused']);
        $summarydata[] = array(get_string('absent', 'qratt'), $stats['absent']);
        $summarydata[] = array(get_string('attendancepercentage', 'qratt'), $stats['percentage'] . '%');
        
        $summarytable = new html_table();
        $summarytable->data = $summarydata;
        $summarytable->attributes['class'] = 'generaltable attendance-summary';
        
        echo html_writer::table($summarytable);
    }
}

if ($canmanage && $meetings) {
    echo html_writer::div(
        $OUTPUT->single_button(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'add')), 
                             get_string('addmeeting', 'qratt'), 'get'),
        'add-meeting-button'
    );
}

// Finish the page.
echo $OUTPUT->footer();
