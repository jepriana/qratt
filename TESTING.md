# Testing Guide for QR Attendance Plugin

## ✅ Setup Complete!

Your PHPUnit testing environment is now fully configured and all tests are passing.

## Test Results

```
✔ 17 tests
✔ 96 assertions
✔ 100% passing
```

## Running Tests

### From Moodle Directory

```bash
cd /Applications/MAMP/htdocs/moodle500

# Run all qratt tests
vendor/bin/phpunit mod/qratt/tests/unit_test.php mod/qratt/tests/integration_workflow_test.php

# Run unit tests only
vendor/bin/phpunit mod/qratt/tests/unit_test.php

# Run integration tests only
vendor/bin/phpunit mod/qratt/tests/integration_workflow_test.php

# Run with detailed output
vendor/bin/phpunit mod/qratt/tests/unit_test.php --testdox

# Run with verbose assertions
vendor/bin/phpunit mod/qratt/tests/unit_test.php -v
```

## Configuration Files

### Moodle config.php
Location: `/Applications/MAMP/htdocs/moodle500/config.php`

PHPUnit settings:
```php
$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/Applications/MAMP/data/moodle500_phpunit';
```

### PHP Settings
Location: `/opt/homebrew/etc/php/8.4/php.ini`

Required settings:
- `extension=intl` ✅ (via `/opt/homebrew/etc/php/8.4/conf.d/ext-intl.ini`)
- `max_input_vars = 5000` ✅

## Test Coverage

### Unit Tests (`unit_test.php`) - 9 tests
- ✅ Module feature support
- ✅ Instance CRUD operations
- ✅ QR code generation
- ✅ Token validation
- ✅ Encryption key management
- ✅ Student role filtering
- ✅ Statistics calculation
- ✅ Institution information

### Integration Tests (`integration_workflow_test.php`) - 8 tests
- ✅ Complete attendance workflow
- ✅ QR token validation with expiry
- ✅ Meeting status transitions
- ✅ Late attendance threshold logic
- ✅ Duplicate attendance prevention
- ✅ Multi-meeting statistics
- ✅ Role-based access control
- ✅ QR code expiry handling

## Updating Tests

After making changes to your plugin:

1. **Copy plugin to Moodle:**
   ```bash
   cp -r /Users/jepriana/Workspaces/STMM/Moodle/qratt_git/* /Applications/MAMP/htdocs/moodle500/mod/qratt/
   ```

2. **Reinitialize PHPUnit** (if database schema changed):
   ```bash
   cd /Applications/MAMP/htdocs/moodle500
   php admin/tool/phpunit/cli/init.php
   ```

3. **Run tests:**
   ```bash
   vendor/bin/phpunit mod/qratt/tests/unit_test.php --testdox
   ```

## Test Generator

Use the test generator to create test data easily:

```php
$generator = $this->getDataGenerator()->get_plugin_generator('mod_qratt');

// Create instance
$qratt = $generator->create_instance(['course' => $course->id]);

// Create meeting
$meeting = $generator->create_meeting(['qrattid' => $qratt->id]);

// Create attendance
$attendance = $generator->create_attendance([
    'meetingid' => $meeting->id,
    'userid' => $student->id
]);

// Create complete scenario
$scenario = $generator->create_test_scenario([
    'num_students' => 5,
    'num_meetings' => 10,
    'create_attendance' => true
]);
```

## Troubleshooting

### "Table not found" error
```bash
php admin/tool/phpunit/cli/init.php
```

### "Cannot connect to database"
Check MAMP is running and config.php has correct settings:
- Port: 8889
- Socket: /Applications/MAMP/tmp/mysql/mysql.sock

### Tests fail after code changes
1. Copy updated code to Moodle directory
2. Reinitialize if schema changed
3. Run tests again

## Continuous Integration

Add to your CI/CD pipeline:
```yaml
- name: Run PHPUnit Tests
  run: |
    cd /Applications/MAMP/htdocs/moodle500
    php admin/tool/phpunit/cli/init.php
    vendor/bin/phpunit mod/qratt/tests/
```

## Next Steps

- Add more test cases for edge cases
- Test error handling scenarios
- Add performance tests
- Test with different user roles
- Test report generation
- Test QR code scanning edge cases

For more information, see `tests/README.md`
