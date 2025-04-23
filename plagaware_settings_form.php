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
 * @package    plagiarism_plagaware
 * @author     Sameh N. Saman developer@plagaware.com
 * @copyright  @2023 plagaware.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');


class plagaware_settings_form extends moodleform
{

    /**
     * Function for form definition
     */
    public function definition()
    {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'common_header', get_string('common_header', 'plagiarism_plagaware'));

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled_long', 'plagiarism_plagaware'), get_string('enabled', 'plagiarism_plagaware'));

        $mform->addElement('text', 'usercode', get_string('usercode', 'plagiarism_plagaware'));
        $mform->setType('usercode', PARAM_ALPHANUM);

        $mform->addElement('header', 'lc_header', get_string('lc_header', 'plagiarism_plagaware'));
        $mform->addElement('static', 'lc_text', get_string('lc_text', 'plagiarism_plagaware'));

        $mform->addElement('advcheckbox', 'create_json_index_file', get_string('create_json_index_file', 'plagiarism_plagaware'), get_string('create_json_index_file_short', 'plagiarism_plagaware'));

        $mform->addElement('duration', 'index_file_grace_seconds_after_cutoff', get_string('index_file_grace_seconds_after_cutoff', 'plagiarism_plagaware'));

        $mform->addElement('text', 'lc_include_assignment_ids', get_string('lc_include_assignments', 'plagiarism_plagaware'));
        $mform->setType('lc_include_assignment_ids', PARAM_SEQUENCE);
        $mform->addHelpButton('lc_include_assignment_ids', 'lc_include_assignments', 'plagiarism_plagaware');

        $mform->addElement('text', 'lc_exclude_assignment_ids', get_string('lc_exclude_assignments', 'plagiarism_plagaware'));
        $mform->setType('lc_exclude_assignment_ids', PARAM_SEQUENCE);
        $mform->addHelpButton('lc_exclude_assignment_ids', 'lc_exclude_assignments', 'plagiarism_plagaware');

        $mform->addElement('header', 'expert_header', get_string('expert_header', 'plagiarism_plagaware'));

        $mform->addElement('text', 'index_file_rows_per_batch', get_string('index_file_rows_per_batch', 'plagiarism_plagaware'));
        $mform->setType('index_file_rows_per_batch', PARAM_INT);

        $mform->addElement('text', 'index_file_index_path', get_string('index_file_index_path', 'plagiarism_plagaware'));
        $mform->setType('index_file_index_path', PARAM_PATH);

        $mform->addElement('text', 'index_file_log_path', get_string('index_file_log_path', 'plagiarism_plagaware'));
        $mform->setType('index_file_log_path', PARAM_PATH);

        $mform->addElement('advcheckbox', 'debugmode', get_string('debugmode', 'plagiarism_plagaware'));

        $this->add_action_buttons(true);
    }

    
}
