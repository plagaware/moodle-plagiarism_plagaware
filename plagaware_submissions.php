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

					foreach ($fileinfos as $fileinfo) {
						$count = "SELECT id  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?  AND fileid = ?";
						$countsubmit = $DB->get_record_sql($count, array($userid, "file", $assignid, $fileinfo->id));
						$record = new stdClass();
						$record->assignid = $assignid;
						$record->userid = $userid;
						$record->filetype = "file";
						$record->fileid = $fileinfo->id;
						$record->timecreated = time();
						$record->reporturl = 1;
						$record->filehash = $fileinfo->contenthash;
						if (!$countsubmit) {
							$newrecordid = $DB->insert_record('plagiarism_plagaware', $record);
							$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $newrecordid;
							$userCode = $plagawareconfig->usercode;
							$sendrequest = self::SubmitPlagAware($userCode, $resultUrl, '', $reportName = null, $reportComment = null, $projectId = null, $fileinfo, true, $dryRun = false);

						}

					}
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

				if (
					$assignmentplagaware = $DB->get_field(
						'plagiarism_plagaware_assign',
						'enabled',
						array('assignid' => $assignsubmission->assignment)
					)
				) {

					// Insert information in plagaware database.
					$count = "SELECT id  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?
						";
					$countsubmit = $DB->get_record_sql($count, array($userid, "onlinetext", $assignmentid));
					$record = new stdClass();
					$record->assignid = $assignsubmission->assignment;
					$record->userid = $userid;
					$record->filetype = "onlinetext";
					$record->fileid = $assignsubmission->id;
					$record->timecreated = time();
					$record->reporturl = 1;

					if (!$countsubmit) {
						$newrecordid = $DB->insert_record('plagiarism_plagaware', $record);
						$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $newrecordid;
					} else {
						$record->id = $countsubmit;
						$DB->update_record('plagiarism_plagaware', $record, $bulk = false);
						$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $countsubmit->id;
					}
					$userCode = $plagawareconfig->usercode;

					$testText = base64_encode($assignsubmission->onlinetext);

					$sendrequest = self::SubmitPlagAware($userCode, $resultUrl, $testText);

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
				if ($assignmentplagaware = $DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignid)) && $assignmentplagaware = $DB->get_field('plagiarism_plagaware_assign', 'autoenabled', array('assignid' => $assignid))) {

					require_once($CFG->dirroot . '/mod/assign/locallib.php');

					$params = array('contextid' => $contextid, 'userid' => $userid);
					$sql = "SELECT * FROM {files} ";
					$sql .= "WHERE filename != '.' ";
					$sql .= "AND contextid= :contextid ";
					$sql .= "AND userid = :userid ";
					$fileinfos = $DB->get_records_sql($sql, $params);

					foreach ($fileinfos as $fileinfo) {
						$count = "SELECT id  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?  AND fileid = ?";
						$countsubmit = $DB->get_record_sql($count, array($userid, "file", $assignid, $fileinfo->id));
						$record = new stdClass();
						$record->assignid = $assignid;
						$record->userid = $userid;
						$record->filetype = "file";
						$record->fileid = $fileinfo->id;
						$record->timecreated = time();
						$record->reporturl = 1;
						$record->filehash = $fileinfo->contenthash;
						if (!$countsubmit) {
							$newrecordid = $DB->insert_record('plagiarism_plagaware', $record);
							$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $newrecordid;
							$userCode = $plagawareconfig->usercode;
							$sendrequest = self::SubmitPlagAware($userCode, $resultUrl, '', $reportName = null, $reportComment = null, $projectId = null, $fileinfo, true, $dryRun = false);

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

				if ($assignmentplagaware = $DB->get_field('plagiarism_plagaware_assign', 'enabled', array('assignid' => $assignmentid)) && $assignmentplagaware = $DB->get_field('plagiarism_plagaware_assign', 'autoenabled', array('assignid' => $assignmentid))) {

					// Insert information in plagaware database.
					$count = "SELECT id  FROM {plagiarism_plagaware} WHERE userid = ? AND filetype = ? AND assignid = ?
						";
					$countsubmit = $DB->get_record_sql($count, array($userid, "onlinetext", $assignmentid));
					$record = new stdClass();
					$record->assignid = $assignsubmission->assignment;
					$record->userid = $userid;
					$record->filetype = "onlinetext";
					$record->fileid = $assignsubmission->id;
					$record->timecreated = time();
					$record->reporturl = 1;

					if (!$countsubmit) {
						$newrecordid = $DB->insert_record('plagiarism_plagaware', $record);
						$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $newrecordid;
					} else {
						$record->id = $countsubmit->id;
						$DB->update_record('plagiarism_plagaware', $record, $bulk = false);
						$resultUrl = $CFG->wwwroot . "/plagiarism/plagaware/callback.php?recoredid=" . $countsubmit->id;
					}
					$userCode = $plagawareconfig->usercode;

					$testText = base64_encode($assignsubmission->onlinetext);

					$sendrequest = self::SubmitPlagAware($userCode, $resultUrl, $testText);


				}
			}
		}
	}


	public static function SubmitPlagAware($UserCode, $resultUrl, $testText, $reportName = null, $reportComment = null, $projectId = null, $filelocation = null, $isfile = false, $dryRun = false)
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
		if (!$isfile) {
			$postdata['TestText'] = base64_decode($testText);
		}

		foreach ($postdata as $key => $val) {
			$content .= "--$boundary\n";
			$content .= "Content-Disposition: form-data; name=\"" . $key . "\"\n\n" . $val . "\n";
		}
		$content .= "--$boundary\n";

		// Collect FILE data
		if ($isfile == true) {

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
?>