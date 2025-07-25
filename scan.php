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
 * QR Code scanning endpoint for QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$token = required_param('token', PARAM_RAW);
$meetingid = required_param('meeting', PARAM_INT);

require_login();

// Get meeting and validate
$meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid), '*', MUST_EXIST);
$qratt = $DB->get_record('qratt', array('id' => $meeting->qrattid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $qratt->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id, false, MUST_EXIST);

// Check if user is enrolled in the course
$context = context_course::instance($course->id);
if (!is_enrolled($context, $USER->id)) {
    print_error('notenrolled', 'error', '', $course->fullname);
}

$modulecontext = context_module::instance($cm->id);
require_capability('mod/qratt:takeattendance', $modulecontext);

$PAGE->set_url('/mod/qratt/scan.php', array('token' => $token, 'meeting' => $meetingid));
$PAGE->set_title(get_string('scanqr', 'qratt'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$currenttime = time();

// Check if meeting is active
if ($meeting->status != QRATT_MEETING_ACTIVE) {
    $PAGE->set_title(get_string('error:meetingnotactive', 'qratt'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:meetingnotactive', 'qratt'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

// Check if QR code has expired
if ($meeting->qrexpiry <= $currenttime) {
    $PAGE->set_title(get_string('qrexpired', 'qratt'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('qrexpired', 'qratt'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

// Validate token (simple validation - in production, use more secure method)
$validtoken = false;
// Use the same fallback salt as in the generation function
$salt = isset($CFG->passwordsaltmain) ? $CFG->passwordsaltmain : 'qratt_default_salt';

// Check current token and previous few tokens to account for refresh timing
for ($i = 0; $i <= 2; $i++) {
    $checktimestamp = $meeting->qrexpiry - ($i * 60);
    $checktoken = md5($meetingid . $checktimestamp . $salt);
    if (hash_equals($token, $checktoken)) {
        $validtoken = true;
        break;
    }
}

if (!$validtoken) {
    $PAGE->set_title(get_string('qrinvalid', 'qratt'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('qrinvalid', 'qratt'), 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

// Check if user already marked attendance for this meeting
$existingattendance = $DB->get_record('qratt_attendance', 
    array('meetingid' => $meetingid, 'userid' => $USER->id));

if ($existingattendance) {
    $PAGE->set_title(get_string('alreadymarked', 'qratt'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('alreadymarked', 'qratt'), 'notifywarning');
    
    // Show current status
    $statustext = get_string('present', 'qratt');
    switch ($existingattendance->status) {
        case QRATT_STATUS_PRESENT:
            $statustext = get_string('present', 'qratt');
            break;
        case QRATT_STATUS_LATE:
            $statustext = get_string('late', 'qratt');
            break;
        case QRATT_STATUS_EXCUSED:
            $statustext = get_string('excused', 'qratt');
            break;
        case QRATT_STATUS_ABSENT:
            $statustext = get_string('absent', 'qratt');
            break;
    }
    
    echo html_writer::div(
        html_writer::tag('p', get_string('yourcurrentstatus', 'qratt') . ': ' . html_writer::tag('strong', $statustext)),
        'current-status'
    );
    
    echo $OUTPUT->continue_button(new moodle_url('/mod/qratt/view.php', array('id' => $cm->id)));
    echo $OUTPUT->footer();
    exit;
}

// Determine attendance status based on timing
$attendancestatus = QRATT_STATUS_PRESENT;
$meetingstart = $meeting->starttime ? $meeting->starttime : $meeting->meetingdate;
$latethreshold = $meetingstart + 900; // 15 minutes late threshold

if ($currenttime > $latethreshold) {
    $attendancestatus = QRATT_STATUS_LATE;
}

// Mark attendance
$attendancerecord = new stdClass();
$attendancerecord->meetingid = $meetingid;
$attendancerecord->userid = $USER->id;
$attendancerecord->status = $attendancestatus;
$attendancerecord->scantime = $currenttime;
$attendancerecord->timecreated = $currenttime;
$attendancerecord->timemodified = $currenttime;

$DB->insert_record('qratt_attendance', $attendancerecord);

// Success page
$PAGE->set_title(get_string('attendancemarked', 'qratt'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('attendancemarked', 'qratt'), 2);

$statustext = ($attendancestatus == QRATT_STATUS_PRESENT) ? 
    get_string('present', 'qratt') : get_string('late', 'qratt');

echo html_writer::div(
    html_writer::tag('div', 
        html_writer::tag('i', '', array('class' => 'fa fa-check-circle', 'style' => 'font-size: 48px; color: #28a745;')) .
        html_writer::tag('h3', get_string('attendancemarked', 'qratt'), array('class' => 'mt-3')) .
        html_writer::tag('p', get_string('yourstatus', 'qratt') . ': ' . html_writer::tag('strong', $statustext), array('class' => 'lead')),
        array('class' => 'text-center')
    ),
    'attendance-success p-4'
);

// Show meeting details
echo html_writer::div(
    html_writer::tag('h4', get_string('meetingdetails', 'qratt')) .
    html_writer::tag('p', html_writer::tag('strong', get_string('meetingnumber', 'qratt') . ': ') . $meeting->meetingnumber) .
    html_writer::tag('p', html_writer::tag('strong', get_string('topic', 'qratt') . ': ') . $meeting->topic) .
    html_writer::tag('p', html_writer::tag('strong', get_string('date', 'qratt') . ': ') . userdate($meeting->meetingdate)) .
    html_writer::tag('p', html_writer::tag('strong', get_string('scantime', 'qratt') . ': ') . userdate($currenttime)),
    'meeting-details mt-4 p-3 bg-light'
);

echo html_writer::div(
    $OUTPUT->single_button(new moodle_url('/mod/qratt/view.php', array('id' => $cm->id)), 
                          get_string('continueto', 'qratt', $qratt->name), 'get'),
    'continue-button mt-3 text-center'
);

?>
<style>
.attendance-success {
    background: #f8f9fa;
    border: 2px solid #28a745;
    border-radius: 10px;
    margin: 20px 0;
}

.meeting-details {
    border-left: 4px solid #007bff;
    border-radius: 5px;
}

.fa-check-circle {
    animation: bounceIn 0.6s ease-in-out;
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .attendance-success {
        margin: 10px;
        padding: 20px;
    }
}
</style>

<?php
echo $OUTPUT->footer();
