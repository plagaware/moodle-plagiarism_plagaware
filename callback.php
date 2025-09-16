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
global $DB;
$rid = required_param('recoredid',PARAM_RAW);
$result = required_param('result',PARAM_RAW);
$status = required_param('status',PARAM_RAW);
$id = required_param('id',PARAM_RAW);

$response = print_r($_REQUEST, true);
$my_file = $CFG->dataroot.'/callback.log';
$handle = fopen($my_file, 'a') or die('Cannot open file:  ' . $my_file);
fwrite($handle, $response);

$record = new stdClass();

$record->id = $rid;
$record->result = ($result != -1) ? $result : null;
$record->status = $status;
$record->reporturl = $id ?: get_string('error', 'plagiarism_plagaware');
$DB->update_record('plagiarism_plagaware', $record, $bulk = false);

die("Callback registered");

?>