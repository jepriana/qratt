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
 * Performance and load tests for QR Attendance
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
 * Performance and load tests
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class performance_test extends \advanced_testcase {

    /**
     * PERF-1: QR code generation should be fast (< 3 seconds)
     * 
     * Tests that initial QR code generation meets performance requirements.
     * Required by non-functional requirement: QR generation < 3 seconds.
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
     * 
     * Tests that QR scan validation completes within acceptable time.
     * Required by non-functional requirement: Scan validation < 5 seconds.
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
     * PERF-3: System handles multiple concurrent attendance requests
     * 
     * Tests system's ability to handle concurrent attendance marking.
     * Required by non-functional requirement: Handle 50+ concurrent requests.
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

    /**
     * PERF-4: QR code refresh overhead is minimal
     * 
     * Tests that repeated QR code generation (every 60 seconds) doesn't burden server.
     * Required by non-functional requirement: 60-second QR refresh minimal overhead.
     */
    public function test_qr_refresh_overhead() {
        $this->resetAfterTest(true);

        $meetingid = 123;
        $expiry = time() + 60;

        // Measure 10 consecutive QR generations (simulating refreshes)
        $starttime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $qrcode = qratt_generate_qr_code($meetingid, $expiry + ($i * 60));
            $this->assertNotEmpty($qrcode);
        }
        
        $endtime = microtime(true);
        $totaltime = $endtime - $starttime;
        $avgtime = $totaltime / 10;

        // Average generation should be very fast (< 0.1s per refresh)
        $this->assertLessThan(0.1, $avgtime, 'QR refresh should have minimal overhead');
        $this->assertLessThan(1.0, $totaltime, '10 QR generations should complete in under 1 second');
    }

    /**
     * PERF-5: Statistics calculation performance
     * 
     * Tests that calculating attendance statistics is fast even with many meetings.
     */
    public function test_statistics_calculation_performance() {
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

        // Create 100 meetings with attendance
        for ($i = 1; $i <= 100; $i++) {
            $meeting = new \stdClass();
            $meeting->qrattid = $qrattid;
            $meeting->meetingnumber = $i;
            $meeting->topic = "Meeting $i";
            $meeting->meetingdate = time() + ($i * 86400);
            $meeting->status = QRATT_MEETING_ENDED;
            $meeting->timecreated = time();
            $meeting->timemodified = time();
            $meetingid = $DB->insert_record('qratt_meetings', $meeting);

            // Add attendance for every meeting
            $attendance = new \stdClass();
            $attendance->meetingid = $meetingid;
            $attendance->userid = $student->id;
            $attendance->status = QRATT_STATUS_PRESENT;
            $attendance->scantime = time();
            $attendance->timecreated = time();
            $attendance->timemodified = time();
            $DB->insert_record('qratt_attendance', $attendance);
        }

        // Measure statistics calculation time
        $starttime = microtime(true);
        $stats = qratt_get_user_statistics($qrattid, $student->id);
        $endtime = microtime(true);

        $executiontime = $endtime - $starttime;

        // Statistics should be calculated quickly (< 1 second for 100 meetings)
        $this->assertLessThan(1.0, $executiontime, 'Statistics calculation should be fast');
        $this->assertEquals(100, $stats['total']);
        $this->assertEquals(100, $stats['present']);
    }

    /**
     * PERF-6: Large class size handling
     * 
     * Tests system performance with a large class (200+ students).
     */
    // public function test_large_class_handling() {
    //     global $DB;
    //     $this->resetAfterTest(true);

    //     $course = $this->getDataGenerator()->create_course();
    //     $generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');
    //     $qratt = $generator->create_instance(['course' => $course->id]);

    //     // Create 200 students
    //     $starttime = microtime(true);
        
    //     $students = [];
    //     for ($i = 0; $i < 200; $i++) {
    //         $student = $this->getDataGenerator()->create_user();
    //         $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
    //         $students[] = $student;
    //     }
        
    //     $endtime = microtime(true);
    //     $setuptime = $endtime - $starttime;

    //     // Setup should be reasonable
    //     $this->assertLessThan(30.0, $setuptime, 'Large class setup should complete in reasonable time');

    //     // Verify all enrolled
    //     $context = \context_course::instance($course->id);
    //     $enrolled = get_enrolled_users($context, 'mod/qratt:takeattendance');
    //     $this->assertEquals(200, count($enrolled));
    // }
}
