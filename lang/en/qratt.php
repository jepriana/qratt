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
 * English strings for qratt
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'QR Attendance';
$string['modulenameplural'] = 'QR Attendances';
$string['modulename_help'] = 'Use the QR Attendance module to track student attendance using QR codes. Teachers can create meetings and generate QR codes that students scan to mark their attendance.';
$string['qratt:addinstance'] = 'Add a new QR Attendance';
$string['qratt:view'] = 'View QR Attendance';
$string['qratt:manage'] = 'Manage QR Attendance';
$string['qratt:takeattendance'] = 'Take attendance';
$string['qratt:viewreports'] = 'View attendance reports';
$string['qratt:manageattendances'] = 'Manage attendance records';
$string['qratt:canbelisted'] = 'Can be listed in attendance';
$string['qrattfieldset'] = 'Custom example fieldset';
$string['qrattname'] = 'QR Attendance name';
$string['qrattname_help'] = 'This is the content of the help tooltip associated with the qrattname field. Markdown syntax is supported.';
$string['qratt'] = 'qratt';
$string['pluginadministration'] = 'QR Attendance administration';
$string['pluginname'] = 'QR Attendance';

// Navigation
$string['overview'] = 'Overview';
$string['meetings'] = 'Meetings';
$string['reports'] = 'Reports';

// Meeting management
$string['addmeeting'] = 'Add meeting';
$string['editmeeting'] = 'Edit meeting';
$string['deleteemeeting'] = 'Delete meeting';
$string['meetingnumber'] = 'Meeting number';
$string['topic'] = 'Topic';
$string['date'] = 'Date';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['yourstatus'] = 'Your status';
$string['activate'] = 'Activate';
$string['showqr'] = 'Show QR Code';
$string['endmeeting'] = 'End meeting';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['ended'] = 'Ended';

// Attendance statuses
$string['present'] = 'Present';
$string['absent'] = 'Absent';
$string['late'] = 'Late';
$string['excused'] = 'Excused';

// Messages
$string['nomeetings'] = 'No meetings have been created yet.';
$string['nomeetingsinfo'] = 'To start taking attendance, you need to create meetings first.';
$string['attendancerecord'] = 'Attended {$a->present} out of {$a->total} meetings';
$string['attendancesummary'] = 'Attendance Summary';
$string['totalmeetings'] = 'Total meetings';
$string['attendancepercentage'] = 'Attendance percentage';

// QR Code
$string['qrcode'] = 'QR Code';
$string['qrcodefor'] = 'QR Code for {$a}';
$string['qrrefresh'] = 'QR code refreshes every 60 seconds';
$string['scanqr'] = 'Scan QR Code';
$string['qrexpired'] = 'QR code has expired';
$string['qrinvalid'] = 'Invalid QR code';
$string['attendancemarked'] = 'Attendance marked successfully';
$string['alreadymarked'] = 'Attendance already marked for this meeting';
$string['fullscreen'] = 'Full Screen';

// Forms
$string['meetingform'] = 'Meeting form';
$string['meetingdate'] = 'Meeting date';
$string['exitfullscreen'] = 'Exit Full Screen';
$string['meetingtopic'] = 'Meeting topic';
$string['duration'] = 'Duration (minutes)';
$string['activeduration'] = 'Active duration (minutes)';
$string['activeduration_help'] = 'How long the QR code should remain active for scanning';

// Errors
$string['error:meetingnotfound'] = 'Meeting not found';
$string['error:cannotactivate'] = 'Cannot activate meeting';
$string['error:meetingnotactive'] = 'Meeting is not active';
$string['error:alreadyended'] = 'Meeting has already ended';

// Additional strings
$string['timeremaining'] = 'Time remaining';
$string['refresh'] = 'Refresh';
$string['meetingactivated'] = 'Meeting activated successfully';
$string['meetingended'] = 'Meeting ended successfully';
$string['meetingdeleted'] = 'Meeting deleted successfully';
$string['meetingcreated'] = 'Meeting created successfully';
$string['meetingupdated'] = 'Meeting updated successfully';
$string['meetingnumberexists'] = 'This meeting number already exists';
$string['confirmdeletion'] = 'Are you sure you want to delete this meeting?';
$string['yourcurrentstatus'] = 'Your current status';
$string['meetingdetails'] = 'Meeting Details';
$string['scantime'] = 'Scan time';
$string['continueto'] = 'Continue to {$a}';
$string['student'] = 'Student';
$string['meeting'] = 'Meeting';
$string['bymeeting'] = 'By Meeting';
$string['bystudent'] = 'By Student';
$string['reportbymeeting'] = 'Report by Meeting';
$string['reportbystudent'] = 'Report by Student';
$string['summary'] = 'Summary';
$string['nousers'] = 'No users found';
$string['attendanceoverview'] = 'Attendance Overview';
$string['nodata'] = 'No data available';
$string['overallstatistics'] = 'Overall Statistics';
$string['totalstudents'] = 'Total students';
$string['overallattendance'] = 'Overall attendance';
$string['attendancebreakdown'] = 'Attendance Breakdown';
$string['meetingwisesummary'] = 'Meeting-wise Summary';
$string['totalpresent'] = 'Total present';
$string['totalabsent'] = 'Total absent';
$string['percentage'] = 'Percentage';
$string['downloadcsv'] = 'Download CSV';

// Scanner functionality
$string['scanqrcode'] = 'Scan QR Code';
$string['activemeetingfound'] = 'Active meeting found! You can scan the QR code now.';
$string['noactivemeetings'] = 'No active meetings at this time.';
$string['scannerinfo'] = 'Point your camera at the QR code displayed by your lecturer to mark your attendance.';
$string['scannerresult'] = 'Scan result will appear here...';
$string['manualentry'] = 'Manual Entry';
$string['manualentryinfo'] = 'If the camera scanner is not working, you can manually enter the QR code URL:';
$string['invalidqrurl'] = 'Invalid QR code URL. Please check the URL and try again.';

// Events
$string['eventcoursemoduleviewed'] = 'QR Attendance module viewed';

// Privacy
$string['privacy:metadata'] = 'The QR Attendance plugin stores attendance data for users.';
$string['privacy:metadata:qratt_attendance'] = 'Information about user attendance in QR Attendance activities.';
$string['privacy:metadata:qratt_attendance:userid'] = 'The ID of the user whose attendance is being recorded.';
$string['privacy:metadata:qratt_attendance:status'] = 'The attendance status of the user for the meeting.';
$string['privacy:metadata:qratt_attendance:scantime'] = 'The time when the user scanned the QR code.';
$string['privacy:metadata:qratt_attendance:timecreated'] = 'The time when the attendance record was created.';
$string['privacy:metadata:qratt_attendance:timemodified'] = 'The time when the attendance record was last modified.';
