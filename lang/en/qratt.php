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
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
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
$string['location'] = 'Location';
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
$string['activeduration'] = 'Active duration';
$string['activeduration_help'] = 'How long after the meeting start time students can still be marked as Present. After this duration, late scans will be marked as Late status.';

// Errors
$string['error:meetingnotfound'] = 'Meeting not found';
$string['error:cannotactivate'] = 'Cannot activate meeting';
$string['error:meetingnotactive'] = 'Meeting is not active';
$string['error:alreadyended'] = 'Meeting has already ended';
$string['error:rolenotfound'] = 'Student role not found in the system';
$string['cannotmarkattendance'] = 'Cannot mark attendance. Please try again.';
$string['onlystudentscanattend'] = 'Only students can mark attendance by scanning QR codes.';
$string['error:notenrolledincourse'] = 'You are not enrolled in the course "{$a}".';

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
$string['reportdate'] = 'Report Date';

// Scanner functionality
$string['scanqrcode'] = 'Scan QR Code';
$string['activemeetingfound'] = 'Active meeting found! You can scan the QR code now.';
$string['noactivemeetings'] = 'No active meetings at this time.';
$string['scannerinfo'] = 'Point your camera at the QR code displayed by your lecturer to mark your attendance.';
$string['scannerresult'] = 'Scan result will appear here...';
$string['manualentry'] = 'Manual Entry';
$string['manualentryinfo'] = 'If the camera scanner is not working, you can manually enter the QR code URL:';
$string['invalidqrurl'] = 'Invalid QR code URL. Please check the URL and try again.';

// Manual attendance
$string['manualattendance'] = 'Manual Attendance';
$string['selectmeeting'] = 'Select Meeting';
$string['selectmeetinginfo'] = 'Select a meeting to manage attendance manually.';
$string['manageattendance'] = 'Manage Attendance';
$string['manualattendancefor'] = 'Attendance for: {$a}';
$string['currentstatus'] = 'Current Status';
$string['setattendance'] = 'Set Attendance';
$string['saveattendance'] = 'Save Attendance';
$string['attendanceupdated'] = 'Attendance updated: {$a->saved} new records saved, {$a->updated} records updated.';
$string['notset'] = 'Not Set';
$string['nostudents'] = 'No students found in this course.';
$string['back'] = 'Back';
$string['managemeetings'] = 'Manage Meetings';
$string['meetingsoverview'] = 'Meetings Overview';
$string['bulkselection'] = 'Bulk Selection';
$string['bulkselectionhelp'] = 'Select an attendance status below to set all students to that status at once. You can then modify individual students as needed.';

// Admin Settings
$string['securitysettings'] = 'Security Settings';
$string['securitysettings_desc'] = 'Configure security settings for QR code generation and validation.';
$string['encryptionkey'] = 'QR Code Encryption Key';
$string['encryptionkey_desc'] = 'Encryption key used for QR code token generation and validation. Leave empty to use default system key. Changing this key will invalidate existing QR codes.';

$string['institutionsettings'] = 'Institution Information';
$string['institutionsettings_desc'] = 'Configure institution information to be included in attendance reports.';
$string['institutionname'] = 'Institution Name';
$string['institutionname_desc'] = 'Name of the educational institution';
$string['institutionaddress'] = 'Institution Address';
$string['institutionaddress_desc'] = 'Complete address of the institution';
$string['institutionphone'] = 'Institution Phone';
$string['institutionphone_desc'] = 'Phone number of the institution';
$string['institutionfax'] = 'Institution Fax';
$string['institutionfax_desc'] = 'Fax number of the institution';
$string['institutionlogo'] = 'Institution Logo';
$string['institutionlogo_desc'] = 'Upload institution logo to be used in reports. Supported formats: PNG, JPG, JPEG, GIF';

$string['reportsettings'] = 'Report Settings';
$string['reportsettings_desc'] = 'Configure what information to include in attendance reports.';
$string['includelogoinreports'] = 'Include Logo in Reports';
$string['includelogoinreports_desc'] = 'Include institution logo in generated reports';
$string['includeaddressinreports'] = 'Include Address in Reports';
$string['includeaddressinreports_desc'] = 'Include institution address in generated reports';
$string['includewebsiteinreports'] = 'Include Website in Reports';
$string['includewebsiteinreports_desc'] = 'Include institution website in generated reports';
$string['includeemailinreports'] = 'Include Email in Reports';
$string['includeemailinreports_desc'] = 'Include institution email in generated reports';
$string['includecityinreports'] = 'Include City in Reports';
$string['includecityinreports_desc'] = 'Include institution city in report footers';
$string['includephoneinreports'] = 'Include Phone in Reports';
$string['includephoneinreports_desc'] = 'Include institution phone number in generated reports';
$string['includefaxinreports'] = 'Include Fax in Reports';
$string['includefaxinreports_desc'] = 'Include institution fax number in generated reports';

// Institution fields
$string['institutioncity'] = 'Institution City';
$string['institutioncity_desc'] = 'City where the institution is located';
$string['institutionwebsite'] = 'Institution Website';
$string['institutionwebsite_desc'] = 'Website URL of the institution';
$string['institutionemail'] = 'Institution Email';
$string['institutionemail_desc'] = 'Official email address of the institution';

// Course information fields
$string['courseinformation'] = 'Course Information';
$string['semester'] = 'Semester';
$string['semester_help'] = 'Academic semester or term (e.g., Fall 2024, Spring 2024)';
$string['department'] = 'Department';
$string['department_help'] = 'Department or faculty offering this course';
$string['studyprogram'] = 'Study Program';
$string['studyprogram_help'] = 'Study program or major associated with this course';
$string['subject'] = 'Subject';
$string['subject_help'] = 'Subject or course name';
$string['credits'] = 'Credits (SKS)';
$string['credits_help'] = 'Number of credit hours for this course';
$string['classname'] = 'Class';
$string['classname_help'] = 'Class name or section (e.g., A, B, Morning, Evening)';
$string['lecturer'] = 'Lecturer';
$string['lecturer_help'] = 'Name of the instructor or lecturer';
$string['dayofweek'] = 'Day of Week';
$string['dayofweek_help'] = 'Day of the week when this class is scheduled';
$string['scheduletime'] = 'Schedule Time';
$string['scheduletime_help'] = 'Time when this class is scheduled (e.g., 08:00-10:00)';
$string['room'] = 'Room';
$string['room_help'] = 'Classroom or location where this class takes place';

// Days of the week
$string['selectday'] = 'Select day';
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';

// Meeting teacher
$string['meetingteacher'] = 'Meeting Teacher';
$string['meetingteacher_help'] = 'Select the teacher who will be conducting this meeting';
$string['selectteacher'] = 'Select teacher';

// Report strings
$string['studentreport'] = 'Student Attendance Report';
$string['teacherreport'] = 'Lecture Teaching Report';
$string['printstudentreport'] = 'Student Report';
$string['printteacherreport'] = 'Lecture Report';
$string['no'] = 'No.';
$string['nim'] = 'Student ID';
$string['fullname'] = 'Full Name';
$string['numberpresent'] = 'Number Present';
$string['numberabsent'] = 'Number Absent';
$string['print'] = 'Print';
$string['lecturer_in_charge'] = 'Lecturer in Charge';

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
