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


require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();
require_once('plagaware_submissions.php');

$sendtoplagaware = new plagiarism_plugin_plagaware_submissions;

$type = optional_param('type', 0, PARAM_RAW);
$userid = optional_param('sid', 0, PARAM_RAW);
$cmidd = optional_param('cmid', 0, PARAM_RAW);
$return = optional_param('return', 0, PARAM_RAW);
$action = optional_param('action', 0, PARAM_RAW);
if ($type == "text") {
	$assignmentid = optional_param('assignment', 0, PARAM_RAW);
	$post = $sendtoplagaware->check_and_send_submission_text_to_plagaware($userid, $assignmentid);

} elseif ($type == "file") {
	$fid = optional_param('fid', 0, PARAM_RAW);
	$contextid = optional_param('contextid', 0, PARAM_RAW);
	$post = $sendtoplagaware->check_and_send_submission_file_to_plagaware($userid, $fid, $contextid, $cmidd);
}
if ($action == 'grading') {
	$urltogo = new moodle_url($CFG->wwwroot . '/mod/assign/view.php', array('id' => $return, 'action' => $action));
} else {
	$urltogo = new moodle_url($CFG->wwwroot . '/mod/assign/view.php', array('userid' => $userid, 'id' => $return, 'action' => 'grader'));
}
if ($post) {
	redirect($urltogo, get_string('submited', 'plagiarism_plagaware'), null, \core\output\notification::NOTIFY_SUCCESS);
} else {
	redirect($urltogo, get_string('submission_error', 'plagiarism_plagaware'), null, \core\output\notification::NOTIFY_WARNING);
}

?>