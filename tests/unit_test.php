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
 * Unit tests for mod_qratt lib.php functions
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
 * Unit tests for lib.php functions
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unit_test extends \advanced_testcase {

    /**
     * Test qratt_supports function
     */
    public function test_qratt_supports() {
        $this->resetAfterTest(true);

        // Test supported features
        $this->assertTrue(qratt_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(qratt_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(qratt_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(qratt_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertTrue(qratt_supports(FEATURE_GROUPS));

        // Test unsupported features
        $this->assertFalse(qratt_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertFalse(qratt_supports(FEATURE_GRADE_OUTCOMES));
        $this->assertFalse(qratt_supports(FEATURE_GROUPINGS));

        // Test unknown feature
        $this->assertNull(qratt_supports('unknown_feature'));
    }

    /**
     * Test qratt_add_instance function
     */
    public function test_qratt_add_instance() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test course
        $course = $this->getDataGenerator()->create_course();

        // Create qratt data
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test QR Attendance';
        $qratt->intro = 'Test intro';
        $qratt->introformat = FORMAT_HTML;
        $qratt->semester = 'Genap 2024/2025';
        $qratt->department = 'Computer Science';

        // Add instance
        $id = qratt_add_instance($qratt);

        // Verify record was created
        $this->assertNotEmpty($id);
        $record = $DB->get_record('qratt', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Test QR Attendance', $record->name);
        $this->assertEquals($course->id, $record->course);
        $this->assertNotEmpty($record->timecreated);
        $this->assertNotEmpty($record->timemodified);
    }

    /**
     * Test qratt_update_instance function
     */
    public function test_qratt_update_instance() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test course
        $course = $this->getDataGenerator()->create_course();

        // Create qratt instance
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Original Name';
        $qratt->intro = 'Original intro';
        $qratt->introformat = FORMAT_HTML;
        
        $id = qratt_add_instance($qratt);
        $originaltime = $DB->get_field('qratt', 'timemodified', ['id' => $id]);

        // Wait a second to ensure timemodified changes
        $this->waitForSecond();

        // Update instance
        $qratt->instance = $id;
        $qratt->name = 'Updated Name';
        $result = qratt_update_instance($qratt);

        // Verify update
        $this->assertTrue($result);
        $record = $DB->get_record('qratt', ['id' => $id]);
        $this->assertEquals('Updated Name', $record->name);
        $this->assertGreaterThan($originaltime, $record->timemodified);
    }

    /**
     * Test qratt_delete_instance function
     */
    public function test_qratt_delete_instance() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test data
        $course = $this->getDataGenerator()->create_course();
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test QR Attendance';
        $qratt->intro = 'Test intro';
        $qratt->introformat = FORMAT_HTML;
        
        $qrattid = qratt_add_instance($qratt);

        // Create meetings
        $meeting1 = new \stdClass();
        $meeting1->qrattid = $qrattid;
        $meeting1->meetingnumber = 1;
        $meeting1->topic = 'Meeting 1';
        $meeting1->meetingdate = time();
        $meeting1->status = QRATT_MEETING_INACTIVE;
        $meeting1->timecreated = time();
        $meeting1->timemodified = time();
        $meetingid1 = $DB->insert_record('qratt_meetings', $meeting1);

        $meeting2 = new \stdClass();
        $meeting2->qrattid = $qrattid;
        $meeting2->meetingnumber = 2;
        $meeting2->topic = 'Meeting 2';
        $meeting2->meetingdate = time();
        $meeting2->status = QRATT_MEETING_INACTIVE;
        $meeting2->timecreated = time();
        $meeting2->timemodified = time();
        $meetingid2 = $DB->insert_record('qratt_meetings', $meeting2);

        // Create attendance records
        $user = $this->getDataGenerator()->create_user();
        $attendance = new \stdClass();
        $attendance->meetingid = $meetingid1;
        $attendance->userid = $user->id;
        $attendance->status = QRATT_STATUS_PRESENT;
        $attendance->scantime = time();
        $attendance->timecreated = time();
        $attendance->timemodified = time();
        $DB->insert_record('qratt_attendance', $attendance);

        // Delete instance
        $result = qratt_delete_instance($qrattid);
        $this->assertTrue($result);

        // Verify cascade deletion
        $this->assertFalse($DB->record_exists('qratt', ['id' => $qrattid]));
        $this->assertFalse($DB->record_exists('qratt_meetings', ['qrattid' => $qrattid]));
        $this->assertFalse($DB->record_exists('qratt_attendance', ['meetingid' => $meetingid1]));
        $this->assertFalse($DB->record_exists('qratt_attendance', ['meetingid' => $meetingid2]));

        // Test deleting non-existent instance
        $result = qratt_delete_instance(99999);
        $this->assertFalse($result);
    }

    /**
     * Test qratt_generate_qr_code function
     */
    public function test_qratt_generate_qr_code() {
        global $CFG;
        $this->resetAfterTest(true);

        $meetingid = 123;
        $expiry = time() + 60;

        $qrcode = qratt_generate_qr_code($meetingid, $expiry);

        // Verify QR code contains expected components
        $this->assertStringContainsString($CFG->wwwroot, $qrcode);
        $this->assertStringContainsString('/mod/qratt/scan.php', $qrcode);
        $this->assertStringContainsString('token=', $qrcode);
        $this->assertStringContainsString('meeting=' . $meetingid, $qrcode);

        // Verify token is MD5 hash (32 characters)
        preg_match('/token=([a-f0-9]{32})/', $qrcode, $matches);
        $this->assertNotEmpty($matches);
        $this->assertEquals(32, strlen($matches[1]));
    }

    /**
     * Test qratt_get_encryption_key function
     */
    public function test_qratt_get_encryption_key() {
        global $CFG;
        $this->resetAfterTest(true);

        // Test with custom encryption key
        set_config('encryptionkey', 'test_custom_key', 'mod_qratt');
        $key = qratt_get_encryption_key();
        $this->assertEquals('test_custom_key', $key);

        // Test fallback to password salt
        set_config('encryptionkey', '', 'mod_qratt');
        $key = qratt_get_encryption_key();
        if (isset($CFG->passwordsaltmain)) {
            $this->assertEquals($CFG->passwordsaltmain, $key);
        } else {
            $this->assertEquals('qratt_default_salt', $key);
        }
    }

    /**
     * Test qratt_filter_students_only function
     */
    public function test_qratt_filter_students_only() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test course
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Create users with different roles
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $editingteacher = $this->getDataGenerator()->create_user();

        // Enroll users
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($editingteacher->id, $course->id, 'editingteacher');

        $users = [$student1, $student2, $teacher, $editingteacher];

        // Filter to get only students
        $filteredstudents = qratt_filter_students_only($users, $context);

        // Verify only students are returned
        $this->assertCount(2, $filteredstudents);
        $this->assertArrayHasKey($student1->id, $filteredstudents);
        $this->assertArrayHasKey($student2->id, $filteredstudents);
        $this->assertArrayNotHasKey($teacher->id, $filteredstudents);
        $this->assertArrayNotHasKey($editingteacher->id, $filteredstudents);
    }

    /**
     * Test qratt_get_user_statistics function
     */
    public function test_qratt_get_user_statistics() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test data
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test QR Attendance';
        $qratt->intro = 'Test intro';
        $qratt->introformat = FORMAT_HTML;
        $qrattid = qratt_add_instance($qratt);

        // Create 5 meetings
        for ($i = 1; $i <= 5; $i++) {
            $meeting = new \stdClass();
            $meeting->qrattid = $qrattid;
            $meeting->meetingnumber = $i;
            $meeting->topic = "Meeting $i";
            $meeting->meetingdate = time();
            $meeting->status = QRATT_MEETING_INACTIVE;
            $meeting->timecreated = time();
            $meeting->timemodified = time();
            $meetingids[] = $DB->insert_record('qratt_meetings', $meeting);
        }

        // Create attendance records: 2 present, 1 late, 1 excused, 1 absent (no record)
        $statuses = [QRATT_STATUS_PRESENT, QRATT_STATUS_PRESENT, QRATT_STATUS_LATE, QRATT_STATUS_EXCUSED];
        foreach ($statuses as $index => $status) {
            $attendance = new \stdClass();
            $attendance->meetingid = $meetingids[$index];
            $attendance->userid = $user->id;
            $attendance->status = $status;
            $attendance->scantime = time();
            $attendance->timecreated = time();
            $attendance->timemodified = time();
            $DB->insert_record('qratt_attendance', $attendance);
        }

        // Get statistics
        $stats = qratt_get_user_statistics($qrattid, $user->id);

        // Verify statistics
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['present']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(1, $stats['excused']);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(40.0, $stats['percentage']); // 2/5 * 100 = 40%
    }

    /**
     * Test qratt_get_institution_info function
     */
    public function test_qratt_get_institution_info() {
        $this->resetAfterTest(true);

        // Set institution configuration
        set_config('institutionname', 'Test University', 'mod_qratt');
        set_config('institutionaddress', '123 Test Street', 'mod_qratt');
        set_config('institutionphone', '123-456-7890', 'mod_qratt');
        set_config('institutionfax', '098-765-4321', 'mod_qratt');
        set_config('includeinstitutioninfo', 1, 'mod_qratt');
        set_config('includelogoinreports', 1, 'mod_qratt');

        $info = qratt_get_institution_info();

        $this->assertEquals('Test University', $info->name);
        $this->assertEquals('123 Test Street', $info->address);
        $this->assertEquals('123-456-7890', $info->phone);
        $this->assertEquals('098-765-4321', $info->fax);
        $this->assertTrue($info->includeinreports);
        $this->assertTrue($info->includelogo);
    }

    /**
     * Test qratt_user_outline function
     */
    // public function test_qratt_user_outline() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $user = $this->getDataGenerator()->create_user();

    //     $qratt = new \stdClass();
    //     $qratt->course = $course->id;
    //     $qratt->name = 'Test Attendance';
    //     $qratt->intro = 'Test';
    //     $qratt->introformat = FORMAT_HTML;
    //     $qrattid = qratt_add_instance($qratt);

    //     // Create 5 meetings
    //     for ($i = 1; $i <= 5; $i++) {
    //         $meeting = new \stdClass();
    //         $meeting->qrattid = $qrattid;
    //         $meeting->meetingnumber = $i;
    //         $meeting->topic = "Meeting $i";
    //         $meeting->meetingdate = time();
    //         $meeting->status = QRATT_MEETING_ENDED;
    //         $meeting->timecreated = time();
    //         $meeting->timemodified = time();
    //         $meetingid = $DB->insert_record('qratt_meetings', $meeting);

    //         // User attends 3 out of 5
    //         if ($i <= 3) {
    //             $attendance = new \stdClass();
    //             $attendance->meetingid = $meetingid;
    //             $attendance->userid = $user->id;
    //             $attendance->status = QRATT_STATUS_PRESENT;
    //             $attendance->scantime = time();
    //             $attendance->timecreated = time();
    //             $attendance->timemodified = time();
    //             $DB->insert_record('qratt_attendance', $attendance);
    //         }
    //     }

    //     $cm = get_coursemodule_from_instance('qratt', $qrattid, $course->id);
    //     $qrattobj = $DB->get_record('qratt', ['id' => $qrattid]);

    //     $result = qratt_user_outline($course, $user, $cm, $qrattobj);

    //     $this->assertNotEmpty($result);
    //     $this->assertObjectHasProperty('info', $result);
    //     $this->assertObjectHasProperty('time', $result);
    //     $this->assertStringContainsString('3', $result->info); // 3 present
    //     $this->assertStringContainsString('5', $result->info); // out of 5 total
    // }

    // /**
    //  * Test qratt_user_complete function with meetings
    //  */
    // public function test_qratt_user_complete_output() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $user = $this->getDataGenerator()->create_user();

    //     $qratt = new \stdClass();
    //     $qratt->course = $course->id;
    //     $qratt->name = 'Test Attendance';
    //     $qratt->intro = 'Test';
    //     $qratt->introformat = FORMAT_HTML;
    //     $qrattid = qratt_add_instance($qratt);

    //     // Create meeting with attendance
    //     $meeting = new \stdClass();
    //     $meeting->qrattid = $qrattid;
    //     $meeting->meetingnumber = 1;
    //     $meeting->topic = 'Test Meeting';
    //     $meeting->meetingdate = time();
    //     $meeting->status = QRATT_MEETING_ENDED;
    //     $meeting->timecreated = time();
    //     $meeting->timemodified = time();
    //     $meetingid = $DB->insert_record('qratt_meetings', $meeting);

    //     $attendance = new \stdClass();
    //     $attendance->meetingid = $meetingid;
    //     $attendance->userid = $user->id;
    //     $attendance->status = QRATT_STATUS_PRESENT;
    //     $attendance->scantime = time();
    //     $attendance->timecreated = time();
    //     $attendance->timemodified = time();
    //     $DB->insert_record('qratt_attendance', $attendance);

    //     $cm = get_coursemodule_from_instance('qratt', $qrattid, $course->id);
    //     $qrattobj = $DB->get_record('qratt', ['id' => $qrattid]);

    //     // Capture output
    //     ob_start();
    //     qratt_user_complete($course, $user, $cm, $qrattobj);
    //     $output = ob_get_clean();

    //     $this->assertNotEmpty($output);
    //     $this->assertStringContainsString('Test Meeting', $output);
    //     // Case-insensitive check for 'present'
    //     $this->assertMatchesRegularExpression('/present/i', $output);
    // }

    // /**
    //  * Test qratt_user_complete function with no meetings
    //  */
    // public function test_qratt_user_complete_no_meetings() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $user = $this->getDataGenerator()->create_user();

    //     $qratt = new \stdClass();
    //     $qratt->course = $course->id;
    //     $qratt->name = 'Test Attendance';
    //     $qratt->intro = 'Test';
    //     $qratt->introformat = FORMAT_HTML;
    //     $qrattid = qratt_add_instance($qratt);

    //     $cm = get_coursemodule_from_instance('qratt', $qrattid, $course->id);
    //     $qrattobj = $DB->get_record('qratt', ['id' => $qrattid]);

    //     // Capture output
    //     ob_start();
    //     qratt_user_complete($course, $user, $cm, $qrattobj);
    //     $output = ob_get_clean();

    //     // Should show no meetings message
    //     $this->assertMatchesRegularExpression('/no.*meeting/i', $output);
    // }

    // /**
    //  * Test qratt_generate_qr_code with invalid meeting ID
    //  */
    // public function test_qratt_generate_qr_code_with_invalid_meeting_id() {
    //     $this->resetAfterTest(true);

    //     // Test with negative meeting ID
    //     $qrcode = qratt_generate_qr_code(-1, time() + 60);
    //     $this->assertNotEmpty($qrcode);
    //     $this->assertStringContainsString('meeting=-1', $qrcode);
    // }

    // /**
    //  * Test qratt_generate_qr_code with past expiry
    //  */
    // public function test_qratt_generate_qr_code_with_past_expiry() {
    //     $this->resetAfterTest(true);

    //     // Test with expired timestamp
    //     $past = time() - 3600;
    //     $qrcode = qratt_generate_qr_code(123, $past);
    //     $this->assertNotEmpty($qrcode);
    //     // QR code should still generate, validation happens at scan time
    //     $this->assertStringContainsString('/mod/qratt/scan.php', $qrcode);
    // }

    // /**
    //  * Test qratt_get_user_statistics with no meetings
    //  */
    // public function test_qratt_get_user_statistics_with_no_meetings() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $user = $this->getDataGenerator()->create_user();

    //     $qratt = new \stdClass();
    //     $qratt->course = $course->id;
    //     $qratt->name = 'Empty Attendance';
    //     $qratt->intro = 'Test';
    //     $qratt->introformat = FORMAT_HTML;
    //     $qrattid = qratt_add_instance($qratt);

    //     $stats = qratt_get_user_statistics($qrattid, $user->id);

    //     $this->assertEquals(0, $stats['total']);
    //     $this->assertEquals(0, $stats['present']);
    //     $this->assertEquals(0, $stats['late']);
    //     $this->assertEquals(0, $stats['excused']);
    //     $this->assertEquals(0, $stats['absent']);
    //     $this->assertEquals(0, $stats['percentage']); // Should handle division by zero
    // }

    // /**
    //  * Test qratt_get_user_statistics with all attendance statuses
    //  */
    // public function test_qratt_get_user_statistics_with_all_statuses() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $user = $this->getDataGenerator()->create_user();

    //     $qratt = new \stdClass();
    //     $qratt->course = $course->id;
    //     $qratt->name = 'Test Attendance';
    //     $qratt->intro = 'Test';
    //     $qratt->introformat = FORMAT_HTML;
    //     $qrattid = qratt_add_instance($qratt);

    //     // Create 4 meetings with different statuses
    //     $statuses = [QRATT_STATUS_PRESENT, QRATT_STATUS_LATE, QRATT_STATUS_EXCUSED, QRATT_STATUS_ABSENT];

    //     foreach ($statuses as $index => $status) {
    //         $meeting = new \stdClass();
    //         $meeting->qrattid = $qrattid;
    //         $meeting->meetingnumber = $index + 1;
    //         $meeting->topic = "Meeting " . ($index + 1);
    //         $meeting->meetingdate = time();
    //         $meeting->status = QRATT_MEETING_ENDED;
    //         $meeting->timecreated = time();
    //         $meeting->timemodified = time();
    //         $meetingid = $DB->insert_record('qratt_meetings', $meeting);

    //         // ABSENT doesn't get a record
    //         if ($status != QRATT_STATUS_ABSENT) {
    //             $attendance = new \stdClass();
    //             $attendance->meetingid = $meetingid;
    //             $attendance->userid = $user->id;
    //             $attendance->status = $status;
    //             $attendance->scantime = time();
    //             $attendance->timecreated = time();
    //             $attendance->timemodified = time();
    //             $DB->insert_record('qratt_attendance', $attendance);
    //         }
    //     }

    //     $stats = qratt_get_user_statistics($qrattid, $user->id);

    //     $this->assertEquals(4, $stats['total']);
    //     $this->assertEquals(1, $stats['present']);
    //     $this->assertEquals(1, $stats['late']);
    //     $this->assertEquals(1, $stats['excused']);
    //     $this->assertEquals(1, $stats['absent']);
    //     $this->assertEquals(25.0, $stats['percentage']); // 1/4 * 100 = 25%
    // }

    // /**
    //  * Test qratt_filter_students_only with empty array
    //  */
    // public function test_qratt_filter_students_only_with_empty_array() {
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $context = \context_course::instance($course->id);

    //     $result = qratt_filter_students_only([], $context);

    //     $this->assertIsArray($result);
    //     $this->assertEmpty($result);
    // }

    // /**
    //  * Test qratt_filter_students_only with mixed user roles
    //  */
    // public function test_qratt_filter_students_only_with_mixed_users() {
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $context = \context_course::instance($course->id);

    //     // Create users with various roles
    //     $student1 = $this->getDataGenerator()->create_user();
    //     $student2 = $this->getDataGenerator()->create_user();
    //     $teacher = $this->getDataGenerator()->create_user();
    //     $manager = $this->getDataGenerator()->create_user();

    //     $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
    //     $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
    //     $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
    //     $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'manager');

    //     $allusers = [$student1, $student2, $teacher, $manager];
    //     $filtered = qratt_filter_students_only($allusers, $context);

    //     $this->assertCount(2, $filtered);
    //     $this->assertArrayHasKey($student1->id, $filtered);
    //     $this->assertArrayHasKey($student2->id, $filtered);
    //     $this->assertArrayNotHasKey($teacher->id, $filtered);
    //     $this->assertArrayNotHasKey($manager->id, $filtered);
    // }

    // /**
    //  * Test qratt_get_institution_logo_url with no logo
    //  */
    // public function test_qratt_get_institution_logo_url_no_logo() {
    //     $this->resetAfterTest(true);

    //     $url = qratt_get_institution_logo_url();

    //     // When no logo is configured, should return null
    //     $this->assertNull($url);
    // }

    /**
     * Helper function to wait for a second
     */
    public function waitForSecond() {
        $now = time();
        while (time() == $now) {
            usleep(100000); // Sleep for 0.1 seconds
        }
    }
}
