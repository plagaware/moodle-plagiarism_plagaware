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
        $mform =& $this->_form;

        // Use of the plugin.
        $mform->addElement('checkbox', 'enabled', get_string('enabled', 'plagiarism_plagaware'));
       
        $mform->addHelpButton('enabled', 'enabled', 'plagiarism_plagaware');

        $mform->addElement('text', 'usercode', get_string('usercode', 'plagiarism_plagaware'));
        $mform->setType('usercode', PARAM_RAW);
        

        $this->add_action_buttons(true);
    }
}