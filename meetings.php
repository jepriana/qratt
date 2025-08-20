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
 * Meeting management and overview for QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/formslib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$meetingid = optional_param('meetingid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('qratt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$qratt = $DB->get_record('qratt', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qratt:manage', $context);

$PAGE->set_url('/mod/qratt/meetings.php', array('id' => $cm->id));
$PAGE->set_title(format_string($qratt->name));
$PAGE->set_heading(format_string($course->fullname));

// Handle actions
if ($action == 'activate' && $meetingid) {
    require_sesskey();
    $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    
    if ($meeting->status == QRATT_MEETING_INACTIVE) {
        // Deactivate any currently active meetings
        $DB->set_field('qratt_meetings', 'status', QRATT_MEETING_ENDED, 
                      array('qrattid' => $qratt->id, 'status' => QRATT_MEETING_ACTIVE));
        
        // Activate this meeting
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->starttime = time();
        $meeting->qrexpiry = time() + 60; // Expires in 60 seconds
        $meeting->qrcode = qratt_generate_qr_code($meeting->id, $meeting->qrexpiry);
        $meeting->timemodified = time();
        
        $DB->update_record('qratt_meetings', $meeting);
        
        redirect($PAGE->url, get_string('meetingactivated', 'qratt'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

if ($action == 'end' && $meetingid) {
    require_sesskey();
    $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    
    if ($meeting->status == QRATT_MEETING_ACTIVE) {
        $meeting->status = QRATT_MEETING_ENDED;
        $meeting->endtime = time();
        $meeting->timemodified = time();
        
        $DB->update_record('qratt_meetings', $meeting);
        
        redirect($PAGE->url, get_string('meetingended', 'qratt'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

if ($action == 'delete' && $meetingid) {
    require_sesskey();
    $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    
    // Delete attendance records first
    $DB->delete_records('qratt_attendance', array('meetingid' => $meeting->id));
    
    // Delete the meeting
    $DB->delete_records('qratt_meetings', array('id' => $meeting->id));
    
    redirect($PAGE->url, get_string('meetingdeleted', 'qratt'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Handle manual attendance save
if ($action === 'saveattendance' && $meetingid && data_submitted() && confirm_sesskey()) {
    require_capability('mod/qratt:manageattendances', $context);
    $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    
    $attendancedata = optional_param_array('attendance', array(), PARAM_INT);
    $saved = 0;
    $updated = 0;
    
    foreach ($attendancedata as $userid => $statusid) {
        // Skip if no status selected (value 0)
        if ($statusid == 0) {
            continue;
        }
        
        // Check if attendance record already exists
        $existing = $DB->get_record('qratt_attendance', array('meetingid' => $meetingid, 'userid' => $userid));
        
        if ($existing) {
            // Update existing record
            $existing->status = $statusid;
            $existing->timemodified = time();
            $existing->scantime = ($statusid == QRATT_STATUS_PRESENT || $statusid == QRATT_STATUS_LATE) ? time() : null;
            $DB->update_record('qratt_attendance', $existing);
            $updated++;
        } else {
            // Create new record
            $newrecord = new stdClass();
            $newrecord->meetingid = $meetingid;
            $newrecord->userid = $userid;
            $newrecord->status = $statusid;
            $newrecord->scantime = ($statusid == QRATT_STATUS_PRESENT || $statusid == QRATT_STATUS_LATE) ? time() : null;
            $newrecord->timecreated = time();
            $newrecord->timemodified = time();
            $DB->insert_record('qratt_attendance', $newrecord);
            $saved++;
        }
    }
    
    // Redirect with success message
    $message = get_string('attendanceupdated', 'qratt', array('saved' => $saved, 'updated' => $updated));
    redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'attendance', 'meetingid' => $meetingid)), 
             $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

/**
 * Meeting form class
 */
class meeting_form extends moodleform {
    
    protected function definition() {
        $mform = $this->_form;
        $qratt = $this->_customdata['qratt'];
        $meeting = $this->_customdata['meeting'];
        
        $mform->addElement('header', 'general', get_string('meetingform', 'qratt'));
        
        $options = array();
        for ($i = 1; $i <= 16; $i++) {
            $options[$i] = $i;
        }

        $mform->addElement('select', 'meetingnumber', get_string('meetingnumber', 'qratt'), $options);
        $mform->addRule('meetingnumber', null, 'required', null, 'client');
        $mform->addRule('meetingnumber', null, 'numeric', null, 'client');
        
        $mform->addElement('text', 'topic', get_string('meetingtopic', 'qratt'), array('size' => 60));
        $mform->setType('topic', PARAM_TEXT);
        $mform->addRule('topic', null, 'required', null, 'client');
        
        $mform->addElement('date_time_selector', 'meetingdate', get_string('meetingdate', 'qratt'));
        
        $mform->addElement('duration', 'activeduration', get_string('activeduration', 'qratt'), 
                          array('defaultunit' => MINSECS, 'optional' => false));
        $mform->addHelpButton('activeduration', 'activeduration', 'qratt');
        $mform->setDefault('activeduration', 1800); // 30 minutes default
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        
        if ($meeting) {
            $mform->addElement('hidden', 'meetingid');
            $mform->setType('meetingid', PARAM_INT);
        }
        
        $this->add_action_buttons(true, $meeting ? get_string('savechanges') : get_string('addmeeting', 'qratt'));
    }
    
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        
        $qratt = $this->_customdata['qratt'];
        $meeting = $this->_customdata['meeting'];
        
        // Check if meeting number already exists (for new meetings or when changing meeting number)
        $conditions = array('qrattid' => $qratt->id, 'meetingnumber' => $data['meetingnumber']);
        if ($meeting) {
            $conditions['id'] = array('!=', $meeting->id);  // Exclude current meeting when editing
        }
        
        if ($DB->record_exists('qratt_meetings', $conditions)) {
            $errors['meetingnumber'] = get_string('meetingnumberexists', 'qratt');
        }
        
        return $errors;
    }
}

// Output starts here
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
// if ($qratt->intro) {
//     echo $OUTPUT->box(format_module_intro('qratt', $qratt, $cm->id), 'generalbox mod_introbox', 'qrattintro');
// }

// Display navigation tabs
$tabs = array();
$tabs[] = new tabobject('meetings', new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
                        get_string('meetings', 'qratt'));
$tabs[] = new tabobject('reports', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id)), 
                        get_string('reports', 'qratt'));

echo $OUTPUT->tabtree($tabs, 'meetings');

// No sub-tabs needed - unified meetings view

// Handle different actions
if ($action == 'add' || $action == 'edit') {
    $meeting = null;
    if ($action == 'edit' && $meetingid) {
        $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    }
    
    $mform = new meeting_form(null, array('qratt' => $qratt, 'meeting' => $meeting));
    
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'manage')));
    } else if ($data = $mform->get_data()) {
        if ($meeting) {
            // Update existing meeting
            $meeting->meetingnumber = (int)$data->meetingnumber;
            $meeting->topic = $data->topic;
            $meeting->meetingdate = $data->meetingdate;
            $meeting->timemodified = time();
            
            $DB->update_record('qratt_meetings', $meeting);
            
            redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'manage')), 
                    get_string('meetingupdated', 'qratt'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Create new meeting
            $newmeeting = new stdClass();
            $newmeeting->qrattid = $qratt->id;
            $newmeeting->meetingnumber = (int)$data->meetingnumber;
            $newmeeting->topic = $data->topic;
            $newmeeting->meetingdate = $data->meetingdate;
            $newmeeting->status = QRATT_MEETING_INACTIVE;
            $newmeeting->timecreated = time();
            $newmeeting->timemodified = time();
            
            $DB->insert_record('qratt_meetings', $newmeeting);
            
            redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'manage')), 
                    get_string('meetingcreated', 'qratt'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
    
    echo $OUTPUT->heading($action == 'add' ? get_string('addmeeting', 'qratt') : get_string('editmeeting', 'qratt'));
    
    if ($meeting) {
        $mform->set_data(array(
            'id' => $cm->id,
            'action' => 'edit',
            'meetingid' => $meeting->id,
            'meetingnumber' => $meeting->meetingnumber,
            'topic' => $meeting->topic,
            'meetingdate' => $meeting->meetingdate
        ));
    } else {
        $mform->set_data(array('id' => $cm->id, 'action' => 'add'));
    }
    
    $mform->display();

} else if ($action == 'attendance' && $meetingid) {
    // Manual attendance input
    require_capability('mod/qratt:manageattendances', $context);
    $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    
    echo $OUTPUT->heading(get_string('manualattendancefor', 'qratt', $meeting->topic), 2);
    
    // Meeting information
    echo html_writer::div(
        html_writer::tag('strong', get_string('meetingnumber', 'qratt') . ': ') . $meeting->meetingnumber . html_writer::empty_tag('br') .
        html_writer::tag('strong', get_string('topic', 'qratt') . ': ') . $meeting->topic . html_writer::empty_tag('br') .
        html_writer::tag('strong', get_string('date', 'qratt') . ': ') . userdate($meeting->meetingdate),
        'meeting-info mb-3 p-3 bg-light border-left-primary'
    );
    
    // Get students enrolled in the course (only users with student role)
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    if (!$studentrole) {
        echo $OUTPUT->notification(get_string('error:rolenotfound', 'qratt'), 'notifyproblem');
        echo $OUTPUT->footer();
        exit;
    }
    
    // Get users enrolled with student role only
    $students = get_enrolled_users($context, 'mod/qratt:canbelisted', 0, 
                                 'u.id, u.firstname, u.lastname, u.email', 
                                 'u.lastname, u.firstname', 0, '', '', '', 0, $studentrole->id);
    
    if (!$students) {
        echo $OUTPUT->notification(get_string('nostudents', 'qratt'), 'notifymessage');
    } else {
        // Get current attendance records for this meeting
        $attendances = $DB->get_records('qratt_attendance', array('meetingid' => $meetingid), '', 'userid, status, scantime');
        
        echo html_writer::start_tag('form', array(
            'method' => 'post',
            'action' => new moodle_url('/mod/qratt/meetings.php'),
            'class' => 'attendance-form'
        ));
        
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ));
        
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'id',
            'value' => $cm->id
        ));
        
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'meetingid',
            'value' => $meetingid
        ));
        
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'saveattendance'
        ));
        
        // Bulk selection section
        echo html_writer::div(
            html_writer::tag('h4', get_string('bulkselection', 'qratt'), array('class' => 'mb-3')) .
            html_writer::tag('p', get_string('bulkselectionhelp', 'qratt'), array('class' => 'text-muted mb-3')) .
            html_writer::div(
                html_writer::tag('label', 
                    html_writer::empty_tag('input', array(
                        'type' => 'radio',
                        'name' => 'bulk_status',
                        'value' => '0',
                        'class' => 'mr-1 bulk-radio',
                        'data-status' => '0'
                    )) . get_string('notset', 'qratt'),
                    array('class' => 'radio-option mr-3')
                ) .
                html_writer::tag('label', 
                    html_writer::empty_tag('input', array(
                        'type' => 'radio',
                        'name' => 'bulk_status',
                        'value' => QRATT_STATUS_PRESENT,
                        'class' => 'mr-1 bulk-radio',
                        'data-status' => QRATT_STATUS_PRESENT
                    )) . get_string('present', 'qratt'),
                    array('class' => 'radio-option mr-3 text-success')
                ) .
                html_writer::tag('label', 
                    html_writer::empty_tag('input', array(
                        'type' => 'radio',
                        'name' => 'bulk_status',
                        'value' => QRATT_STATUS_LATE,
                        'class' => 'mr-1 bulk-radio',
                        'data-status' => QRATT_STATUS_LATE
                    )) . get_string('late', 'qratt'),
                    array('class' => 'radio-option mr-3 text-warning')
                ) .
                html_writer::tag('label', 
                    html_writer::empty_tag('input', array(
                        'type' => 'radio',
                        'name' => 'bulk_status',
                        'value' => QRATT_STATUS_ABSENT,
                        'class' => 'mr-1 bulk-radio',
                        'data-status' => QRATT_STATUS_ABSENT
                    )) . get_string('absent', 'qratt'),
                    array('class' => 'radio-option mr-3 text-danger')
                ) .
                html_writer::tag('label', 
                    html_writer::empty_tag('input', array(
                        'type' => 'radio',
                        'name' => 'bulk_status',
                        'value' => QRATT_STATUS_EXCUSED,
                        'class' => 'mr-1 bulk-radio',
                        'data-status' => QRATT_STATUS_EXCUSED
                    )) . get_string('excused', 'qratt'),
                    array('class' => 'radio-option mr-3 text-info')
                ),
                'bulk-options'
            ),
            'bulk-selection p-3 mb-4 bg-light border rounded'
        );
        
        // Attendance input table
        $table = new html_table();
        $table->head = array(
            get_string('student', 'qratt'),
            get_string('email'),
            get_string('currentstatus', 'qratt'),
            get_string('setattendance', 'qratt')
        );
        $table->attributes['class'] = 'attendance-table generaltable';
        
        foreach ($students as $student) {
            $currentstatus = isset($attendances[$student->id]) ? $attendances[$student->id]->status : null;
            $scantime = isset($attendances[$student->id]) ? $attendances[$student->id]->scantime : null;
            
            // Current status display
            $currentstatustext = get_string('notset', 'qratt');
            $currentstatusclass = 'text-muted';
            
            if ($currentstatus !== null) {
                switch ($currentstatus) {
                    case QRATT_STATUS_PRESENT:
                        $currentstatustext = get_string('present', 'qratt');
                        $currentstatusclass = 'text-success';
                        break;
                    case QRATT_STATUS_LATE:
                        $currentstatustext = get_string('late', 'qratt');
                        $currentstatusclass = 'text-warning';
                        break;
                    case QRATT_STATUS_EXCUSED:
                        $currentstatustext = get_string('excused', 'qratt');
                        $currentstatusclass = 'text-info';
                        break;
                    case QRATT_STATUS_ABSENT:
                        $currentstatustext = get_string('absent', 'qratt');
                        $currentstatusclass = 'text-danger';
                        break;
                }
                
                if ($scantime) {
                    $currentstatustext .= html_writer::tag('small', 
                        html_writer::empty_tag('br') . get_string('scantime', 'qratt') . ': ' . userdate($scantime, get_string('strftimetime')),
                        array('class' => 'text-muted')
                    );
                }
            }
            
            // Radio buttons for attendance status
            $radiooptions = '';
            
            // None/Not Set option
            $checked = ($currentstatus === null) ? 'checked' : '';
            $radiooptions .= html_writer::tag('label', 
                html_writer::empty_tag('input', array(
                    'type' => 'radio',
                    'name' => 'attendance[' . $student->id . ']',
                    'value' => '0',
                    'class' => 'mr-1',
                    $checked => $checked
                )) . get_string('notset', 'qratt'),
                array('class' => 'radio-option mr-3')
            );
            
            // Present option
            $checked = ($currentstatus == QRATT_STATUS_PRESENT) ? 'checked' : '';
            $radiooptions .= html_writer::tag('label', 
                html_writer::empty_tag('input', array(
                    'type' => 'radio',
                    'name' => 'attendance[' . $student->id . ']',
                    'value' => QRATT_STATUS_PRESENT,
                    'class' => 'mr-1',
                    $checked => $checked
                )) . get_string('present', 'qratt'),
                array('class' => 'radio-option mr-3 text-success')
            );
            
            // Late option
            $checked = ($currentstatus == QRATT_STATUS_LATE) ? 'checked' : '';
            $radiooptions .= html_writer::tag('label', 
                html_writer::empty_tag('input', array(
                    'type' => 'radio',
                    'name' => 'attendance[' . $student->id . ']',
                    'value' => QRATT_STATUS_LATE,
                    'class' => 'mr-1',
                    $checked => $checked
                )) . get_string('late', 'qratt'),
                array('class' => 'radio-option mr-3 text-warning')
            );
            
            // Absent option
            $checked = ($currentstatus == QRATT_STATUS_ABSENT) ? 'checked' : '';
            $radiooptions .= html_writer::tag('label', 
                html_writer::empty_tag('input', array(
                    'type' => 'radio',
                    'name' => 'attendance[' . $student->id . ']',
                    'value' => QRATT_STATUS_ABSENT,
                    'class' => 'mr-1',
                    $checked => $checked
                )) . get_string('absent', 'qratt'),
                array('class' => 'radio-option mr-3 text-danger')
            );
            
            // Excused option
            $checked = ($currentstatus == QRATT_STATUS_EXCUSED) ? 'checked' : '';
            $radiooptions .= html_writer::tag('label', 
                html_writer::empty_tag('input', array(
                    'type' => 'radio',
                    'name' => 'attendance[' . $student->id . ']',
                    'value' => QRATT_STATUS_EXCUSED,
                    'class' => 'mr-1',
                    $checked => $checked
                )) . get_string('excused', 'qratt'),
                array('class' => 'radio-option mr-3 text-info')
            );
            
            $table->data[] = array(
                $student->firstname . ' ' . $student->lastname,
                $student->email,
                html_writer::span($currentstatustext, $currentstatusclass),
                $radiooptions
            );
        }
        
        echo html_writer::table($table);
        
        // Form buttons
        echo html_writer::div(
            html_writer::empty_tag('input', array(
                'type' => 'submit',
                'value' => get_string('saveattendance', 'qratt'),
                'class' => 'btn btn-primary btn-lg mr-2'
            )) .
            html_writer::link(
                new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)),
                get_string('back'),
                array('class' => 'btn btn-secondary btn-lg')
            ),
            'form-buttons mt-4 text-center'
        );
        
        echo html_writer::end_tag('form');
        
        // Attendance summary for this meeting
        if (!empty($attendances)) {
            echo $OUTPUT->heading(get_string('attendancesummary', 'qratt'), 3, 'mt-4');
            
            $present = $late = $excused = $absent = 0;
            foreach ($attendances as $att) {
                switch ($att->status) {
                    case QRATT_STATUS_PRESENT:
                        $present++;
                        break;
                    case QRATT_STATUS_LATE:
                        $late++;
                        break;
                    case QRATT_STATUS_EXCUSED:
                        $excused++;
                        break;
                    case QRATT_STATUS_ABSENT:
                        $absent++;
                        break;
                }
            }
            
            $totalstudents = count($students);
            $totalset = count($attendances);
            $notset = $totalstudents - $totalset;
            
            echo html_writer::div(
                html_writer::tag('div',
                    html_writer::tag('span', get_string('present', 'qratt') . ': ', array('class' => 'font-weight-bold')) .
                    html_writer::tag('span', $present, array('class' => 'badge badge-success mr-3')) .
                    html_writer::tag('span', get_string('late', 'qratt') . ': ', array('class' => 'font-weight-bold')) .
                    html_writer::tag('span', $late, array('class' => 'badge badge-warning mr-3')) .
                    html_writer::tag('span', get_string('excused', 'qratt') . ': ', array('class' => 'font-weight-bold')) .
                    html_writer::tag('span', $excused, array('class' => 'badge badge-info mr-3')) .
                    html_writer::tag('span', get_string('absent', 'qratt') . ': ', array('class' => 'font-weight-bold')) .
                    html_writer::tag('span', $absent, array('class' => 'badge badge-danger mr-3')) .
                    html_writer::tag('span', get_string('notset', 'qratt') . ': ', array('class' => 'font-weight-bold')) .
                    html_writer::tag('span', $notset, array('class' => 'badge badge-secondary'))
                ),
                'attendance-summary p-3 bg-light border rounded'
            );
        }
    }
    
    ?>
    <style>
    .attendance-form .radio-option {
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 5px;
        white-space: nowrap;
    }
    
    .attendance-form .radio-option input[type="radio"] {
        margin-right: 5px;
    }
    
    .attendance-table td {
        vertical-align: middle;
    }
    
    .meeting-info {
        border-left: 4px solid #007bff !important;
    }
    
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    
    .attendance-summary {
        font-size: 1.1em;
    }
    
    .form-buttons {
        border-top: 1px solid #dee2e6;
        padding-top: 20px;
    }
    
    .bulk-selection {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    
    .bulk-selection h4 {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .attendance-form .radio-option {
            display: block;
            margin-right: 0;
            margin-bottom: 8px;
        }
        
        .attendance-table {
            font-size: 0.9em;
        }
        
        .form-buttons .btn {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle bulk selection radio buttons
        const bulkRadios = document.querySelectorAll('.bulk-radio');
        
        bulkRadios.forEach(function(bulkRadio) {
            bulkRadio.addEventListener('change', function() {
                if (this.checked) {
                    const selectedStatus = this.getAttribute('data-status');
                    
                    // Find all student attendance radio buttons and set them to the selected status
                    const attendanceInputs = document.querySelectorAll('input[name^="attendance["]');
                    
                    attendanceInputs.forEach(function(input) {
                        if (input.value === selectedStatus) {
                            input.checked = true;
                        }
                    });
                }
            });
        });
    });
    </script>
    <?php

} else {
    // Unified meetings view
    echo $OUTPUT->heading(get_string('meetings', 'qratt'), 2);
    
    // Get meetings for this QR Attendance instance
    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');
    
    if (!$meetings) {
        echo $OUTPUT->notification(get_string('nomeetings', 'qratt'), 'notifymessage');
        echo html_writer::tag('p', get_string('nomeetingsinfo', 'qratt'));
        echo $OUTPUT->single_button(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'add')), 
                                   get_string('addmeeting', 'qratt'), 'get');
    } else {
        // Display meetings table with all actions
        $table = new html_table();
        $table->head = array(
            get_string('meetingnumber', 'qratt'),
            get_string('topic', 'qratt'),
            get_string('date', 'qratt'),
            get_string('status', 'qratt'),
            get_string('actions', 'qratt')
        );
        
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
            
            $actions = array();
            
            if ($meeting->status == QRATT_MEETING_INACTIVE) {
                $actions[] = html_writer::link(
                    new moodle_url('/mod/qratt/meetings.php', 
                        array('id' => $cm->id, 'action' => 'activate', 'meetingid' => $meeting->id, 'sesskey' => sesskey())),
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
                    new moodle_url('/mod/qratt/meetings.php', 
                        array('id' => $cm->id, 'action' => 'end', 'meetingid' => $meeting->id, 'sesskey' => sesskey())),
                    get_string('endmeeting', 'qratt'),
                    array('class' => 'btn btn-warning btn-sm')
                );
            }
            
            // View attendance action - always available if user has capability
            if (has_capability('mod/qratt:manageattendances', $context)) {
                $actions[] = html_writer::link(
                    new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'attendance', 'meetingid' => $meeting->id)),
                    get_string('view'),
                    array('class' => 'btn btn-info btn-sm')
                );
            }
            
            $actions[] = html_writer::link(
                new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'edit', 'meetingid' => $meeting->id)),
                get_string('edit', 'moodle'),
                array('class' => 'btn btn-secondary btn-sm')
            );

            $actions[] = html_writer::link(
                new moodle_url('/mod/qratt/meetings.php', 
                    array('id' => $cm->id, 'action' => 'delete', 'meetingid' => $meeting->id, 'sesskey' => sesskey())),
                get_string('delete', 'moodle'),
                array('class' => 'btn btn-danger btn-sm', 'onclick' => 'return confirm("' . get_string('confirmdeletion', 'qratt') . '");')
            );
            
            $table->data[] = array(
                $meeting->meetingnumber,
                $meeting->topic,
                userdate($meeting->meetingdate),
                html_writer::span($statustext, 'meeting-status ' . $statusclass),
                implode(' ', $actions)
            );
        }
        
        echo html_writer::table($table);
        
        // Add meeting button
        echo html_writer::div(
            $OUTPUT->single_button(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'add')), 
                                 get_string('addmeeting', 'qratt'), 'get'),
            'add-meeting-button mt-3'
        );
    }
}

echo $OUTPUT->footer();
?>
