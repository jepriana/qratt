# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is **mod_qratt**, a QR Attendance plugin for Moodle 4.5+. It allows lecturers to take student attendance using dynamically generated QR codes that refresh every 60 seconds for security.

**Key Capabilities:**
- Meeting management with multiple attendance sessions per activity
- QR code generation with automatic expiry and refresh
- Real-time attendance tracking with Present/Late/Absent/Excused statuses
- Comprehensive reporting and CSV export
- Multi-language support (English/Indonesian)
- Security features including token validation and enrollment verification

## Development Commands

### Core Development Tasks

**Local Development Setup:**
```bash
# Copy plugin to Moodle installation
cp -r . /path/to/moodle/mod/qratt

# Install/upgrade plugin via Moodle admin interface
# Navigate to Site Administration > Notifications in Moodle
```

**Database Operations:**
```bash
# Plugin uses Moodle's database API - no direct SQL commands needed
# Schema is defined in db/install.xml
# Upgrades handled through version.php and db/upgrade.php (if exists)
```

**Testing and Validation:**
```bash
# Test QR code generation
php -r "
require_once '/path/to/moodle/config.php';
require_once '/path/to/moodle/mod/qratt/lib.php';
echo qratt_generate_qr_code(1, time() + 60);
"

# Validate language strings
php /path/to/moodle/admin/cli/check_lang.php --langdir=lang/

# Check code style (if using Moodle coding standards)
vendor/bin/phpcs --standard=moodle mod/qratt/
```

**Language Development:**
```bash
# Add new language pack
mkdir -p lang/[language_code]
cp lang/en/qratt.php lang/[language_code]/qratt.php
# Then translate strings in the new file
```

## Architecture Overview

### Database Schema (4 core tables)
- **qratt**: Main activity instances
- **qratt_meetings**: Individual meeting sessions within each activity
- **qratt_attendance**: Student attendance records linked to meetings
- **qratt_statuses**: Attendance status definitions (Present=1, Absent=2, Late=3, Excused=4)

### Core Components

**Entry Points:**
- `view.php` - Main activity view with overview and navigation tabs
- `meetings.php` - Meeting management interface for instructors
- `attendance.php` - Manual attendance input with radio buttons for all students
- `qrcode.php` - QR code display with auto-refresh and fullscreen mode
- `scan.php` - QR scanning endpoint that validates tokens and records attendance
- `reports.php` - Attendance reporting and CSV export
- `scanner.php` - Student QR code scanner interface

**Key Libraries:**
- `lib.php` - Core functions including Moodle hooks and attendance logic
- `mod_form.php` - Activity creation/editing form definition

**Meeting Status Flow:**
```
INACTIVE (0) → ACTIVE (1) → ENDED (2)
    ↓           ↓            ↓
 Can activate  QR active    QR expired
              Auto-refresh  Historical
```

### QR Code Security System

**Token Generation Logic:**
```php
// 60-second expiry tokens using MD5 hash
$token = md5($meetingid . $expiry . $salt);
$qrcode_url = $CFG->wwwroot . '/mod/qratt/scan.php?token=' . $token . '&meeting=' . $meetingid;
```

**Validation Process:**
1. Check meeting is active
2. Validate token matches expected hash (allows 2-minute tolerance for timing)
3. Verify user enrollment in course
4. Check attendance capability
5. Prevent duplicate attendance marking
6. Determine Present vs Late status based on timing

### Capability System
```php
'mod/qratt:addinstance'       // Create new QR Attendance activities
'mod/qratt:view'             // View activity (all users)
'mod/qratt:manage'           // Manage meetings and settings
'mod/qratt:takeattendance'   // Students can scan QR codes
'mod/qratt:viewreports'      // View attendance reports
'mod/qratt:manageattendances' // Manual attendance override
'mod/qratt:canbelisted'      // Appear in attendance lists
```

### Attendance Timing Logic
- **Present**: Scanned within meeting start time
- **Late**: Scanned 15+ minutes after meeting start (`$latethreshold = $meetingstart + 900`)
- **Absent**: No scan recorded
- **Excused**: Manually set by instructor

## Development Guidelines

### When Modifying QR Generation
- QR codes expire every 60 seconds (`qrexpiry` field)
- Currently uses external QR service: `https://api.qrserver.com/v1/create-qr-code/`
- For production, consider implementing local QR generation library
- Token validation allows 2-minute window to handle refresh timing issues

### Database Interactions
- All database operations use Moodle's `$DB` global object
- Foreign key relationships enforced through XMLDB schema
- Unique constraints prevent duplicate meeting numbers per activity
- Cascade deletion implemented in `qratt_delete_instance()`

### Frontend Patterns
- Uses Moodle's HTML writer API for all output
- Tabbed navigation pattern for different views
- Responsive CSS with mobile-specific breakpoints
- JavaScript for QR countdown timer and fullscreen mode
- Form validation through Moodle's form API

### Internationalization
- All user-facing strings use `get_string()` with 'qratt' namespace
- Language files in `lang/[locale]/qratt.php`
- Support for RTL languages through Moodle's base CSS

### Security Considerations
- Session key validation for state-changing operations
- Capability checks on all major functions
- Token-based QR validation prevents replay attacks
- Course enrollment verification for attendance marking
- HTML output sanitization through Moodle APIs

## Common Debugging

**QR Code Issues:**
```bash
# Check meeting status and expiry
SELECT id, status, qrexpiry, qrcode FROM mdl_qratt_meetings WHERE id = [meeting_id];

# Verify token generation
# Compare generated token with expected hash using same salt
```

**Attendance Problems:**
```bash
# Check user attendance records
SELECT * FROM mdl_qratt_attendance WHERE userid = [user_id] AND meetingid = [meeting_id];

# Verify capability assignments
# Check user has 'mod/qratt:takeattendance' in course context
```

**Permission Issues:**
- Ensure users have appropriate role assignments in course
- Check capability definitions in `db/access.php`
- Verify context levels match usage patterns

