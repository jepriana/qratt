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
 * Teacher report for QR Attendance
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
require_capability('mod/qratt:viewreports', $context);

// Get institution settings
$institutionname = get_config('mod_qratt', 'institutionname');
$institutionaddress = get_config('mod_qratt', 'institutionaddress');
$institutioncity = get_config('mod_qratt', 'institutioncity');
$institutionwebsite = get_config('mod_qratt', 'institutionwebsite');
$institutionemail = get_config('mod_qratt', 'institutionemail');
$institutionphone = get_config('mod_qratt', 'institutionphone');
$institutionfax = get_config('mod_qratt', 'institutionfax');

// Get institution logo
$fs = get_file_storage();
$logofiles = $fs->get_area_files(context_system::instance()->id, 'mod_qratt', 'institutionlogo', 0, 'filesize DESC', false);
$logourl = '';
if (!empty($logofiles)) {
    $logofile = reset($logofiles);
    $logourl = moodle_url::make_pluginfile_url(
        $logofile->get_contextid(),
        $logofile->get_component(),
        $logofile->get_filearea(),
        $logofile->get_itemid(),
        $logofile->get_filepath(),
        $logofile->get_filename()
    );
}

// Get report inclusion settings
$includelogoinreports = get_config('mod_qratt', 'includelogoinreports');
$includeaddressinreports = get_config('mod_qratt', 'includeaddressinreports');
$includewebsiteinreports = get_config('mod_qratt', 'includewebsiteinreports');
$includeemailinreports = get_config('mod_qratt', 'includeemailinreports');
$includecityinreports = get_config('mod_qratt', 'includecityinreports');

// Get meetings
$meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC');

// Get attendance counts for each meeting
$meetingdata = array();
foreach ($meetings as $meeting) {
    // Get attendance counts
    $attendancecounts = $DB->get_records_sql("
        SELECT status, COUNT(*) as count 
        FROM {qratt_attendance} 
        WHERE meetingid = ? 
        GROUP BY status", array($meeting->id));
    
    $present = 0;
    $late = 0;
    $absent = 0;
    $excused = 0;
    
    foreach ($attendancecounts as $count) {
        switch ($count->status) {
            case QRATT_STATUS_PRESENT:
                $present = $count->count;
                break;
            case QRATT_STATUS_LATE:
                $late = $count->count;
                break;
            case QRATT_STATUS_ABSENT:
                $absent = $count->count;
                break;
            case QRATT_STATUS_EXCUSED:
                $excused = $count->count;
                break;
        }
    }
    
    $meetingdata[] = array(
        'number' => $meeting->meetingnumber,
        'date' => $meeting->meetingdate,
        'topic' => $meeting->topic,
        'lecturer' => $qratt->lecturer,
        'present' => $present + $late, // Include late as present
        'absent' => $absent
    );
}

// Set content type to HTML with UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo get_string('teacherreport', 'qratt'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .report-header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .logo-section {
            display: table-cell;
            width: 120px;
            vertical-align: top;
        }
        .logo-section img {
            max-width: 100px;
            max-height: 100px;
        }
        .institution-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 20px;
        }
        .institution-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .institution-details {
            font-size: 11px;
            line-height: 1.4;
        }
        .course-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #000;
        }
        .course-info-left, .course-info-right {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .course-info-right {
            border-left: 1px solid #000;
        }
        .info-row {
            margin-bottom: 3px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .meetings-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .meetings-table th, .meetings-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .meetings-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .meetings-table td.topic-column {
            text-align: left;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        .footer-signature {
            margin-top: 60px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Report Header -->
    <div class="report-header">
        <?php if ($includelogoinreports && !empty($logourl)): ?>
        <div class="logo-section">
            <img src="<?php echo $logourl; ?>" alt="<?php echo get_string('institutionlogo', 'qratt'); ?>">
        </div>
        <?php endif; ?>
        <div class="institution-info">
            <?php if (!empty($institutionname)): ?>
            <div class="institution-name"><?php echo htmlspecialchars($institutionname); ?></div>
            <?php endif; ?>
            <div class="institution-details">
                <?php if ($includeaddressinreports && !empty($institutionaddress)): ?>
                    <?php echo nl2br(htmlspecialchars($institutionaddress)); ?><br>
                <?php endif; ?>
                <?php if ($includewebsiteinreports && !empty($institutionwebsite)): ?>
                    Website: <?php echo htmlspecialchars($institutionwebsite); ?><br>
                <?php endif; ?>
                <?php if ($includeemailinreports && !empty($institutionemail)): ?>
                    Email: <?php echo htmlspecialchars($institutionemail); ?><br>
                <?php endif; ?>
                <?php if (!empty($institutionphone)): ?>
                    Phone: <?php echo htmlspecialchars($institutionphone); ?><br>
                <?php endif; ?>
                <?php if (!empty($institutionfax)): ?>
                    Fax: <?php echo htmlspecialchars($institutionfax); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Course Information -->
    <div class="course-info">
        <div class="course-info-left">
            <?php if (!empty($qratt->semester)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('semester', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->semester); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->department)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('department', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->department); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->studyprogram)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('studyprogram', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->studyprogram); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->subject)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('subject', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->subject); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->credits)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('credits', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->credits); ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="course-info-right">
            <?php if (!empty($qratt->classname)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('classname', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->classname); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->lecturer)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('lecturer', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->lecturer); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->dayofweek)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('dayofweek', 'qratt'); ?>:</span>
                <?php echo get_string($qratt->dayofweek, 'qratt'); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->scheduletime)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('scheduletime', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->scheduletime); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->room)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('room', 'qratt'); ?>:</span>
                <?php echo htmlspecialchars($qratt->room); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Meeting Data Table -->
    <table class="meetings-table">
        <thead>
            <tr>
                <th><?php echo get_string('meetingnumber', 'qratt'); ?></th>
                <th><?php echo get_string('date', 'qratt'); ?></th>
                <th><?php echo get_string('lecturer', 'qratt'); ?></th>
                <th><?php echo get_string('topic', 'qratt'); ?></th>
                <th><?php echo get_string('numberpresent', 'qratt'); ?></th>
                <th><?php echo get_string('numberabsent', 'qratt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($meetingdata)): ?>
                <?php foreach ($meetingdata as $data): ?>
                    <tr>
                        <td><?php echo $data['number']; ?></td>
                        <td><?php echo userdate($data['date'], get_string('strftimedatefullshort')); ?></td>
                        <td><?php echo htmlspecialchars($data['lecturer'] ?: '-'); ?></td>
                        <td class="topic-column"><?php echo htmlspecialchars($data['topic']); ?></td>
                        <td><?php echo $data['present']; ?></td>
                        <td><?php echo $data['absent']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6"><?php echo get_string('nomeetings', 'qratt'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <?php if ($includecityinreports && !empty($institutioncity)): ?>
            <?php echo htmlspecialchars($institutioncity) . ', ' . userdate(time(), get_string('strftimedatefullshort')); ?>
        <?php else: ?>
            <?php echo userdate(time(), get_string('strftimedatefullshort')); ?>
        <?php endif; ?>
        <div class="footer-signature">
            <?php echo get_string('lecturer_in_charge', 'qratt'); ?><br><br><br>
            <?php if (!empty($qratt->lecturer)): ?>
                <?php echo htmlspecialchars($qratt->lecturer); ?>
            <?php else: ?>
                _______________________
            <?php endif; ?>
        </div>
    </div>

    <!-- Print button for non-print view -->
    <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
        <button onclick="window.print();" style="padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <?php echo get_string('print', 'qratt'); ?>
        </button>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>