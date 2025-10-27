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
 * Plugin file serving functions for QR Attendance
 *
 * @package    mod_qratt
 * @copyright  2025 QR Attendance Team (I Wayan Jepriana)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves files for QR Attendance plugin
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function qratt_serve_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $USER;

    // Check if user is logged in
    require_login();

    // Handle institution logo files (system context)
    if ($filearea === 'institutionlogo') {
        $systemcontext = context_system::instance();
        
        // Check if user has capability to view reports
        if (!has_capability('moodle/site:config', $systemcontext)) {
            // Allow users with report viewing capability in any QR attendance activity to view logo
            $capability_found = false;
            $qratts = $DB->get_records('qratt');
            foreach ($qratts as $qratt) {
                $qratt_cm = get_coursemodule_from_instance('qratt', $qratt->id);
                if ($qratt_cm) {
                    $qratt_context = context_module::instance($qratt_cm->id);
                    if (has_capability('mod/qratt:viewreports', $qratt_context)) {
                        $capability_found = true;
                        break;
                    }
                }
            }
            if (!$capability_found) {
                return false;
            }
        }

        $fs = get_file_storage();
        $filename = array_pop($args);
        $itemid = array_shift($args);
        $filepath = '/'.implode('/', $args).'/';

        if (empty($filepath) or $filepath == '/') {
            $filepath = '/';
        }

        $file = $fs->get_file($systemcontext->id, 'mod_qratt', $filearea, $itemid, $filepath, $filename);

        if (!$file) {
            return false;
        }

        send_stored_file($file, null, 0, $forcedownload, $options);
        return true;
    }

    // Handle other file areas if needed in the future
    return false;
}