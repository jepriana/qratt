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
 * Meeting management for QR Attendance
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

// Display navigation tabs
$tabs = array();
$tabs[] = new tabobject('view', new moodle_url('/mod/qratt/view.php', array('id' => $cm->id)), 
                        get_string('overview', 'qratt'));
$tabs[] = new tabobject('meetings', new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
                        get_string('meetings', 'qratt'));
$tabs[] = new tabobject('reports', new moodle_url('/mod/qratt/reports.php', array('id' => $cm->id)), 
                        get_string('reports', 'qratt'));

echo $OUTPUT->tabtree($tabs, 'meetings');

if ($action == 'add' || $action == 'edit') {
    $meeting = null;
    if ($action == 'edit' && $meetingid) {
        $meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);
    }
    
    $mform = new meeting_form(null, array('qratt' => $qratt, 'meeting' => $meeting));
    
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)));
    } else if ($data = $mform->get_data()) {
        if ($meeting) {
            // Update existing meeting
            $meeting->meetingnumber = (int)$data->meetingnumber;
            $meeting->topic = $data->topic;
            $meeting->meetingdate = $data->meetingdate;
            $meeting->timemodified = time();
            
            $DB->update_record('qratt_meetings', $meeting);
            
            redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
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
            
            redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
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
    
} else {
    // List meetings
    echo $OUTPUT->heading(get_string('meetings', 'qratt'));
    
    $meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');
    
    if (!$meetings) {
        echo $OUTPUT->notification(get_string('nomeetings', 'qratt'), 'notifymessage');
    } else {
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
    }
    
    echo html_writer::div(
        $OUTPUT->single_button(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id, 'action' => 'add')), 
                             get_string('addmeeting', 'qratt'), 'get'),
        'add-meeting-button'
    );
}

echo $OUTPUT->footer();

