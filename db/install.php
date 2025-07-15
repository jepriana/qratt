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
 * Post installation script for QR Attendance module
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to insert default attendance statuses after installation
 */
function xmldb_qratt_install() {
    global $DB;

    // Define default attendance statuses
    $defaultstatuses = array(
        array('statusvalue' => 1, 'description' => 'Present', 'grade' => 1.00, 'visible' => 1),
        array('statusvalue' => 2, 'description' => 'Absent', 'grade' => 0.00, 'visible' => 1),
        array('statusvalue' => 3, 'description' => 'Late', 'grade' => 0.50, 'visible' => 1),
        array('statusvalue' => 4, 'description' => 'Excused', 'grade' => 0.00, 'visible' => 1)
    );

    return true;
}
