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
 * QR Attendance plugin settings
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('modsettingqratt', new lang_string('pluginname', 'mod_qratt'));

    // Security settings section
    $settings->add(new admin_setting_heading('qratt_security_settings',
        new lang_string('securitysettings', 'mod_qratt'),
        new lang_string('securitysettings_desc', 'mod_qratt')));

    // QR Code Encryption Key
    $settings->add(new admin_setting_configpasswordunmask('mod_qratt/encryptionkey',
        new lang_string('encryptionkey', 'mod_qratt'),
        new lang_string('encryptionkey_desc', 'mod_qratt'),
        ''));

    // Institution information section
    $settings->add(new admin_setting_heading('qratt_institution_settings',
        new lang_string('institutionsettings', 'mod_qratt'),
        new lang_string('institutionsettings_desc', 'mod_qratt')));

    // Institution Name
    $settings->add(new admin_setting_configtext('mod_qratt/institutionname',
        new lang_string('institutionname', 'mod_qratt'),
        new lang_string('institutionname_desc', 'mod_qratt'),
        '', PARAM_TEXT));

    // Institution Address
    $settings->add(new admin_setting_configtextarea('mod_qratt/institutionaddress',
        new lang_string('institutionaddress', 'mod_qratt'),
        new lang_string('institutionaddress_desc', 'mod_qratt'),
        '', PARAM_TEXT));

    // Institution Phone
    $settings->add(new admin_setting_configtext('mod_qratt/institutionphone',
        new lang_string('institutionphone', 'mod_qratt'),
        new lang_string('institutionphone_desc', 'mod_qratt'),
        '', PARAM_TEXT));

    // Institution Fax
    $settings->add(new admin_setting_configtext('mod_qratt/institutionfax',
        new lang_string('institutionfax', 'mod_qratt'),
        new lang_string('institutionfax_desc', 'mod_qratt'),
        '', PARAM_TEXT));

    // Institution City
    $settings->add(new admin_setting_configtext('mod_qratt/institutioncity',
        new lang_string('institutioncity', 'mod_qratt'),
        new lang_string('institutioncity_desc', 'mod_qratt'),
        '', PARAM_TEXT));

    // Institution Website
    $settings->add(new admin_setting_configtext('mod_qratt/institutionwebsite',
        new lang_string('institutionwebsite', 'mod_qratt'),
        new lang_string('institutionwebsite_desc', 'mod_qratt'),
        '', PARAM_URL));

    // Institution Email
    $settings->add(new admin_setting_configtext('mod_qratt/institutionemail',
        new lang_string('institutionemail', 'mod_qratt'),
        new lang_string('institutionemail_desc', 'mod_qratt'),
        '', PARAM_EMAIL));

    // Institution Logo
    $settings->add(new admin_setting_configstoredfile('mod_qratt/institutionlogo',
        new lang_string('institutionlogo', 'mod_qratt'),
        new lang_string('institutionlogo_desc', 'mod_qratt'),
        'institutionlogo', 0,
        array('maxfiles' => 1, 'accepted_types' => array('.png', '.jpg', '.jpeg', '.gif')));

    // Report settings section
    $settings->add(new admin_setting_heading('qratt_report_settings',
        new lang_string('reportsettings', 'mod_qratt'),
        new lang_string('reportsettings_desc', 'mod_qratt')));

    // Include institution info in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includeinstitutioninfo',
        new lang_string('includeinstitutioninfo', 'mod_qratt'),
        new lang_string('includeinstitutioninfo_desc', 'mod_qratt'),
        1));

    // Include logo in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includelogoinreports',
        new lang_string('includelogoinreports', 'mod_qratt'),
        new lang_string('includelogoinreports_desc', 'mod_qratt'),
        1));

    // Include address in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includeaddressinreports',
        new lang_string('includeaddressinreports', 'mod_qratt'),
        new lang_string('includeaddressinreports_desc', 'mod_qratt'),
        1));

    // Include website in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includewebsiteinreports',
        new lang_string('includewebsiteinreports', 'mod_qratt'),
        new lang_string('includewebsiteinreports_desc', 'mod_qratt'),
        1));

    // Include email in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includeemailinreports',
        new lang_string('includeemailinreports', 'mod_qratt'),
        new lang_string('includeemailinreports_desc', 'mod_qratt'),
        1));

    // Include city in reports
    $settings->add(new admin_setting_configcheckbox('mod_qratt/includecityinreports',
        new lang_string('includecityinreports', 'mod_qratt'),
        new lang_string('includecityinreports_desc', 'mod_qratt'),
        1));

    $ADMIN->add('modsettings', $settings);
}