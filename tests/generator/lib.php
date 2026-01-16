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
 * Generator for mod_qratt
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/qratt/lib.php');

/**
 * QR Attendance module test data generator
 *
 * @package    mod_qratt
 * @category   test
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qratt_generator extends testing_module_generator {

    /**
     * Create a new instance of qratt module
     *
     * @param array|stdClass $record Data for module being generated
     * @param null|array $options General options for course module
     * @return stdClass Record from qratt table with additional field cmid
     */
    public function create_instance($record = null, ?array $options = null) {
        global $DB, $CFG;

        $record = (object)(array)$record;

        // Set default values
        if (!isset($record->course)) {
            throw new coding_exception('Module generator requires $record->course');
        }
        if (!isset($record->name)) {
            $record->name = 'Test QR Attendance ' . time();
        }
        if (!isset($record->intro)) {
            $record->intro = 'Test QR Attendance intro';
        }
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_HTML;
        }
        if (!isset($record->semester)) {
            $record->semester = 'Genap 2024/2025';
        }
        if (!isset($record->department)) {
            $record->department = 'Test Department';
        }
        if (!isset($record->studyprogram)) {
            $record->studyprogram = 'Test Study Program';
        }
        if (!isset($record->subject)) {
            $record->subject = 'Test Subject';
        }
        if (!isset($record->credits)) {
            $record->credits = 3;
        }
        if (!isset($record->classname)) {
            $record->classname = 'Test Class';
        }

        $record->timecreated = time();
        $record->timemodified = time();

        // Let parent create the course module first
        $instance = parent::create_instance($record, $options);
        
        return $instance;
    }

    /**
     * Create a meeting for a qratt instance
     *
     * @param array|stdClass $record Data for meeting
     * @return stdClass Meeting record
     */
    public function create_meeting($record = null) {
        global $DB;

        $record = (object)(array)$record;

        if (!isset($record->qrattid)) {
            throw new coding_exception('Meeting generator requires $record->qrattid');
        }
        if (!isset($record->meetingnumber)) {
            // Find the next meeting number
            $lastmeeting = $DB->get_record_sql(
                'SELECT MAX(meetingnumber) as maxnum FROM {qratt_meetings} WHERE qrattid = ?',
                [$record->qrattid]
            );
            $record->meetingnumber = $lastmeeting && $lastmeeting->maxnum ? $lastmeeting->maxnum + 1 : 1;
        }
        if (!isset($record->topic)) {
            $record->topic = 'Test Meeting ' . $record->meetingnumber;
        }
        if (!isset($record->meetingdate)) {
            $record->meetingdate = time();
        }
        if (!isset($record->starttime)) {
            $record->starttime = $record->meetingdate;
        }
        if (!isset($record->endtime)) {
            $record->endtime = $record->starttime + 3600; // 1 hour default
        }
        if (!isset($record->location)) {
            $record->location = 'Test Room';
        }
        if (!isset($record->activeduration)) {
            $record->activeduration = 900; // 15 minutes default
        }
        if (!isset($record->status)) {
            $record->status = QRATT_MEETING_INACTIVE;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }

        // Generate QR code if meeting is active
        if ($record->status == QRATT_MEETING_ACTIVE) {
            if (!isset($record->qrexpiry)) {
                $record->qrexpiry = time() + 60; // 60 seconds default
            }
            $record->qrcode = qratt_generate_qr_code($record->qrattid, $record->qrexpiry);
        }

        $record->id = $DB->insert_record('qratt_meetings', $record);

        return $record;
    }

    /**
     * Create an attendance record for a meeting
     *
     * @param array|stdClass $record Data for attendance
     * @return stdClass Attendance record
     */
    public function create_attendance($record = null) {
        global $DB;

        $record = (object)(array)$record;

        if (!isset($record->meetingid)) {
            throw new coding_exception('Attendance generator requires $record->meetingid');
        }
        if (!isset($record->userid)) {
            throw new coding_exception('Attendance generator requires $record->userid');
        }
        if (!isset($record->status)) {
            $record->status = QRATT_STATUS_PRESENT;
        }
        if (!isset($record->scantime)) {
            $record->scantime = time();
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }

        $record->id = $DB->insert_record('qratt_attendance', $record);

        return $record;
    }

    /**
     * Create a complete test scenario with course, users, qratt, meetings, and attendance
     *
     * @param array $options Options for test scenario
     * @return stdClass Complete test scenario data
     */
    public function create_test_scenario(array $options = []) {
        global $DB;

        $scenario = new stdClass();

        // Create course
        $scenario->course = $this->datagenerator->create_course();

        // Create users
        $scenario->teacher = $this->datagenerator->create_user();
        $numstudents = isset($options['num_students']) ? $options['num_students'] : 3;
        $scenario->students = [];
        for ($i = 0; $i < $numstudents; $i++) {
            $scenario->students[] = $this->datagenerator->create_user();
        }

        // Enroll users
        $this->datagenerator->enrol_user($scenario->teacher->id, $scenario->course->id, 'editingteacher');
        foreach ($scenario->students as $student) {
            $this->datagenerator->enrol_user($student->id, $scenario->course->id, 'student');
        }

        // Create qratt instance
        $scenario->qratt = $this->create_instance([
            'course' => $scenario->course->id,
            'name' => 'Scenario QR Attendance',
        ]);

        // Create meetings
        $nummeetings = isset($options['num_meetings']) ? $options['num_meetings'] : 5;
        $scenario->meetings = [];
        for ($i = 0; $i < $nummeetings; $i++) {
            $scenario->meetings[] = $this->create_meeting([
                'qrattid' => $scenario->qratt->id,
                'meetingnumber' => $i + 1,
                'topic' => "Meeting " . ($i + 1),
                'meetingdate' => time() + ($i * 86400), // One day apart
                'status' => QRATT_MEETING_ENDED,
            ]);
        }

        // Create attendance records if requested
        if (isset($options['create_attendance']) && $options['create_attendance']) {
            $scenario->attendances = [];
            foreach ($scenario->meetings as $meeting) {
                foreach ($scenario->students as $student) {
                    // Random attendance status
                    $statuses = [QRATT_STATUS_PRESENT, QRATT_STATUS_LATE, QRATT_STATUS_ABSENT];
                    $status = $statuses[array_rand($statuses)];
                    
                    if ($status != QRATT_STATUS_ABSENT) {
                        $scenario->attendances[] = $this->create_attendance([
                            'meetingid' => $meeting->id,
                            'userid' => $student->id,
                            'status' => $status,
                        ]);
                    }
                }
            }
        }

        return $scenario;
    }
}
