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


class plagiarism_plugin_plagaware_submissions
{

	/**
	 * Create a new record to track file submissions to PlagAware with default settings
	 */
	private static function CreateNewPlagAwareFileRecord($assignid, $userid, $fileinfo) {
		$record = new stdClass();
		$record->assignid = $assignid;
		$record->userid = $userid;
		$record->filetype = "file";
		$record->fileid = $fileinfo->id;
		$record->timecreated = time();
		$record->reporturl = 1;
		//$record->filehash = $fileinfo->contenthash;
		$record->status = "waiting";
		//$record->replytime = null;
		return $record;
	}

	/**
	 * Create a new record to track online text submissions to PlagAware with default settings
	 */
	private static function CreateNewPlagAwareTextRecord($assignsubmission, $userid) {
		$record = new stdClass();
		$record->assignid = $assignsubmission->assignment;
		$record->userid = $userid;
		$record->filetype = "onlinetext";
		$record->fileid = $assignsubmission->id;
		$record->timecreated = time();
		$record->reporturl = 1;
		$record->status = "waiting";
		//$record->replytime = null;
		return $record;
	}

	/**
	 * Fetch an existing record for a given file transmission. Used to make sure files are not submitted repeatedly.
	 */
	private static function GetExistingPlagAwareFileRecord($assignid, $userid, $fileinfo) {
		global $DB;
		$existingRecordSql = "SELECT id, status  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?  AND fileid = ?";
		$existingRecord = $DB->get_record_sql($existingRecordSql, array($userid, "file", $assignid, $fileinfo->id));
		return $existingRecord;
	}

	/**
	 * Fetch an existing record for a given online text transmission. Used to make sure files are not submitted repeatedly.
	 */
	private static function GetExistingPlagAwareTextRecord($assignid, $userid) {
		global $DB;
		$existingRecordSql = "SELECT id  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?";
		$existingRecord = $DB->get_record_sql($existingRecordSql, array($userid, "onlinetext", $assignid));
		return $existingRecord;
	}


	/**
	 * Submit a file or online text to PlagAware. Update record with error message in case of failure.
	 * Returns true in case of success, false in case of failure
	 */
	private static function SubmitRecord($record, $testText = null, $fileinfo = null) {

		global $DB, $CFG;
		$plagawareconfig = get_config('plagiarism_plagaware');

		// submit the file to PlagAware and register callback function
		$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $record->id;
		$userCode = $plagawareconfig->usercode;
		$sendrequest = self::SubmitPlagAware($userCode, $resultUrl, $testText, $reportName = null, $reportComment = null, $projectId = null, $fileinfo, $dryRun = false);
		
		// in case of success, PlagAware returns a JSON object
		$replyObject = json_decode($sendrequest);
		// if the object cannot be read, treat PlagAware return message as error message and mark the file as error
		if (!$replyObject) {
			$record->status = "error";
			$record->reporturl = $sendrequest; // Attention: we're hijacking expected report url as description for the error
			$DB->update_record('plagiarism_plagaware', $record, $bulk = false);
			return false;
		}
		return true;
	}

	public static function check_and_send_submission_file_to_plagaware($userid, $fid, $contextid, $cmidd)
	{
		global $DB, $CFG;

		$plagawareconfig = get_config('plagiarism_plagaware');

		if (isset($plagawareconfig->enabled) && $plagawareconfig->enabled) {
			$submissionid = $cmidd;

			if ($assignid = $DB->get_field('assignsubmission_file', 'assignment', array('submission' => $submissionid))) {
				if ($assignmentplagaware = $DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignid))) {

					require_once($CFG->dirroot . '/mod/assign/locallib.php');

					//$params = array('contextid' => $contextid, 'userid' => $userid);
					$params = array('fid' => $fid);
					$sql = "SELECT * FROM {files} ";
					$sql .= "WHERE id = :fid ";
					$fileinfos = $DB->get_records_sql($sql, $params);

					$transmissionSuccess = true;

					foreach ($fileinfos as $fileinfo) {
						
						$record = self::CreateNewPlagAwareFileRecord($assignid, $userid, $fileinfo);
						$existingRecord = self::GetExistingPlagAwareFileRecord($assignid, $userid, $fileinfo);
						//print_object($existingRecord);

						// if there is no record for this file (was never sent to Plagaware before), create a new default record
						if (!$existingRecord) {
							$record->id = $DB->insert_record('plagiarism_plagaware', $record);
						} // otherwise, update the existing record with default parameters
						else {
							$record->id = $existingRecord->id;
							$DB->update_record('plagiarism_plagaware', $record, $bulk = false);
						}
						$transmissionSuccess &= self::SubmitRecord($record, null, $fileinfo);
					}

					return $transmissionSuccess;
				}
			}
		}
	}



	public static function check_and_send_submission_text_to_plagaware($userid, $assignmentid)
	{
		global $DB, $CFG;

		$sqlz = "SELECT a.id, o.onlinetext,o.id as tid
			FROM {assignsubmission_onlinetext} o
			JOIN {assign_submission} a ON a.id = o.submission
			WHERE a.userid = ? AND o.assignment = ?
			ORDER BY a.id DESC";
		$moodletextsubmissions = $DB->get_record_sql($sqlz, array($userid, $assignmentid));
		$plagawareconfig = get_config('plagiarism_plagaware');

		if (isset($plagawareconfig->enabled) && $plagawareconfig->enabled) {
			$submissionid = $moodletextsubmissions->tid;

			if ($assignsubmission = $DB->get_record('assignsubmission_onlinetext', array('id' => $submissionid))) {

				if ($DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignsubmission->assignment))) {

					$existingRecord = self::GetExistingPlagAwareTextRecord($assignmentid, $userid);
					$record = self::CreateNewPlagAwareTextRecord($assignsubmission, $userid);

					$transmissionSuccess = true;

					// if there is no record for this file (was never sent to Plagaware before), create a new default record
					if (!$existingRecord) {
						$record->id = $DB->insert_record('plagiarism_plagaware', $record);
					} // otherwise, update the existing record with default parameters
					else {
						$record->id = $existingRecord->id;
						$DB->update_record('plagiarism_plagaware', $record, $bulk = false);
					}
					$testText = base64_encode($assignsubmission->onlinetext);
					$transmissionSuccess &= self::SubmitRecord($record, $testText, null);
					return $transmissionSuccess;
				}
			}
		}
	}


	public static function auto_check_and_send_submission_file_to_plagaware($event)
	{

		$userid = $event->userid;
		$contextid = $event->contextid;
		$cmidd = $event->objectid;

		global $DB, $CFG;

		$plagawareconfig = get_config('plagiarism_plagaware');

		if (isset($plagawareconfig->enabled) && $plagawareconfig->enabled) {
			$submissionid = $cmidd;

			if ($assignid = $DB->get_field('assignsubmission_file', 'assignment', array('id' => $submissionid))) {
				if ($DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignid)) && $DB->get_field('plagiarism_plagaware_assign', 'autoenabled', array('assignid' => $assignid))) {

					require_once($CFG->dirroot . '/mod/assign/locallib.php');

					$params = array('contextid' => $contextid, 'userid' => $userid);
					$sql = "SELECT * FROM {files} ";
					$sql .= "WHERE filename != '.' ";
					$sql .= "AND contextid= :contextid ";
					$sql .= "AND userid = :userid ";
					$fileinfos = $DB->get_records_sql($sql, $params);

					foreach ($fileinfos as $fileinfo) {

						$record = self::CreateNewPlagAwareFileRecord($assignid, $userid, $fileinfo);
						$existingRecord = self::GetExistingPlagAwareFileRecord($assignid, $userid, $fileinfo);

						// Only consider newly submitted files to prevent double-checking of already checked files
						if (!$existingRecord) {
							$record->id = $DB->insert_record('plagiarism_plagaware', $record);
							self::SubmitRecord($record, null, $fileinfo);
						}
					}
				}
			}
		}
	}



	public static function auto_check_and_send_submission_text_to_plagaware($event)
	{
		$userid = $event->userid;

		global $DB, $CFG;

		$sqlz = "SELECT *
			FROM {assignsubmission_onlinetext} 
			WHERE id = ? ";
		$moodletextsubmissions = $DB->get_record_sql($sqlz, array($event->objectid));
		$plagawareconfig = get_config('plagiarism_plagaware');

		if (isset($plagawareconfig->enabled) && $plagawareconfig->enabled) {
			$assignmentid = $moodletextsubmissions->assignment;
			if ($assignsubmission = $DB->get_record('assignsubmission_onlinetext', array('id' => $event->objectid))) {

				if ($DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignmentid)) && $DB->get_field('plagiarism_plagaware_assign', 'autoenabled', array('assignid' => $assignmentid))) {

					$existingRecord = self::GetExistingPlagAwareTextRecord($assignmentid, $userid);
					$record = self::CreateNewPlagAwareTextRecord($assignsubmission, $userid);

					// Only submit if the text has not been submitted before
					if (!$existingRecord) {
						$record->id = $DB->insert_record('plagiarism_plagaware', $record);
						$testText = base64_encode($assignsubmission->onlinetext);
						self::SubmitRecord($record, $testText, null);
					}
				}
			}
		}
	}


	public static function SubmitPlagAware($UserCode, $resultUrl, $testText, $reportName = null, $reportComment = null, $projectId = null, $filelocation = null, $dryRun = false)
	{
		global $USER;
		$lang = explode("_", $USER->lang);
		$url = 'https://www.plagaware.com/service/api';
		$boundary = "---------------------" . substr(md5(rand(0, 32000)), 0, 10);
		$header = "Content-Type: multipart/form-data; boundary=" . $boundary;
		$content = "";
		$postdata = array(
			'UserCode' => $UserCode,
			'ResultUrl' => $resultUrl,
			'ReportName' => $reportName,
			'ReportComment' => $reportComment,
			'ProjectId' => $projectId,
			'DryRun' => $dryRun,
			'Lang' => $lang[0]
		);
		if ($testText) {
			$postdata['TestText'] = base64_decode($testText);
		}

		foreach ($postdata as $key => $val) {
			$content .= "--$boundary\n";
			$content .= "Content-Disposition: form-data; name=\"" . $key . "\"\n\n" . $val . "\n";
		}
		$content .= "--$boundary\n";

		// Collect FILE data
		if ($filelocation) {

			// Get the content of the file.
			$fcontent = "";
			$fs = get_file_storage();
			$file = $fs->get_file(
				$filelocation->contextid,
				$filelocation->component,
				$filelocation->filearea,
				$filelocation->itemid,
				$filelocation->filepath,
				$filelocation->filename
			);

			if ($file)
				$fcontent = $file->get_content();
			$fileext = "." . pathinfo($filelocation->filename, PATHINFO_EXTENSION);
			$filedata = array("TestFile", basename($filelocation->filename), $fcontent);
			$content .= "Content-Disposition: form-data; name=\"{$filedata[0]}\"; filename=\"{$filedata[1]}\"\n";
			$content .= "Content-Transfer-Encoding: binary\n\n";
			$content .= $filedata[2] . "\n";
			$content .= "--$boundary--\n";
		}

		//Create stream context
		$context = stream_context_create(
			array(
				'http' => array(
					'method' => 'POST',
					'header' => $header,
					'content' => $content
				)
			)
		);


		$result = file_get_contents($url, false, $context);
		return $result;
	}


}
