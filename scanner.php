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
 * QR Code scanner interface for students
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('qratt', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$qratt = $DB->get_record('qratt', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qratt:takeattendance', $context);

$PAGE->set_url('/mod/qratt/scanner.php', array('id' => $cm->id));
$PAGE->set_title(get_string('scanqr', 'qratt'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('scanqr', 'qratt'), 2);

// Check for active meetings
$activemeetings = $DB->get_records('qratt_meetings', 
    array('qrattid' => $qratt->id, 'status' => QRATT_MEETING_ACTIVE));

if (empty($activemeetings)) {
    echo $OUTPUT->notification(get_string('noactivemeetings', 'qratt'), 'notifymessage');
    echo html_writer::div(
        $OUTPUT->continue_button(new moodle_url('/mod/qratt/view.php', array('id' => $cm->id))),
        'continue-button mt-3'
    );
} else {
    // Show QR scanner interface
    echo html_writer::div(
        html_writer::tag('p', get_string('scannerinfo', 'qratt'), array('class' => 'scanner-info')) .
        html_writer::tag('div', '', array('id' => 'qr-reader', 'class' => 'qr-reader')) .
        html_writer::tag('div', 
            html_writer::tag('p', get_string('scannerresult', 'qratt'), array('id' => 'scan-result', 'class' => 'scan-result')),
            array('class' => 'scan-output')
        ),
        'qr-scanner-container'
    );
    $action_url = new moodle_url('/mod/qratt/scan.php', array('id' => $cm->id));
    
    // Manual entry option
    echo html_writer::div(
        html_writer::tag('h4', get_string('manualentry', 'qratt')) .
        html_writer::tag('p', get_string('manualentryinfo', 'qratt')) .
        html_writer::start_tag('form', array('method' => 'get', 'action' => $action_url)) .
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'meeting', 'value' => '')) .
        html_writer::tag('div',
            html_writer::tag('label', get_string('qrcode', 'qratt') . ' URL:', array('for' => 'qr-url')) .
            html_writer::empty_tag('input', array(
                'type' => 'url', 
                'id' => 'qr-url', 
                'name' => 'qr-url',
                'class' => 'form-control',
                'placeholder' => 'https://...'
            )),
            array('class' => 'form-group')
        ) .
        html_writer::tag('div',
            html_writer::empty_tag('input', array(
                'type' => 'submit', 
                'value' => get_string('submit'),
                'class' => 'btn btn-primary'
            )),
            array('class' => 'form-group')
        ) .
        html_writer::end_tag('form'),
        'manual-entry mt-4'
    );
}

?>

<style>
.qr-scanner-container {
    text-align: center;
    margin: 20px 0;
}

.qr-reader {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    border: 2px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
}

.scanner-info {
    font-size: 16px;
    margin-bottom: 20px;
    color: #666;
}

.scan-result {
    padding: 10px;
    margin-top: 10px;
    border-radius: 5px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.manual-entry {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
}

.form-group {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .qr-reader {
        max-width: 300px;
    }
}
</style>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function onScanSuccess(decodedText, decodedResult) {
    console.log(`Code matched = ${decodedText}`, decodedResult);
    
    // Check if the scanned URL is valid and redirect
    if (decodedText.includes('/mod/qratt/scan.php')) {
        document.getElementById('scan-result').innerHTML = 
            '<span style="color: green;">✓ QR Code detected! Redirecting...</span>';
        
        setTimeout(() => {
            window.location.href = decodedText;
        }, 1000);
    } else {
        document.getElementById('scan-result').innerHTML = 
            '<span style="color: red;">✗ Invalid QR code. Please scan the attendance QR code displayed by your lecturer.</span>';
    }
}

function onScanFailure(error) {
    console.warn(`Code scan error = ${error}`);
}

// Initialize QR scanner when page loads
document.addEventListener('DOMContentLoaded', function() {
    const html5QrCode = new Html5Qrcode("qr-reader");
    
    html5QrCode.start(
        { facingMode: "environment" }, // Use back camera
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        onScanSuccess,
        onScanFailure
    ).catch(err => {
        console.error("Failed to start QR scanner:", err);
        document.getElementById('scan-result').innerHTML = 
            '<span style="color: orange;">⚠ Camera access required. Please allow camera permission and refresh the page.</span>';
    });
});

// Handle manual entry form submission
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const urlString = document.getElementById('qr-url').value;
    
    if (urlString) {
        try {
            const url = new URL(urlString);
            const token = url.searchParams.get("token");
            const meeting = url.searchParams.get("meeting");
            const pathname = url.pathname;

            // Validate the parsed components of the URL
            if (token && meeting && pathname.includes('/mod/qratt/scan.php')) {
                // Reconstruct a clean URL to ensure it is valid
                const redirectUrl = new URL(window.location.origin + pathname);
                redirectUrl.searchParams.append('token', token);
                redirectUrl.searchParams.append('meeting', meeting);
                
                window.location.href = redirectUrl.href;
            } else {
                alert('<?php echo get_string('invalidqrurl', 'qratt'); ?>');
            }
        } catch (error) {
            // This will catch any invalid URLs that cannot be parsed
            alert('<?php echo get_string('invalidqrurl', 'qratt'); ?>');
        }
    }
});
</script>

<?php
echo $OUTPUT->footer();
?>
