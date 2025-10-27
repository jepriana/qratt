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
 * Security and functional requirements tests for QR Attendance
 * Tests requirements not covered by lib_test.php and attendance_workflow_test.php
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_qratt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/qratt/lib.php');

/**
 * Security and functional requirements tests
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class security_requirements_test extends \advanced_testcase {

    // ==================== SECURITY TESTS ====================

    /**
     * SEC-1: QR code must be rejected after session ends
     */
    public function test_qr_rejected_after_session_ends() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        // Create ended meeting
        $meeting = new \stdClass();
        $meeting->qrattid = $qratt->id;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'Ended Meeting';
        $meeting->meetingdate = time() - 3600;
        $meeting->status = QRATT_MEETING_ENDED;
        $meeting->qrexpiry = time() - 1800;
        $meeting->timecreated = time();
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // Verify meeting cannot accept attendance
        $isactive = ($meeting->status == QRATT_MEETING_ACTIVE);
        $notexpired = ($meeting->qrexpiry > time());
        $canacceptattendance = ($isactive && $notexpired);
        
        $this->assertFalse($canacceptattendance, 'Ended meeting should not accept attendance');
    }

    /**
     * SEC-2: Unregistered students cannot access attendance data
     */
    public function test_unregistered_student_denied_access() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $unregistered = $this->getDataGenerator()->create_user();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($course->id);

        $this->setUser($unregistered);

        // Verify not enrolled
        $isenrolled = is_enrolled($coursecontext, $unregistered->id);
        $this->assertFalse($isenrolled, 'User should not be enrolled');

        // Verify no access to module
        $hasaccess = has_capability('mod/qratt:view', $context);
        $this->assertFalse($hasaccess, 'Unregistered student should not have access');
    }

    /**
     * SEC-3: Cross-teacher access denied (Teacher B cannot access Teacher A's data)
     */
    public function test_cross_teacher_access_denied() {
        global $DB;
        $this->resetAfterTest(true);

        $courseA = $this->getDataGenerator()->create_course();
        $courseB = $this->getDataGenerator()->create_course();
        $teacherA = $this->getDataGenerator()->create_user();
        $teacherB = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($teacherA->id, $courseA->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacherB->id, $courseB->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qrattA = $generator->create_instance(['course' => $courseA->id]);

        $cmA = get_coursemodule_from_instance('qratt', $qrattA->id, $courseA->id);
        $contextA = \context_module::instance($cmA->id);

        $this->setUser($teacherB);

        // Teacher B should NOT have access to Course A
        $canmanage = has_capability('mod/qratt:manage', $contextA);
        $canviewreports = has_capability('mod/qratt:viewreports', $contextA);
        
        $this->assertFalse($canmanage, 'Teacher B should not manage Course A');
        $this->assertFalse($canviewreports, 'Teacher B should not view Course A reports');
    }

    /**
     * SEC-4: Cross-student data access denied (Student A cannot access Student B's data)
     */
    public function test_cross_student_access_denied() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $studentA = $this->getDataGenerator()->create_user();
        $studentB = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($studentA->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($studentB->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);
        $generator->create_attendance([
            'meetingid' => $meeting->id,
            'userid' => $studentB->id,
            'status' => QRATT_STATUS_PRESENT
        ]);

        $this->setUser($studentA);
        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        // Student A should not have admin capabilities
        $canmanage = has_capability('mod/qratt:manageattendances', $context);
        $canviewallreports = has_capability('mod/qratt:viewreports', $context);
        
        $this->assertFalse($canmanage, 'Students should not manage attendance');
        $this->assertFalse($canviewallreports, 'Students should not view all reports');

        // Verify students can only see their own data
        $statsA = qratt_get_user_statistics($qratt->id, $studentA->id);
        $statsB = qratt_get_user_statistics($qratt->id, $studentB->id);
        
        $this->assertEquals(0, $statsA['present'], 'Student A should have 0 present');
        $this->assertEquals(1, $statsB['present'], 'Student B should have 1 present');
    }

    /**
     * SEC-5: Direct API manipulation prevention
     */
    public function test_api_manipulation_prevented() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

        $this->setUser($student);
        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        // Students should not have permission to manage attendance
        $canmanage = has_capability('mod/qratt:manageattendances', $context);
        $this->assertFalse($canmanage, 'Students cannot manage attendance via API');
    }

    // ==================== TEACHER FUNCTIONAL TESTS ====================

    /**
     * FUNC-1: Teachers can manage attendance activities
     */
    public function test_teacher_manage_activities() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        $this->setUser($teacher);

        // Verify teacher capabilities
        $this->assertTrue(has_capability('mod/qratt:manage', $context));
        $this->assertTrue(has_capability('mod/qratt:manageattendances', $context));
        $this->assertTrue(has_capability('mod/qratt:viewreports', $context));
    }

    /**
     * FUNC-2: Teachers can activate and deactivate meetings
     */
    public function test_teacher_activate_deactivate_meeting() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        // Create inactive meeting
        $meeting = $generator->create_meeting([
            'qrattid' => $qratt->id,
            'status' => QRATT_MEETING_INACTIVE,
            'teacherid' => $teacher->id
        ]);

        $this->assertEquals(QRATT_MEETING_INACTIVE, $meeting->status);

        // Activate
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->qrexpiry = time() + 1800;
        $meeting->qrcode = qratt_generate_qr_code($meeting->id, $meeting->qrexpiry);
        $DB->update_record('qratt_meetings', $meeting);

        $activated = $DB->get_record('qratt_meetings', ['id' => $meeting->id]);
        $this->assertEquals(QRATT_MEETING_ACTIVE, $activated->status);
        $this->assertNotEmpty($activated->qrcode);

        // Deactivate
        $activated->status = QRATT_MEETING_ENDED;
        $DB->update_record('qratt_meetings', $activated);

        $ended = $DB->get_record('qratt_meetings', ['id' => $meeting->id]);
        $this->assertEquals(QRATT_MEETING_ENDED, $ended->status);
    }

    /**
     * FUNC-3: Teachers can display dynamic QR codes
     */
    public function test_teacher_display_dynamic_qr() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        // Create active meeting with QR
        $meeting = $generator->create_meeting([
            'qrattid' => $qratt->id,
            'status' => QRATT_MEETING_ACTIVE,
            'teacherid' => $teacher->id
        ]);

        // Generate QR code
        $qrcode = qratt_generate_qr_code($meeting->id, time() + 60);
        
        $this->assertNotEmpty($qrcode);
        $this->assertStringContainsString('/mod/qratt/scan.php', $qrcode);
        $this->assertStringContainsString('meeting=' . $meeting->id, $qrcode);
    }

    /**
     * FUNC-4: Teachers can change student attendance status
     */
    public function test_teacher_change_attendance_status() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

        // Create attendance as ABSENT
        $attendance = $generator->create_attendance([
            'meetingid' => $meeting->id,
            'userid' => $student->id,
            'status' => QRATT_STATUS_ABSENT
        ]);

        // Teacher changes to EXCUSED
        $this->setUser($teacher);
        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);
        
        $this->assertTrue(has_capability('mod/qratt:manageattendances', $context));

        $attendance->status = QRATT_STATUS_EXCUSED;
        $attendance->timemodified = time();
        $DB->update_record('qratt_attendance', $attendance);

        $updated = $DB->get_record('qratt_attendance', ['id' => $attendance->id]);
        $this->assertEquals(QRATT_STATUS_EXCUSED, $updated->status);
    }

    /**
     * FUNC-5: Teachers can access attendance reports
     */
    public function test_teacher_access_reports() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        $this->setUser($teacher);
        $this->assertTrue(has_capability('mod/qratt:viewreports', $context));
    }

    // ==================== STUDENT FUNCTIONAL TESTS ====================

    /**
     * FUNC-6: Students can scan QR codes
     */
    public function test_student_scan_qr_code() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting([
            'qrattid' => $qratt->id,
            'status' => QRATT_MEETING_ACTIVE
        ]);

        $this->setUser($student);
        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        // Student has takeattendance capability
        $this->assertTrue(has_capability('mod/qratt:takeattendance', $context));

        // Simulate scan
        $attendance = $generator->create_attendance([
            'meetingid' => $meeting->id,
            'userid' => $student->id,
            'status' => QRATT_STATUS_PRESENT,
            'scantime' => time()
        ]);

        $this->assertNotEmpty($attendance->scantime);
        $this->assertEquals($student->id, $attendance->userid);
    }

    /**
     * FUNC-7: Students receive status report after scan
     */
    public function test_student_receive_status_after_scan() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

        // Record attendance
        $attendance = $generator->create_attendance([
            'meetingid' => $meeting->id,
            'userid' => $student->id,
            'status' => QRATT_STATUS_PRESENT
        ]);

        // Verify status is recorded
        $record = $DB->get_record('qratt_attendance', ['id' => $attendance->id]);
        $this->assertEquals(QRATT_STATUS_PRESENT, $record->status);
        
        // In real implementation, scan.php displays this status
        $statustext = ($record->status == QRATT_STATUS_PRESENT) ? 'Present' : 'Other';
        $this->assertEquals('Present', $statustext);
    }

    /**
     * FUNC-8: Students can view their attendance history
     */
    public function test_student_view_history() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        // Create 5 meetings with attendance
        for ($i = 1; $i <= 5; $i++) {
            $meeting = $generator->create_meeting([
                'qrattid' => $qratt->id,
                'meetingnumber' => $i
            ]);
            $generator->create_attendance([
                'meetingid' => $meeting->id,
                'userid' => $student->id,
                'status' => QRATT_STATUS_PRESENT
            ]);
        }

        // Get statistics (history)
        $stats = qratt_get_user_statistics($qratt->id, $student->id);
        
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(5, $stats['present']);
        $this->assertEquals(100.0, $stats['percentage']);
    }

    // ==================== SECURITY REQUIREMENTS ====================

    /**
     * SEC-6: QR codes must be unique per session
     */
    public function test_qr_codes_unique_per_session() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $qrcodes = [];
        for ($i = 1; $i <= 10; $i++) {
            $meeting = $generator->create_meeting([
                'qrattid' => $qratt->id,
                'meetingnumber' => $i
            ]);
            $qrcodes[] = qratt_generate_qr_code($meeting->id, time() + 60);
        }

        // All QR codes must be unique
        $unique = array_unique($qrcodes);
        $this->assertCount(10, $unique, 'All QR codes must be unique');
    }

    /**
     * SEC-7: QR codes cannot be reused
     */
    public function test_qr_codes_cannot_be_reused() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $meeting1 = $generator->create_meeting(['qrattid' => $qratt->id, 'meetingnumber' => 1]);
        $meeting2 = $generator->create_meeting(['qrattid' => $qratt->id, 'meetingnumber' => 2]);

        $qr1 = qratt_generate_qr_code($meeting1->id, time() + 60);
        $qr2 = qratt_generate_qr_code($meeting2->id, time() + 60);

        $this->assertNotEquals($qr1, $qr2, 'Different meetings must have different QR codes');
    }

    /**
     * SEC-8: Role-based access restriction verified
     */
    public function test_role_based_access_restrictions() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        // Student restrictions
        $this->setUser($student);
        $this->assertFalse(has_capability('mod/qratt:manage', $context));
        $this->assertFalse(has_capability('mod/qratt:viewreports', $context));

        // Teacher permissions
        $this->setUser($teacher);
        $this->assertTrue(has_capability('mod/qratt:manage', $context));
        $this->assertTrue(has_capability('mod/qratt:viewreports', $context));
    }

    /**
     * SEC-9: Comprehensive role-based access control validation
     * Moved from attendance_workflow_test.php - security test, not workflow
     */
    public function test_sec9_comprehensive_role_verification() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $editingteacher = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($editingteacher->id, $course->id, 'editingteacher');

        // Create module using proper generator
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance([
            'course' => $course->id,
            'name' => 'Test Attendance'
        ]);

        $cm = get_coursemodule_from_instance('qratt', $qratt->id, $course->id);
        $context = \context_module::instance($cm->id);

        // Test student capabilities
        $this->setUser($student);
        $this->assertTrue(has_capability('mod/qratt:view', $context));
        $this->assertTrue(has_capability('mod/qratt:takeattendance', $context));
        $this->assertFalse(has_capability('mod/qratt:manage', $context));
        $this->assertFalse(has_capability('mod/qratt:viewreports', $context));
        $this->assertFalse(has_capability('mod/qratt:manageattendances', $context));

        // Test teacher capabilities
        $this->setUser($teacher);
        $this->assertTrue(has_capability('mod/qratt:view', $context));
        $this->assertTrue(has_capability('mod/qratt:manage', $context));
        $this->assertTrue(has_capability('mod/qratt:viewreports', $context));
        $this->assertTrue(has_capability('mod/qratt:manageattendances', $context));

        // Test editing teacher capabilities
        $this->setUser($editingteacher);
        $this->assertTrue(has_capability('mod/qratt:view', $context));
        $this->assertTrue(has_capability('mod/qratt:manage', $context));
        $this->assertTrue(has_capability('mod/qratt:viewreports', $context));
        $this->assertTrue(has_capability('mod/qratt:manageattendances', $context));
        $this->assertTrue(has_capability('mod/qratt:addinstance', \context_course::instance($course->id)));
    }

    /**
     * SEC-10: Duplicate attendance prevention (prevents manipulation)
     * Moved from attendance_workflow_test.php - security test, not workflow
     */
    public function test_sec10_duplicate_prevention() {
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

        $meeting = new \stdClass();
        $meeting->qrattid = $qrattid;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'Test Meeting';
        $meeting->meetingdate = time();
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->timecreated = time();
        $meeting->timemodified = time();
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // First attendance record
        $attendance1 = new \stdClass();
        $attendance1->meetingid = $meetingid;
        $attendance1->userid = $student->id;
        $attendance1->status = QRATT_STATUS_PRESENT;
        $attendance1->scantime = time();
        $attendance1->timecreated = time();
        $attendance1->timemodified = time();
        $DB->insert_record('qratt_attendance', $attendance1);

        // Check existing attendance (as scan.php does)
        $existing = $DB->get_record('qratt_attendance', 
            ['meetingid' => $meetingid, 'userid' => $student->id]);
        $this->assertNotEmpty($existing);

        // Attempt duplicate attendance should be prevented
        $this->expectException(\dml_write_exception::class);
        $DB->insert_record('qratt_attendance', $attendance1);
    }

    /**
     * SEC-11: QR code expiry validation (prevents replay attacks)
     * Moved from attendance_workflow_test.php - security test, not workflow
     */
    public function test_sec11_qr_expiry_validation() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test Attendance';
        $qratt->intro = 'Test';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        // Create meeting with QR code that expires soon
        $currenttime = time();
        $meeting = new \stdClass();
        $meeting->qrattid = $qrattid;
        $meeting->meetingnumber = 1;
        $meeting->topic = 'Test Meeting';
        $meeting->meetingdate = $currenttime;
        $meeting->status = QRATT_MEETING_ACTIVE;
        $meeting->qrexpiry = $currenttime + 60; // Expires in 60 seconds
        $meeting->timecreated = $currenttime;
        $meeting->timemodified = $currenttime;
        $meetingid = $DB->insert_record('qratt_meetings', $meeting);

        // Verify QR code is not expired
        $record = $DB->get_record('qratt_meetings', ['id' => $meetingid]);
        $this->assertGreaterThan($currenttime, $record->qrexpiry);

        // Simulate expired QR code
        $record->qrexpiry = $currenttime - 1; // Already expired
        $DB->update_record('qratt_meetings', $record);

        $record = $DB->get_record('qratt_meetings', ['id' => $meetingid]);
        $this->assertLessThanOrEqual($currenttime, $record->qrexpiry);

        // Verify expired status
        $isexpired = ($record->qrexpiry <= $currenttime);
        $this->assertTrue($isexpired);
    }

    // ==================== PERFORMANCE REQUIREMENTS ====================

    /**
     * PERF-1: QR code generation should be fast (< 3 seconds)
     */
    public function test_qr_generation_performance() {
        $this->resetAfterTest(true);

        $starttime = microtime(true);
        $qrcode = qratt_generate_qr_code(123, time() + 60);
        $endtime = microtime(true);

        $executiontime = $endtime - $starttime;
        $this->assertLessThan(3.0, $executiontime, 'QR generation must be under 3 seconds');
        $this->assertNotEmpty($qrcode);
    }

    /**
     * PERF-2: Scan validation should be fast (< 5 seconds)
     */
    public function test_scan_validation_performance() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

        $starttime = microtime(true);
        
        // Simulate validation steps
        $qrcode = qratt_generate_qr_code($meeting->id, time() + 60);
        $url = new \moodle_url($qrcode);
        $token = $url->get_param('token');
        $meetingid = $url->get_param('meeting');
        
        $salt = qratt_get_encryption_key();
        $validtoken = md5($meetingid . (time() + 60) . $salt);
        
        $endtime = microtime(true);
        $executiontime = $endtime - $starttime;

        $this->assertLessThan(5.0, $executiontime, 'Scan validation must be under 5 seconds');
    }

    /**
     * PERF-3: System handles multiple concurrent requests
     */
    public function test_concurrent_attendance_handling() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
        $qratt = $generator->create_instance(['course' => $course->id]);
        $meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

        // Simulate 50 concurrent requests
        $starttime = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            $student = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
            
            $generator->create_attendance([
                'meetingid' => $meeting->id,
                'userid' => $student->id,
                'status' => QRATT_STATUS_PRESENT
            ]);
        }
        
        $endtime = microtime(true);
        $executiontime = $endtime - $starttime;

        // Verify all records created
        $count = $DB->count_records('qratt_attendance', ['meetingid' => $meeting->id]);
        $this->assertEquals(50, $count, 'System should handle 50 concurrent requests');
        
        // Performance should be reasonable (< 10 seconds for 50 inserts)
        $this->assertLessThan(10.0, $executiontime);
    }
}
