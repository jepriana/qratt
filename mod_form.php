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
 * The main qratt configuration form
 *
 * @package    mod_qratt
 * @copyright  2024 QR Attendance Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_qratt_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('qrattname', 'qratt'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'qrattname', 'qratt');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Course Information section
        $mform->addElement('header', 'courseinfo', get_string('courseinformation', 'qratt'));

        // Semester field
        $mform->addElement('text', 'semester', get_string('semester', 'qratt'), array('size' => '20'));
        $mform->setType('semester', PARAM_TEXT);
        $mform->addHelpButton('semester', 'semester', 'qratt');

        // Department field (Jurusan)
        $mform->addElement('text', 'department', get_string('department', 'qratt'), array('size' => '64'));
        $mform->setType('department', PARAM_TEXT);
        $mform->addHelpButton('department', 'department', 'qratt');

        // Study Program field (Program Studi)
        $mform->addElement('text', 'studyprogram', get_string('studyprogram', 'qratt'), array('size' => '64'));
        $mform->setType('studyprogram', PARAM_TEXT);
        $mform->addHelpButton('studyprogram', 'studyprogram', 'qratt');

        // Subject field (Mata Kuliah)
        $mform->addElement('text', 'subject', get_string('subject', 'qratt'), array('size' => '64'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addHelpButton('subject', 'subject', 'qratt');

        // Credits field (SKS)
        $mform->addElement('text', 'credits', get_string('credits', 'qratt'), array('size' => '5'));
        $mform->setType('credits', PARAM_INT);
        $mform->addHelpButton('credits', 'credits', 'qratt');

        // Class name field (Kelas)
        $mform->addElement('text', 'classname', get_string('classname', 'qratt'), array('size' => '20'));
        $mform->setType('classname', PARAM_TEXT);
        $mform->addHelpButton('classname', 'classname', 'qratt');

        // Lecturer field (Dosen)
        $mform->addElement('text', 'lecturer', get_string('lecturer', 'qratt'), array('size' => '64'));
        $mform->setType('lecturer', PARAM_TEXT);
        $mform->addHelpButton('lecturer', 'lecturer', 'qratt');

        // Day of week field (Hari)
        $daysoptions = array(
            '' => get_string('selectday', 'qratt'),
            'monday' => get_string('monday', 'qratt'),
            'tuesday' => get_string('tuesday', 'qratt'),
            'wednesday' => get_string('wednesday', 'qratt'),
            'thursday' => get_string('thursday', 'qratt'),
            'friday' => get_string('friday', 'qratt'),
            'saturday' => get_string('saturday', 'qratt'),
            'sunday' => get_string('sunday', 'qratt')
        );
        $mform->addElement('select', 'dayofweek', get_string('dayofweek', 'qratt'), $daysoptions);
        $mform->addHelpButton('dayofweek', 'dayofweek', 'qratt');

        // Schedule time field (Pukul)
        $mform->addElement('text', 'scheduletime', get_string('scheduletime', 'qratt'), array('size' => '20'));
        $mform->setType('scheduletime', PARAM_TEXT);
        $mform->addHelpButton('scheduletime', 'scheduletime', 'qratt');

        // Room field (Ruang)
        $mform->addElement('text', 'room', get_string('room', 'qratt'), array('size' => '20'));
        $mform->setType('room', PARAM_TEXT);
        $mform->addHelpButton('room', 'room', 'qratt');

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
