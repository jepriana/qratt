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
 * Integration tests for QR attendance workflow
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_qratt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/qratt/lib.php');

/**
 * Integration tests for attendance workflow
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_workflow_test extends \advanced_testcase {

    /**
     * Test complete attendance workflow from meeting creation to QR scanning
     */
    public function test_complete_attendance_workflow() {
        global $DB;
        $this->resetAfterTest(true);

        // Create course and users
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        // Enroll users
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');

        // Create QR Attendance instance
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Course Attendance';
        $qratt->intro = 'Test attendance';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        // Create meeting
        $meeting = new \stdClass();
        $meeting->qrattid = $qrattid;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'First Meeting';
        $meeting->meetingdate = time();
        $meeting->starttime = time();
        $meeting->endtime = time() + 3600;
        $meeting->activeduration = 900; // 15 minutes for late threshold
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->qrexpiry = time() + 60;
        $meeting->timecreated = time();
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // Generate QR code
        $qrcode = qratt_generate_qr_code($meetingid, $meeting->qrexpiry);
        $this->assertNotEmpty($qrcode);

        // Parse QR code URL to get token
        $url = new \moodle_url($qrcode);
        $token = $url->get_param('token');
        $this->assertNotEmpty($token);

        // Simulate student scanning QR code (student1 - present)
        $attendancerecord1 = new \stdClass();
        $attendancerecord1->meetingid = $meetingid;
        $attendancerecord1->userid = $student1->id;
        $attendancerecord1->status = QRATT_STATUS_PRESENT;
        $attendancerecord1->scantime = time();
        $attendancerecord1->timecreated = time();
        $attendancerecord1->timemodified = time();
        $DB->insert_record('qratt_attendance', $attendancerecord1);

        // Simulate student2 scanning late
        $attendancerecord2 = new \stdClass();
        $attendancerecord2->meetingid = $meetingid;
        $attendancerecord2->userid = $student2->id;
        $attendancerecord2->status = QRATT_STATUS_LATE;
        $attendancerecord2->scantime = time() + 1000; // 16+ minutes late
        $attendancerecord2->timecreated = time();
        $attendancerecord2->timemodified = time();
        $DB->insert_record('qratt_attendance', $attendancerecord2);

        // Verify attendance records
        $attendance1 = $DB->get_record('qratt_attendance', 
            ['meetingid' => $meetingid, 'userid' => $student1->id]);
        $this->assertNotEmpty($attendance1);
        $this->assertEquals(QRATT_STATUS_PRESENT, $attendance1->status);

        $attendance2 = $DB->get_record('qratt_attendance', 
            ['meetingid' => $meetingid, 'userid' => $student2->id]);
        $this->assertNotEmpty($attendance2);
        $this->assertEquals(QRATT_STATUS_LATE, $attendance2->status);

        // Verify statistics
        $stats1 = qratt_get_user_statistics($qrattid, $student1->id);
        $this->assertEquals(1, $stats1['present']);
        $this->assertEquals(0, $stats1['late']);

        $stats2 = qratt_get_user_statistics($qrattid, $student2->id);
        $this->assertEquals(0, $stats2['present']);
        $this->assertEquals(1, $stats2['late']);
    }

    /**
     * Test QR token validation with expiry
     */
    public function test_qr_token_validation() {
        global $CFG;
        $this->resetAfterTest(true);

        $meetingid = 123;
        $expiry = time() + 60;
        $salt = qratt_get_encryption_key();

        // Generate valid token
        $validtoken = md5($meetingid . $expiry . $salt);
        $qrcode = qratt_generate_qr_code($meetingid, $expiry);
        
        // Verify token is in QR code
        $this->assertStringContainsString($validtoken, $qrcode);

        // Test token validation for different time windows (as in scan.php)
        $found = false;
        for ($i = 0; $i <= 2; $i++) {
            $checktimestamp = $expiry - ($i * 60);
            $checktoken = md5($meetingid . $checktimestamp . $salt);
            if (hash_equals($validtoken, $checktoken)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // Test invalid token
        $invalidtoken = md5('invalid' . $expiry . $salt);
        $this->assertNotEquals($validtoken, $invalidtoken);
    }

    /**
     * Test meeting status transitions
     */
    public function test_meeting_status_transitions() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test Attendance';
        $qratt->intro = 'Test';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        // Create meeting in INACTIVE status
        $meeting = new \stdClass();
        $meeting->qrattid = $qrattid;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'Test Meeting';
        $meeting->meetingdate = time();
        $meeting->status = QRATT_MEETING_INACTIVE;
        $meeting->timecreated = time();
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // Verify INACTIVE status
        $record = $DB->get_record('qratt_meetings', ['id' => $meetingid]);
        $this->assertEquals(QRATT_MEETING_INACTIVE, $record->status);

        // Transition to ACTIVE
        $record->status = QRATT_MEETING_ACTIVE;
        $record->qrexpiry = time() + 60;
        $record->qrcode = qratt_generate_qr_code($meetingid, $record->qrexpiry);
        $DB->update_record('qratt_meetings', $record);

        $record = $DB->get_record('qratt_meetings', ['id' => $meetingid]);
        $this->assertEquals(QRATT_MEETING_ACTIVE, $record->status);
        $this->assertNotEmpty($record->qrcode);
        $this->assertGreaterThan(time(), $record->qrexpiry);

        // Transition to ENDED
        $record->status = QRATT_MEETING_ENDED;
        $DB->update_record('qratt_meetings', $record);

        $record = $DB->get_record('qratt_meetings', ['id' => $meetingid]);
        $this->assertEquals(QRATT_MEETING_ENDED, $record->status);
    }

    /**
     * Test late attendance threshold logic
     */
    public function test_late_attendance_threshold() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test Attendance';
        $qratt->intro = 'Test';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        $meetingstart = time();
        $meeting = new \stdClass();
        $meeting->qrattid = $qrattid;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'Test Meeting';
        $meeting->meetingdate = $meetingstart;
        $meeting->starttime = $meetingstart;
        $meeting->activeduration = 900; // 15 minutes
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->qrexpiry = $meetingstart + 1800; // 30 minutes
        $meeting->timecreated = time();
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // Test 1: Scan within active duration (should be PRESENT)
        $scantime1 = $meetingstart + 600; // 10 minutes after start
        $latethreshold = $meetingstart + $meeting->activeduration;
        $status1 = ($scantime1 > $latethreshold) ? QRATT_STATUS_LATE : QRATT_STATUS_PRESENT;
        $this->assertEquals(QRATT_STATUS_PRESENT, $status1);

        // Test 2: Scan after active duration (should be LATE)
        $scantime2 = $meetingstart + 1000; // 16+ minutes after start
        $status2 = ($scantime2 > $latethreshold) ? QRATT_STATUS_LATE : QRATT_STATUS_PRESENT;
        $this->assertEquals(QRATT_STATUS_LATE, $status2);

        // Test 3: Scan exactly at threshold
        $scantime3 = $latethreshold;
        $status3 = ($scantime3 > $latethreshold) ? QRATT_STATUS_LATE : QRATT_STATUS_PRESENT;
        $this->assertEquals(QRATT_STATUS_PRESENT, $status3);
    }

    /**
     * Test attendance statistics calculation with multiple meetings
     */
    public function test_attendance_statistics_multiple_meetings() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test Attendance';
        $qratt->intro = 'Test';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        // Create 10 meetings
        $meetingids = [];
        for ($i = 1; $i <= 10; $i++) {
            $meeting = new \stdClass();
            $meeting->qrattid = $qrattid;
            $meeting->meetingnumber = $i;
            $meeting->topic = "Meeting $i";
            $meeting->meetingdate = time() + ($i * 86400); // One day apart
            $meeting->status = QRATT_MEETING_ENDED;
            $meeting->timecreated = time();
            $meeting->timemodified = time();
            $meetingids[] = $DB->insert_record('qratt_meetings', $meeting);
        }

        // Create varied attendance: 5 present, 2 late, 1 excused, 2 absent
        $statuses = [
            QRATT_STATUS_PRESENT, QRATT_STATUS_PRESENT, QRATT_STATUS_PRESENT,
            QRATT_STATUS_PRESENT, QRATT_STATUS_PRESENT, QRATT_STATUS_LATE,
            QRATT_STATUS_LATE, QRATT_STATUS_EXCUSED
        ];

        foreach ($statuses as $index => $status) {
            $attendance = new \stdClass();
            $attendance->meetingid = $meetingids[$index];
            $attendance->userid = $student->id;
            $attendance->status = $status;
            $attendance->scantime = time();
            $attendance->timecreated = time();
            $attendance->timemodified = time();
            $DB->insert_record('qratt_attendance', $attendance);
        }

        // Get and verify statistics
        $stats = qratt_get_user_statistics($qrattid, $student->id);
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['present']);
        $this->assertEquals(2, $stats['late']);
        $this->assertEquals(1, $stats['excused']);
        $this->assertEquals(2, $stats['absent']); // 10 - 5 - 2 - 1
        $this->assertEquals(50.0, $stats['percentage']); // 5/10 * 100
    }

}
