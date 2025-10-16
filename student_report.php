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
 * Student attendance report for QR Attendance
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
$includephoneinreports = get_config('mod_qratt', 'includephoneinreports');
$includefaxinreports = get_config('mod_qratt', 'includefaxinreports');

// Get students enrolled with student role only
$studentrole = $DB->get_record('role', array('shortname' => 'student'));
if (!$studentrole) {
    echo $OUTPUT->notification(get_string('error:rolenotfound', 'qratt'), 'notifyproblem');
    exit;
}

// Get only users with student role, including username for NIM
$students = get_enrolled_users($context, 'mod/qratt:canbelisted', 0, 
                             'u.id, u.username, u.idnumber, u.firstname, u.lastname', 
                             'u.lastname, u.firstname', 0, '', '', '', 0, $studentrole->id);

// Get meetings (up to 16 meetings)
$meetings = $DB->get_records('qratt_meetings', array('qrattid' => $qratt->id), 'meetingnumber ASC', 'id, meetingnumber, topic, meetingdate', 0, 16);

// Get all attendance records for this activity (students only)
$attendancerecords = array();
if (!empty($meetings) && !empty($students)) {
    $meetingids = array_keys($meetings);
    $studentids = array_keys($students);
    
    if (!empty($meetingids) && !empty($studentids)) {
        list($meetinginsql, $meetingparams) = $DB->get_in_or_equal($meetingids);
        list($studentinsql, $studentparams) = $DB->get_in_or_equal($studentids);
        $params = array_merge($meetingparams, $studentparams);
        
        $attendances = $DB->get_records_sql("SELECT a.*, m.meetingnumber 
                                            FROM {qratt_attendance} a 
                                            JOIN {qratt_meetings} m ON a.meetingid = m.id 
                                            WHERE a.meetingid $meetinginsql 
                                            AND a.userid $studentinsql", $params);
        
        foreach ($attendances as $attendance) {
            $attendancerecords[$attendance->userid][$attendance->meetingnumber] = $attendance->status;
        }
    }
}

// Set content type to HTML with UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo get_string('studentreport', 'qratt'); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }
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
            border: 0px solid #000;
        }
        .course-info-left, .course-info-right {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .course-info-right {
            border-left: 0px solid #000;
        }
        .info-row {
            margin-bottom: 3px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .attendance-table th, .attendance-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }
        .attendance-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .nim-column {
            width: 80px;
        }
        .name-column {
            width: 150px;
            text-align: left;
        }
        .meeting-column {
            width: 25px;
        }
        .summary {
            margin-top: 20px;
            font-size: 11px;
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
        .present { background-color: #d4edda; }
        .late { background-color: #fff3cd; }
        .absent { background-color: #f8d7da; }
        .excused { background-color: #cce7ff; }
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
                <?php 
                $phonefax = array();
                if ($includephoneinreports && !empty($institutionphone)) {
                    $phonefax[] = 'Phone: ' . htmlspecialchars($institutionphone);
                }
                if ($includefaxinreports && !empty($institutionfax)) {
                    $phonefax[] = 'Fax: ' . htmlspecialchars($institutionfax);
                }
                if (!empty($phonefax)): ?>
                    <?php echo implode(' | ', $phonefax); ?><br>
                <?php endif; ?>
                <?php 
                $emailweb = array();
                if ($includeemailinreports && !empty($institutionemail)) {
                    $emailweb[] = 'Email: ' . htmlspecialchars($institutionemail);
                }
                if ($includewebsiteinreports && !empty($institutionwebsite)) {
                    $emailweb[] = 'Website: ' . htmlspecialchars($institutionwebsite);
                }
                if (!empty($emailweb)): ?>
                    <?php echo implode(' | ', $emailweb); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div style="text-align: center; margin: 4px 0; font-size: 16px; font-weight: bold; text-transform: uppercase;">
        <?php echo get_string('studentreport', 'qratt'); ?>
    </div>

    <!-- Course Information -->
    <div class="course-info">
        <div class="course-info-left">
            <?php if (!empty($qratt->semester)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('semester', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->semester); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->department)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('department', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->department); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->studyprogram)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('studyprogram', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->studyprogram); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->subject)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('subject', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->subject); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->credits)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('credits', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->credits); ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="course-info-right">
            <?php if (!empty($qratt->classname)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('classname', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->classname); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->lecturer)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('lecturer', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->lecturer); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->dayofweek)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('dayofweek', 'qratt'); ?></span>
                <?php echo get_string($qratt->dayofweek, 'qratt'); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->scheduletime)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('scheduletime', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->scheduletime); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($qratt->room)): ?>
            <div class="info-row">
                <span class="info-label"><?php echo get_string('room', 'qratt'); ?></span>
                <?php echo htmlspecialchars($qratt->room); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Table -->
    <table class="attendance-table">
        <thead>
            <tr>
                <th rowspan="2"><?php echo get_string('no', 'qratt'); ?></th>
                <th rowspan="2" class="nim-column"><?php echo get_string('nim', 'qratt'); ?></th>
                <th rowspan="2" class="name-column"><?php echo get_string('fullname', 'qratt'); ?></th>
                <th colspan="16"><?php echo get_string('meetings', 'qratt'); ?></th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= 16; $i++): ?>
                    <th class="meeting-column"><?php echo $i; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($students)): ?>
                <?php $no = 1; ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php 
                            // Use username as NIM, remove @ and everything after if present
                            $nim = $student->username;
                            if (strpos($nim, '@') !== false) {
                                $nim = substr($nim, 0, strpos($nim, '@'));
                            }
                            echo htmlspecialchars($nim); 
                        ?></td>
                        <td class="name-column"><?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?></td>
                        <?php for ($meetingnum = 1; $meetingnum <= 16; $meetingnum++): ?>
                            <td class="meeting-column">
                                <?php 
                                $status = isset($attendancerecords[$student->id][$meetingnum]) ? $attendancerecords[$student->id][$meetingnum] : null;
                                $cellclass = '';
                                $symbol = '-';
                                
                                switch ($status) {
                                    case QRATT_STATUS_PRESENT:
                                        $symbol = '✓';
                                        $cellclass = 'present';
                                        break;
                                    case QRATT_STATUS_LATE:
                                        $symbol = 'L';
                                        $cellclass = 'late';
                                        break;
                                    case QRATT_STATUS_ABSENT:
                                        $symbol = '✗';
                                        $cellclass = 'absent';
                                        break;
                                    case QRATT_STATUS_EXCUSED:
                                        $symbol = 'E';
                                        $cellclass = 'excused';
                                        break;
                                }
                                echo '<span class="' . $cellclass . '">' . $symbol . '</span>';
                                ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="19"><?php echo get_string('nostudents', 'qratt'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Attendance Summary -->
    <?php if (!empty($students)): ?>
    <div class="summary">
        <strong><?php echo get_string('attendancesummary', 'qratt'); ?>:</strong><br>
        <?php echo get_string('totalstudents', 'qratt'); ?>: <?php echo count($students); ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <?php 
        // Format date as dd MMMM yyyy (Indonesian style)
        $dateformat = '%d %B %Y';
        if ($includecityinreports && !empty($institutioncity)): ?>
            <?php echo htmlspecialchars($institutioncity) . ', ' . userdate(time(), $dateformat); ?>
        <?php else: ?>
            <?php echo userdate(time(), $dateformat); ?>
        <?php endif; ?>
        <br>
        <?php echo get_string('lecturer_in_charge', 'qratt'); ?><br><br>
        <div class="footer-signature">
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