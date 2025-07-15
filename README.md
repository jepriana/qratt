# QR Attendance Plugin for Moodle

A Moodle activity module that allows lecturers to take student attendance using QR codes.

## Features

- **Meeting Management**: Create and manage multiple meetings within a single QR Attendance activity
- **QR Code Generation**: Dynamic QR codes that refresh every 60 seconds for security
- **Automatic Attendance Tracking**: Students scan QR codes to automatically mark their attendance
- **Multiple Attendance Statuses**: Support for Present, Absent, Late, and Excused statuses
- **Real-time Updates**: QR codes automatically refresh to prevent unauthorized sharing
- **Comprehensive Reports**: View attendance data by meeting or by student
- **CSV Export**: Download attendance reports in CSV format
- **Multi-language Support**: Available in English and Indonesian
- **Manual Override**: Lecturers can manually change student attendance status

## Requirements

- Moodle 4.5 or higher
- PHP 7.4 or higher
- MySQL/PostgreSQL database

## Installation

1. Download or clone this repository
2. Copy the `qratt` folder to your Moodle's `/mod/` directory
3. Navigate to Site Administration > Notifications in your Moodle instance
4. Follow the installation prompts to install the plugin
5. The plugin will create the necessary database tables automatically

## Usage

### For Lecturers

1. **Create QR Attendance Activity**:
   - Go to your course
   - Turn editing on
   - Add activity → QR Attendance
   - Fill in the activity name and description

2. **Add Meetings**:
   - Click on the QR Attendance activity
   - Go to the "Meetings" tab
   - Click "Add meeting"
   - Enter meeting number, topic, and date

3. **Activate Meeting**:
   - In the meetings list, click "Activate" for the meeting you want to start
   - This will activate the QR code generation

4. **Display QR Code**:
   - Click "Show QR Code" for an active meeting
   - Display this QR code to students in the classroom
   - The QR code refreshes every 60 seconds automatically

5. **End Meeting**:
   - Click "End meeting" when the session is over
   - Students can no longer scan the QR code after this

6. **View Reports**:
   - Go to the "Reports" tab to view attendance data
   - Choose from overview, by meeting, or by student reports
   - Download CSV reports if needed

### For Students

1. **Join Course**: Ensure you are enrolled in the course
2. **Scan QR Code**: When the lecturer displays the QR code, scan it with any QR code reader
3. **Automatic Attendance**: Your attendance will be automatically marked as Present or Late
4. **View Status**: Check your attendance status in the QR Attendance activity

## Database Schema

The plugin creates four main tables:

- `qratt`: Main QR Attendance instances
- `qratt_meetings`: Individual meetings within each instance
- `qratt_attendance`: Student attendance records
- `qratt_statuses`: Attendance status definitions

## Attendance Statuses

1. **Present**: Student scanned QR code within the allowed time
2. **Late**: Student scanned QR code after the late threshold (15 minutes)
3. **Absent**: Student did not scan the QR code
4. **Excused**: Manually set by lecturer for excused absences

## Security Features

- QR codes expire every 60 seconds
- Unique tokens prevent QR code sharing
- Course enrollment verification
- Capability-based access control
- Session validation

## Configuration

The plugin uses several constants that can be customized:

- QR code refresh interval: 60 seconds (hardcoded)
- Late threshold: 15 minutes after meeting start
- Attendance statuses: Present (1), Absent (2), Late (3), Excused (4)

## File Structure

```
qratt/
├── db/
│   ├── access.php          # Capability definitions
│   ├── install.php         # Installation script
│   └── install.xml         # Database schema
├── lang/
│   ├── en/qratt.php       # English language strings
│   └── id/qratt.php       # Indonesian language strings
├── lib.php                 # Main library functions
├── mod_form.php           # Activity creation form
├── version.php            # Plugin version information
├── view.php               # Main view page
├── meetings.php           # Meeting management
├── qrcode.php            # QR code display
├── scan.php              # QR code scanning endpoint
├── reports.php           # Attendance reports
└── README.md             # This file
```

## Development

### Adding New Languages

1. Create a new folder in `lang/` with the language code
2. Copy `lang/en/qratt.php` to the new folder
3. Translate all strings in the new file

### Customizing QR Code Generation

The plugin currently uses an online QR code service. For production use, consider:
- Installing a local QR code library
- Implementing offline QR code generation
- Adding QR code customization options

### Extending Attendance Statuses

To add new attendance statuses:
1. Update the constants in `lib.php`
2. Add new status records in `db/install.php`
3. Update language strings
4. Modify status handling in relevant files

## Support

For support and bug reports, please create an issue in the project repository.

## License

This plugin is licensed under the GNU GPL v3 or later.

## Credits

Developed by the QR Attendance Team for Moodle 4.5+.
