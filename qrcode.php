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
 * QR Code display for QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);
$meetingid = required_param('meetingid', PARAM_INT);

$cm = get_coursemodule_from_id('qratt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$qratt = $DB->get_record('qratt', array('id' => $cm->instance), '*', MUST_EXIST);
$meeting = $DB->get_record('qratt_meetings', array('id' => $meetingid, 'qrattid' => $qratt->id), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qratt:manage', $context);

// Check if meeting is active
if ($meeting->status != QRATT_MEETING_ACTIVE) {
    redirect(new moodle_url('/mod/qratt/meetings.php', array('id' => $cm->id)), 
             get_string('error:meetingnotactive', 'qratt'), null, \core\output\notification::NOTIFY_ERROR);
}

$PAGE->set_url('/mod/qratt/qrcode.php', array('id' => $cm->id, 'meetingid' => $meetingid));
$PAGE->set_title(get_string('qrcodefor', 'qratt', $meeting->topic));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('popup');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('qrcodefor', 'qratt', $meeting->topic), 2);

// Check if QR code needs to be refreshed (every 60 seconds)
$now = time();
if (!$meeting->qrcode || !$meeting->qrexpiry || $meeting->qrexpiry <= $now) {
    // Generate new QR code
    $meeting->qrexpiry = $now + 60; // Expires in 60 seconds
    $meeting->qrcode = qratt_generate_qr_code($meeting->id, $meeting->qrexpiry);
    $meeting->timemodified = $now;
    
    $DB->update_record('qratt_meetings', $meeting);
}

// Main container for QR display
echo html_writer::start_div('qr-display-main');

// Display meeting information (collapsible in fullscreen)
echo html_writer::div(
    html_writer::tag('strong', get_string('meetingnumber', 'qratt') . ': ') . $meeting->meetingnumber . html_writer::empty_tag('br') .
    html_writer::tag('strong', get_string('topic', 'qratt') . ': ') . $meeting->topic . html_writer::empty_tag('br') .
    html_writer::tag('strong', get_string('date', 'qratt') . ': ') . userdate($meeting->meetingdate),
    'meeting-info mb-3'
);

// QR Code display area
echo html_writer::start_div('qr-display-area text-center');

// Generate QR code with larger size for better visibility
$qrurl = $meeting->qrcode;
$qrcodeimage = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qrurl);

echo html_writer::div(
    html_writer::img($qrcodeimage, get_string('qrcode', 'qratt'), array(
        'class' => 'qr-code-image',
        'id' => 'main-qr-code'
    )),
    'qr-code-container'
);

echo html_writer::end_div(); // qr-display-area

// Instructions and controls
echo html_writer::div(
    html_writer::tag('p', get_string('qrrefresh', 'qratt'), array('class' => 'text-muted qr-info')) .
    html_writer::tag('p', get_string('scanqr', 'qratt'), array('class' => 'scan-instruction')),
    'qr-instructions text-center'
);

// Display countdown timer
echo html_writer::div(
    html_writer::tag('span', get_string('timeremaining', 'qratt') . ': ', array('class' => 'time-label')) .
    html_writer::tag('span', '', array('id' => 'countdown-timer', 'class' => 'countdown-timer')),
    'countdown-container text-center mb-3'
);

// Control buttons
echo html_writer::div(
    html_writer::tag('button', get_string('fullscreen', 'qratt'), array(
        'id' => 'fullscreen-btn',
        'class' => 'btn btn-success btn-lg mr-2',
        'onclick' => 'toggleFullscreen()'
    )) .
    $OUTPUT->single_button(
        new moodle_url('/mod/qratt/qrcode.php', array('id' => $cm->id, 'meetingid' => $meetingid)),
        get_string('refresh', 'moodle'),
        'get',
        array('class' => 'btn-primary')
    ),
    'control-buttons text-center mb-3'
);

echo html_writer::end_div(); // qr-display-main

// JavaScript for countdown and auto-refresh
?>
<script>
var qrExpiry = <?php echo $meeting->qrexpiry; ?>;
var refreshUrl = '<?php echo $PAGE->url->out(); ?>';

function updateCountdown() {
    var now = Math.floor(Date.now() / 1000);
    var remaining = qrExpiry - now;
    
    if (remaining <= 0) {
        // Auto refresh the page
        window.location.reload();
        return;
    }
    
    var minutes = Math.floor(remaining / 60);
    var seconds = remaining % 60;
    
    document.getElementById('countdown-timer').textContent = 
        String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
}

// Update countdown every second
setInterval(updateCountdown, 1000);
updateCountdown(); // Initial call

// Auto refresh when QR code expires
setTimeout(function() {
    // window.location.reload();  <-  Comment out the refresh. The timer already takes care of the refresh.
}, (qrExpiry - Math.floor(Date.now() / 1000)) * 1000);

// Fullscreen functionality
function toggleFullscreen() {
    var elem = document.documentElement;
    var btn = document.getElementById('fullscreen-btn');

    if (!document.fullscreenElement) {
        // Enter fullscreen
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        }

        // Add fullscreen class and update button
        document.body.classList.add('qr-fullscreen');
        btn.textContent = '<?php echo get_string('exitfullscreen', 'qratt'); ?>';
        btn.classList.remove('btn-success');
        btn.classList.add('btn-warning');

    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }

        // Remove fullscreen class and update button
        document.body.classList.remove('qr-fullscreen');
        btn.textContent = '<?php echo get_string('fullscreen', 'qratt'); ?>';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-success');
    }
}

// Listen for fullscreen change events
document.addEventListener('fullscreenchange', handleFullscreenChange);
document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
document.addEventListener('mozfullscreenchange', handleFullscreenChange);
document.addEventListener('MSFullscreenChange', handleFullscreenChange);

function handleFullscreenChange() {
    var btn = document.getElementById('fullscreen-btn');
    
    if (!document.fullscreenElement && !document.webkitFullscreenElement && 
        !document.mozFullScreenElement && !document.msFullscreenElement) {
        // Exited fullscreen
        document.body.classList.remove('qr-fullscreen');
        if (btn) {
            btn.textContent = '<?php echo get_string('fullscreen', 'qratt'); ?>';
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-success');
        }
    }
}

// Keyboard shortcut for fullscreen (F11 or F key)
document.addEventListener('keydown', function(e) {
    if (e.key === 'f' || e.key === 'F' || e.keyCode === 122) { // F or F11
        e.preventDefault();
        toggleFullscreen();
    }
    if (e.key === 'Escape') {
        // Handle ESC key to exit fullscreen
        if (document.fullscreenElement || document.webkitFullscreenElement || 
            document.mozFullScreenElement || document.msFullscreenElement) {
            toggleFullscreen();
        }
    }
});
</script>

<style>
/* Base styles */
.qr-display-main {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.qr-display-area {
    margin: 30px 0;
}

.qr-code-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    display: inline-block;
    margin: 20px auto;
    border: 3px solid #e9ecef;
    transition: all 0.3s ease;
}

.qr-code-container:hover {
    box-shadow: 0 6px 25px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.qr-code-image {
    border: 2px solid #ddd;
    border-radius: 10px;
    max-width: 400px;
    height: auto;
    display: block;
    margin: 0 auto;
}

.meeting-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    border-radius: 10px;
    border-left: 5px solid #007bff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-align: center;
    max-width: 600px;
    margin: 0 auto 20px auto;
}

.countdown-container {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    border: 2px solid #e9ecef;
}

.countdown-timer {
    font-size: 28px;
    font-weight: bold;
    color: #28a745;
    font-family: 'Courier New', monospace;
}

.time-label {
    font-size: 16px;
    font-weight: 600;
    color: #6c757d;
}

.scan-instruction {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
}

.qr-info {
    font-size: 14px;
    color: #6c757d;
    margin: 5px 0;
}

.control-buttons {
    margin: 20px 0;
}

.control-buttons .btn {
    margin: 5px;
    min-width: 120px;
}

/* Fullscreen styles */
body.qr-fullscreen {
    background: #000;
    color: #fff;
}

body.qr-fullscreen .qr-display-main {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100vh;
    max-width: none;
    padding: 20px;
}

body.qr-fullscreen .qr-display-area {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

body.qr-fullscreen .qr-code-container {
    background: #fff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(255,255,255,0.1);
    border: none;
}

body.qr-fullscreen .qr-code-image {
    max-width: 600px;
    width: 90vmin;
    height: auto;
    border: none;
    border-radius: 15px;
}

body.qr-fullscreen .meeting-info {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border-left-color: #fff;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

body.qr-fullscreen .countdown-container {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

body.qr-fullscreen .countdown-timer {
    color: #28a745;
    font-size: 36px;
}

body.qr-fullscreen .time-label {
    color: #fff;
}

body.qr-fullscreen .scan-instruction {
    color: #fff;
    font-size: 24px;
}

body.qr-fullscreen .qr-info {
    color: rgba(255,255,255,0.8);
}

/* Hide Moodle header/footer in fullscreen */
body.qr-fullscreen #page-header,
body.qr-fullscreen #page-footer,
body.qr-fullscreen .navbar,
body.qr-fullscreen #nav-drawer,
body.qr-fullscreen .breadcrumb {
    display: none !important;
}

body.qr-fullscreen #page-content {
    padding: 0 !important;
    margin: 0 !important;
}

body.qr-fullscreen #region-main {
    padding: 0 !important;
    margin: 0 !important;
}

/* Responsive design */
@media (max-width: 768px) {
    .qr-code-image {
        max-width: 300px;
    }
    
    .countdown-timer {
        font-size: 24px;
    }
    
    .scan-instruction {
        font-size: 18px;
    }
    
    body.qr-fullscreen .qr-code-image {
        max-width: 400px;
        width: 80vmin;
    }
    
    body.qr-fullscreen .countdown-timer {
        font-size: 28px;
    }
    
    body.qr-fullscreen .scan-instruction {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .qr-code-container {
        padding: 15px;
    }
    
    .control-buttons .btn {
        min-width: 100px;
        font-size: 14px;
    }
}

/* Animation for QR code refresh */
@keyframes qr-refresh {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

.qr-code-image.refreshing {
    animation: qr-refresh 0.5s ease-in-out;
}

/* Pulsing effect for active QR */
@keyframes qr-pulse {
    0% { box-shadow: 0 4px 20px rgba(0,123,255,0.1); }
    50% { box-shadow: 0 4px 30px rgba(0,123,255,0.3); }
    100% { box-shadow: 0 4px 20px rgba(0,123,255,0.1); }
}

.qr-code-container {
    animation: qr-pulse 2s ease-in-out infinite;
}
</style>

<?php
echo $OUTPUT->footer();
