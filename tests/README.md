# QR Attendance Plugin - PHPUnit Tests

This directory contains comprehensive automated tests for the mod_qratt Moodle plugin.

## Quick Status

```
✅ 49 Tests    ✅ 188 Assertions    ✅ 100% Pass Rate    ✅ 100% Coverage
```

## Test Structure

```
tests/
├── unit_test.php                    # 19 unit tests for lib.php functions
├── integration_workflow_test.php    # 13 integration/workflow tests
├── security_test.php                # 11 security & access control tests
├── performance_test.php             # 6 performance & load tests
├── generator/
│   └── lib.php                      # Test data generator
└── README.md                        # This file
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
php vendor/bin/phpunit mod/qratt/tests/
```

**Expected Output:**
```
OK (49 tests, 188 assertions)
Time: 00:11.383, Memory: 83.00 MB
```

### Run specific test file
```bash
# Unit tests (19 tests)
php vendor/bin/phpunit mod/qratt/tests/unit_test.php

# Integration tests (13 tests)
php vendor/bin/phpunit mod/qratt/tests/integration_workflow_test.php

# Security tests (11 tests)
php vendor/bin/phpunit mod/qratt/tests/security_test.php

# Performance tests (6 tests)
php vendor/bin/phpunit mod/qratt/tests/performance_test.php
```

### Run specific test method
```bash
php vendor/bin/phpunit --filter test_qratt_add_instance mod/qratt/tests/unit_test.php
php vendor/bin/phpunit --filter test_complete_attendance_workflow mod/qratt/tests/integration_workflow_test.php
php vendor/bin/phpunit --filter test_qr_generation_performance mod/qratt/tests/performance_test.php
```

### Run with coverage (requires xdebug)
```bash
vendor/bin/phpunit --coverage-html coverage/ mod/qratt/tests/
```

### Run with verbose output
```bash
vendor/bin/phpunit --testdox mod/qratt/tests/unit_test.php
```

## Test Coverage

### Unit Tests (unit_test.php) - 19 tests, 82 assertions

**Core Functions:**
- ✅ Module feature support (`qratt_supports`)
- ✅ Instance CRUD (`qratt_add_instance`, `qratt_update_instance`, `qratt_delete_instance`)
- ✅ QR code generation (`qratt_generate_qr_code`)
- ✅ Encryption key management (`qratt_get_encryption_key`)
- ✅ Student role filtering (`qratt_filter_students_only`)
- ✅ User statistics calculation (`qratt_get_user_statistics`)
- ✅ Institution information (`qratt_get_institution_info`, `qratt_get_institution_logo_url`)
- ✅ User activity reports (`qratt_user_outline`, `qratt_user_complete`)

**Edge Cases:**
- ✅ QR code with invalid meeting ID
- ✅ QR code with past expiry time
- ✅ Statistics with no meetings
- ✅ Statistics with all status types
- ✅ Filter with empty user array
- ✅ Filter with mixed roles

### Integration Tests (integration_workflow_test.php) - 13 tests, 53 assertions

**Workflows:**
- ✅ Complete attendance workflow (meeting → QR → scan → attendance)
- ✅ QR token validation with time windows
- ✅ Meeting status transitions (INACTIVE → ACTIVE → ENDED)
- ✅ Late attendance threshold logic
- ✅ Attendance statistics calculation

**Teacher Functions:**
- ✅ Manage activities (create/update/delete)
- ✅ Activate/deactivate meetings
- ✅ Display dynamic QR codes
- ✅ Change attendance status
- ✅ Access reports

**Student Functions:**
- ✅ Scan QR codes
- ✅ Receive status after scan
- ✅ View attendance history

### Security Tests (security_test.php) - 11 tests, 42 assertions

**Access Control:**
- ✅ QR codes rejected after session ends
- ✅ Unregistered students denied access
- ✅ Cross-teacher access denied
- ✅ Cross-student data access denied
- ✅ API manipulation prevented

**Data Protection:**
- ✅ QR codes unique per session
- ✅ QR codes cannot be reused
- ✅ Role-based access restrictions
- ✅ Comprehensive role verification
- ✅ Duplicate attendance prevention
- ✅ QR expiry validation

### Performance Tests (performance_test.php) - 6 tests, 11 assertions

**Performance Benchmarks:**
- ✅ QR generation < 3s (actual: < 0.01s)
- ✅ Scan validation < 5s (actual: < 0.01s)
- ✅ Concurrent requests (50+ handled in ~7s)
- ✅ QR refresh overhead (< 0.01s per refresh)
- ✅ Statistics calculation (< 0.1s for 100 meetings)
- ✅ Large class handling (200+ students)

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
8. **Test edge cases** (empty inputs, invalid IDs, null values)
9. **Include security tests** for access control and data protection
10. **Add performance tests** for critical operations

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

## Test Organization

### When to Add Tests

**Unit Tests (`unit_test.php`):**
- Testing individual functions in isolation
- Minimal database setup required
- Fast execution (< 0.1s per test)
- Pure logic testing

**Integration Tests (`integration_workflow_test.php`):**
- End-to-end workflows
- Multi-step business processes
- User functional requirements
- Component integration

**Security Tests (`security_test.php`):**
- Access control validation
- Data protection mechanisms
- Role/capability verification
- Security constraints

**Performance Tests (`performance_test.php`):**
- Speed/performance requirements
- Load and scalability testing
- Concurrent request handling
- Benchmark measurements

## Documentation

For complete testing documentation, see:
- `docs/TESTING_DOCUMENTATION.md` - Complete testing guide
- `docs/FINAL_TEST_RESULTS.md` - Latest test results
- `docs/TEST_ORGANIZATION_FINAL.md` - Test structure reference
- `docs/REQUIREMENTS_VERIFICATION.md` - Requirements validation

## Additional Resources

- [Moodle PHPUnit Documentation](https://docs.moodle.org/dev/PHPUnit)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Moodle Testing Guide](https://docs.moodle.org/dev/Testing)

## Current Status

**Version:** 1.1.0  
**Last Updated:** October 27, 2025  
**Status:** ✅ Production Ready

**Test Results:**
- Total Tests: 49
- Total Assertions: 188
- Pass Rate: 100%
- Function Coverage: 100%
- Requirements Coverage: 92%

## Contact

For issues or questions about these tests, contact the QR Attendance Team.
