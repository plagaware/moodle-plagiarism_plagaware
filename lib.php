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
	global $CFG;
	require_once($CFG->dirroot . '/plagiarism/lib.php');
	require_once($CFG->libdir . '/filelib.php');
	require_once($CFG->libdir . '/accesslib.php');
	class plagiarism_plugin_plagaware extends plagiarism_plugin
	{
		public static function default_plugin_options() {
			return [
            'enabled',
            
			];
		}
		
		
		
		
		
		public function get_links($linkarray)
		{
			
			global $DB, $CFG;
			
			$configsettings = get_config('plagiarism_plagaware');
			$action = optional_param('action', 0, PARAM_RAW);
			$params = array(
			'cmid' => $linkarray['cmid'],
			'userid' => $linkarray['userid'],
			);
			$sql = "SELECT pca.enabled ";
			$sql .= "FROM {course_modules} cm ";
			$sql .= "JOIN {plagiarism_plagaware_assign} pca ON cm.instance = pca.assignid ";
			$sql .= "WHERE cm.id = :cmid ";
			$enabled = $DB->get_field_sql($sql, array('cmid' => $linkarray['cmid']));
			
			
			if ((isset($linkarray['assignment']) || isset($linkarray['file'])) && $enabled == 1 && $configsettings->enabled == 1) {
				$context = context_module::instance($linkarray['cmid']);
				$userid = $linkarray['userid'];
				if (has_capability('mod/assign:grade', $context)) {
					$sql = "SELECT * ";
					$sql .= "FROM {plagiarism_plagaware} ";
					$sql .= "WHERE userid = :userid ";
					
					if (isset($linkarray['file'])) {
						//print_object($linkarray);
						$params['fileid'] = $linkarray['file']->get_id();
						$sql .= "AND filetype = 'file' ";
						$sql .= "AND fileid = :fileid ";
						$sql .= "AND reporturl IS NOT NULL ";
						$type = "file";
						$sid = $userid;
						$reportid = $DB->get_record_sql($sql, $params);
						if ($reportid) {
							if ($reportid->reporturl == "1") {
								$returnstring = "<br>" . get_string('wait_for_report', 'plagiarism_plagaware');
								return $returnstring;
							}
							if ($reportid->result == "-1") {
								$returnstring = "<br>" . get_string('error', 'plagiarism_plagaware');
								return $returnstring;
							}
							
							$returnstring = "<br />";
							// The a link tag with url.
							$returnstring .= "PlagAware: <a href='";
							$returnstring .= "https://my.plagaware.com/permalink/";
							$returnstring .= $reportid->reporturl;
							$returnstring .= "' target='_blank' >";
							// The string.
							$returnstring .= $reportid->result . "%";
							$returnstring .= "</a>";
							return $returnstring;
							
							} else {
							$returnstring = "<br />";
							// The a link tag with url.
							
							$returnstring .= "<a href='";
							$returnstring .= $CFG->wwwroot . "/plagiarism/plagaware/post.php";
							$returnstring .= "?fid=" . $linkarray['file']->get_id();
							$returnstring .= "&contextid=" . $linkarray['file']->get_contextid();
							$returnstring .= "&type=" . $type;
							$returnstring .= "&sid=" . $sid;
							$returnstring .= "&cmid=" . $linkarray['file']->get_itemid();
							$returnstring .= "&return=" . $linkarray['cmid'];
							$returnstring .= "&action=" . $action;
							$returnstring .= "'>";
							// The string.
							$returnstring .= get_string('send_file', 'plagiarism_plagaware');
							;
							// Close the link
							$returnstring .= "</a>";
							
							return $returnstring;
						}
						} else if (isset($linkarray['content'])) {
						//print_object($linkarray);
						if (trim($linkarray['content']) == "")
						return;
						$params['assignment'] = $linkarray['assignment'];
						$sql .= "AND filetype = 'onlinetext' ";
						$sql .= "AND assignid = :assignment ";
						$sql .= "AND reporturl IS NOT NULL ";
						$sql .= "ORDER BY timecreated DESC ";
						$sql .= "LIMIT 1 ";
						$type = "text";
						$sid = $userid;
						$reportid = $DB->get_record_sql($sql, $params);
						if ($reportid) {
							$returnstring = "<br />";
							// The a link tag with url.
							if ($reportid->reporturl == "1") {
								$returnstring .= get_string('wait_for_report', 'plagiarism_plagaware');
								} else {
								$returnstring .= "<a href='";
								$returnstring .= "https://www.plagaware.com/reportpreview/";
								$returnstring .= "?id=" . $reportid->reporturl;
								$returnstring .= "' target='_blank' >";
								// The string.
								$returnstring .= $reportid->result . "%[ ";
								$returnstring .= get_string('view_report', 'plagiarism_plagaware');
								$returnstring .= " ]";
								// Close the link
								$returnstring .= "</a>";
							}
							return $returnstring;
							} else {
							$returnstring = "<br />";
							// The a link tag with url.
							$returnstring .= "<a href='";
							$returnstring .= $CFG->wwwroot . "/plagiarism/plagaware/post.php";
							$returnstring .= "?cmid=" . $linkarray['cmid'];
							$returnstring .= "&return=" . $linkarray['cmid'];
							$returnstring .= "&assignment=" . $linkarray['assignment'];
							$returnstring .= "&type=" . $type;
							$returnstring .= "&sid=" . $sid;
							$returnstring .= "&action=" . $action;
							$returnstring .= "'>";
							// The string.
							$returnstring .= "[" . get_string('send_file', 'plagiarism_plagaware') . "] ";
							// Close the link
							$returnstring .= "</a>";
							return $returnstring;
						}
					}
				}
			}
			return "";
		}
		
		
		public function save_form_elements($data) {
			return plagiarism_plagaware_coursemodule_edit_post_actions($data, null);
		}
		
		public function get_form_elements_module($mform, $context, $modulename = "") {
			global $DB;
			// Only with the assign module.
			if ($modulename != 'mod_assign') return;
			$configsettings = get_config('plagiarism_plagaware');
			if (!isset($configsettings->plagaware_use)) return;
			if (!isset($configsettings->usercode)) return;
			if ($configsettings->plagaware_use && trim($configsettings->usercode) != "") {
				$checked = 0;
				$cmid = optional_param('update', 0, PARAM_INT);
				if ($cmid) {
					$sql  = "SELECT pca.enabled ";
					$sql .= "FROM {course_modules} cm ";
					$sql .= "JOIN {plagiarism_plagaware_assign} pca ON cm.instance = pca.assignid ";
					$sql .= "WHERE cm.id = :cmid ";
					$checked = $DB->get_field_sql($sql, array('cmid' => $cmid));
					
					$sqlz  = "SELECT pca.autoenabled ";
					$sqlz .= "FROM {course_modules} cm ";
					$sqlz .= "JOIN {plagiarism_plagaware_assign} pca ON cm.instance = pca.assignid ";
					$sqlz .= "WHERE cm.id = :cmid ";
					$autochecked = $DB->get_field_sql($sqlz, array('cmid' => $cmid));
				}
				$mform->addElement('header', 'plagaware', get_string('pluginname', 'plagiarism_plagaware'));
				$mform->addElement('checkbox', 'plagaware_use', get_string('plagaware_use', 'plagiarism_plagaware'));
				$mform->setDefault('plagaware_use', $checked);
				$mform->addElement('checkbox', 'plagaware_auto', get_string('plagaware_auto_post', 'plagiarism_plagaware'));
				$mform->setDefault('plagaware_auto', $autochecked);
			}
		}
		
		
	}
	
	
	function plagiarism_plagaware_coursemodule_standard_elements($formwrapper, $mform) {
		global $DB;
		$plagium = 'plagiarism_plagaware';
		
		$context = context_course::instance($formwrapper->get_course()->id);
		
		
		
		$modulename = $formwrapper->get_current()->modulename;
		$checked = 0;
		$autochecked = 0;
		$cmid = optional_param('update', 0, PARAM_INT);
		if ($cmid) {
			$sql = "SELECT pca.enabled ";
			$sql .= "FROM {course_modules} cm ";
			$sql .= "JOIN {plagiarism_plagaware_assign} pca ON cm.instance = pca.assignid ";
			$sql .= "WHERE cm.id = :cmid ";
			$checked = $DB->get_field_sql($sql, array('cmid' => $cmid));
			
			$sqlz = "SELECT pca.autoenabled ";
			$sqlz .= "FROM {course_modules} cm ";
			$sqlz .= "JOIN {plagiarism_plagaware_assign} pca ON cm.instance = pca.assignid ";
			$sqlz .= "WHERE cm.id = :cmid ";
			$autochecked = $DB->get_field_sql($sqlz, array('cmid' => $cmid));
		}
		
		$mform->addElement('header', 'plagaware', get_string('pluginname', 'plagiarism_plagaware'));
		$mform->addElement('checkbox', 'enabled', get_string('enabled', 'plagiarism_plagaware'));
		$mform->setDefault('enabled', $checked);
		$mform->addElement('checkbox', 'plagaware_auto', get_string('plagaware_auto_post', 'plagiarism_plagaware'));
		$mform->setDefault('plagaware_auto', $autochecked);
		
	}
	
	function plagiarism_plagaware_coursemodule_edit_post_actions($data, $course) {
		global $DB;
		
		$configsettings = get_config('plagiarism_plagaware');
		if (isset($configsettings->enabled)) {
			$currentassignmentconfig = $DB->get_record('plagiarism_plagaware_assign', array('assignid' => $data->instance));
			$record = new stdClass();
			$record->assignid = $data->instance;
			if (isset($data->enabled))
			$record->enabled = $data->enabled;
			else
			$record->enabled = 0;
			
			
			if (isset($data->plagaware_auto)) {
				$record->autoenabled = $data->plagaware_auto;
				} else {
				$record->autoenabled = 0;
			}
			if ($currentassignmentconfig) {
				$record->id = $currentassignmentconfig->id;
				$DB->update_record('plagiarism_plagaware_assign', $record);
				} else {
				$DB->insert_record('plagiarism_plagaware_assign', $record);
			}
		}
		
		return $data;
	}							