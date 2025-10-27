# QR Attendance Plugin - PHPUnit Tests

This directory contains unit and integration tests for the mod_qratt Moodle plugin.

## Test Structure

```
tests/
├── lib_test.php                    # Unit tests for lib.php functions
├── attendance_workflow_test.php    # Integration tests for attendance workflow
├── generator/
│   └── lib.php                     # Test data generator
└── README.md                       # This file
```

## Prerequisites

1. **PHPUnit installed in your Moodle installation**
   ```bash
   php admin/tool/phpunit/cli/init.php
   ```

2. **Configure phpunit in Moodle config.php**
   ```php
   $CFG->phpunit_prefix = 'phpu_';
   $CFG->phpunit_dataroot = '/path/to/moodledata_phpu';
   ```

## Running Tests

### Run all mod_qratt tests
```bash
cd /path/to/moodle
vendor/bin/phpunit --testsuite mod_qratt_testsuite
```

### Run specific test file
```bash
# Unit tests for lib.php
vendor/bin/phpunit mod/qratt/tests/lib_test.php

# Integration tests for attendance workflow
vendor/bin/phpunit mod/qratt/tests/attendance_workflow_test.php
```

### Run specific test method
```bash
vendor/bin/phpunit --filter test_qratt_add_instance mod/qratt/tests/lib_test.php
vendor/bin/phpunit --filter test_complete_attendance_workflow mod/qratt/tests/attendance_workflow_test.php
```

### Run with coverage (requires xdebug)
```bash
vendor/bin/phpunit --coverage-html coverage/ mod/qratt/tests/
```

### Run with verbose output
```bash
vendor/bin/phpunit --testdox mod/qratt/tests/lib_test.php
```

## Test Coverage

### Unit Tests (lib_test.php)
- ✅ Module feature support (`qratt_supports`)
- ✅ Instance creation (`qratt_add_instance`)
- ✅ Instance update (`qratt_update_instance`)
- ✅ Instance deletion with cascade (`qratt_delete_instance`)
- ✅ QR code generation (`qratt_generate_qr_code`)
- ✅ Encryption key management (`qratt_get_encryption_key`)
- ✅ Student role filtering (`qratt_filter_students_only`)
- ✅ User statistics calculation (`qratt_get_user_statistics`)
- ✅ Institution information (`qratt_get_institution_info`)

### Integration Tests (attendance_workflow_test.php)
- ✅ Complete attendance workflow (meeting → QR → scan → attendance)
- ✅ QR token validation and expiry
- ✅ Meeting status transitions (INACTIVE → ACTIVE → ENDED)
- ✅ Late attendance threshold logic
- ✅ Duplicate attendance prevention
- ✅ Statistics calculation with multiple meetings
- ✅ Role-based access control (student/teacher/editingteacher)
- ✅ QR code expiry handling

## Test Data Generator

The test generator (`tests/generator/lib.php`) provides methods to create test fixtures:

### Usage Examples

```php
// Get the generator
$generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');

// Create a qratt instance
$qratt = $generator->create_instance([
    'course' => $course->id,
    'name' => 'My QR Attendance'
]);

// Create a meeting
$meeting = $generator->create_meeting([
    'qrattid' => $qratt->id,
    'topic' => 'Week 1 Meeting',
    'status' => QRATT_MEETING_ACTIVE
]);

// Create an attendance record
$attendance = $generator->create_attendance([
    'meetingid' => $meeting->id,
    'userid' => $student->id,
    'status' => QRATT_STATUS_PRESENT
]);

// Create a complete test scenario
$scenario = $generator->create_test_scenario([
    'num_students' => 5,
    'num_meetings' => 10,
    'create_attendance' => true
]);
```

## Writing New Tests

### Example Unit Test

```php
namespace mod_qratt;

class my_test extends \advanced_testcase {
    
    public function test_my_feature() {
        global $DB;
        $this->resetAfterTest(true);
        
        // Arrange
        $course = $this->getDataGenerator()->create_course();
        $qratt = new \stdClass();
        $qratt->course = $course->id;
        $qratt->name = 'Test';
        
        // Act
        $id = qratt_add_instance($qratt);
        
        // Assert
        $this->assertNotEmpty($id);
        $record = $DB->get_record('qratt', ['id' => $id]);
        $this->assertEquals('Test', $record->name);
    }
}
```

### Best Practices

1. **Always use `resetAfterTest(true)`** to ensure test isolation
2. **Use the data generator** for creating test data
3. **Follow Arrange-Act-Assert pattern**
4. **Test one thing per test method**
5. **Use descriptive test method names** starting with `test_`
6. **Clean up after tests** (automatic with `resetAfterTest`)
7. **Mock external dependencies** when possible

## Common Issues

### Issue: "Table 'phpu_qratt' doesn't exist"
**Solution:** Reinitialize PHPUnit
```bash
php admin/tool/phpunit/cli/init.php
```

### Issue: "Cannot find data generator"
**Solution:** Check that `tests/generator/lib.php` exists and extends `testing_module_generator`

### Issue: Tests fail with database errors
**Solution:** Ensure test database is properly configured and initialized

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run PHPUnit tests
  run: |
    php admin/tool/phpunit/cli/init.php
    vendor/bin/phpunit mod/qratt/tests/
```

## Additional Resources

- [Moodle PHPUnit Documentation](https://docs.moodle.org/dev/PHPUnit)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Moodle Testing Guide](https://docs.moodle.org/dev/Testing)

## Contact

For issues or questions about these tests, contact the QR Attendance Team.
