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
    public static function default_plugin_options()
    {
        return [
            'enabled',
        ];
    }

    public function get_links($linkarray)
    {
        global $DB, $CFG;

        //(json_encode($linkarray), DEBUG_DEVELOPER, true);     // uncomment for extra bug fixing

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

                $submitIcon     = '<i class="icon fa fa-paper-plane fa-fw" style="color: #000088;"></i>';
                $timeoutIcon     = '<i class="icon fa fa-hourglass-end fa-fw" style="color: #dd0000;"></i>';
                $waitingicon     = '<i class="icon fa fa-hourglass-half fa-fw" style="color: #000088"></i>';
                $okIcon         = '<i class="icon fa fa-check-circle fa-fw" style="color: #000088"></i>';
                $errorIcon         = '<i class="icon fa fa-exclamation-triangle fa-fw" style="color: #dd0000;"></i>';

                $submissionUrl  = $CFG->wwwroot . "/plagiarism/plagaware/post.php";
                $submissionUrl .= "?userid=" . $userid;
                $submissionUrl .= "&action=" . $action;

                $sql = "SELECT * FROM {plagiarism_plagaware} WHERE userid = :userid ";

                if (isset($linkarray['file']) || isset($linkarray['content'])) {

                    if (isset($linkarray['file'])) {
                        $params['fileid'] = $linkarray['file']->get_id();
                        $sql .= "AND filetype = 'file' AND fileid = :fileid AND reporturl IS NOT NULL";
                        $records = $DB->get_records_sql($sql, $params, 0, 1);
                        $reportid = reset($records) ?: null;

                        $type = "file";
                        $submissionUrl .= "&type=" . $type;
                        $submissionUrl .= "&fid=" . $linkarray['file']->get_id();
                        $submissionUrl .= "&contextid=" . $linkarray['file']->get_contextid();
                        $submissionUrl .= "&cmid=" . $linkarray['file']->get_itemid();
                        $submissionUrl .= "&return=" . $linkarray['cmid'];
                    } else if (isset($linkarray['content'])) {
                        if (trim($linkarray['content']) == "")
                            return;
                        $params['assignment'] = $linkarray['assignment'];
                        $sql .= "AND filetype = 'onlinetext' AND assignid = :assignment AND reporturl IS NOT NULL
                                     ORDER BY timecreated DESC";
                        $records = $DB->get_records_sql($sql, $params, 0, 1);
                        $reportid = reset($records) ?: null;

                        $type = "text";
                        $submissionUrl .= "&type=" . $type;
                        $submissionUrl .= "&cmid=" . $linkarray['cmid'];
                        $submissionUrl .= "&return=" . $linkarray['cmid'];
                        $submissionUrl .= "&assignment=" . $linkarray['assignment'];
                    }
                    $restartString  = "<br>$submitIcon<a href='$submissionUrl'>" . get_string('restart', 'plagiarism_plagaware') . "</a>";

                    $debugstring = "";
                    if ($configsettings->debugmode) {
                        $debugstring = "<br>";
                        $debugstring .= " UserId: " . (array_key_exists("userid", $params) ? $params['userid'] : "n/a") . " - ";
                        $debugstring .= " FileId: " . (array_key_exists("fileid", $params) ? $params['fileid'] : "n/a") . " - ";
                        $debugstring .= " Assignment: " . (array_key_exists("assignment", $params) ? $params['assignment'] : "n/a") . " - ";
                        $debugstring .= " Created: " . ($reportid ? $reportid->timecreated : "n/a") . " - ";
                        $debugstring .= " Status: " . ($reportid ? $reportid->status : "n/a") . " - ";
                        $debugstring .= " ReportUrl: " . ($reportid ? $reportid->reporturl : "n/a");
                    }

                    if ($reportid) {    // The file/text has already been sent to PlaAware
                        // The submission to PlagAware failed
                        if ($reportid->status == "error") {
                            $returnstring = "<br>$errorIcon";
                            // later versions of plugin use reporturl for detailed error message
                            $returnstring .= ($reportid->reporturl == "1") ? get_string('error', 'plagiarism_plagaware') : $reportid->reporturl;
                            $returnstring .= $restartString;
                            return $debugstring . $returnstring;
                        }

                        // The callback has been invoked, but PlagAware did not deliver a meantingful result. Needs further checks!
                        if (($reportid->status != "waiting") && ((!$reportid->reporturl) || ($reportid->result == "-1") || (!is_numeric($reportid->result)))) {
                            $returnstring = "<br />$errorIcon";
                            $returnstring .= get_string('error', 'plagiarism_plagaware');
                            $returnstring .= $restartString;
                            return $debugstring . $returnstring;
                        }

                        // We are still waiting for the callback from PlagAware
                        if ($reportid->status == "waiting") {
                            $timeout = ((time() - $reportid->timecreated) > (3 * 60 * 60)); // consider > 3h as timeout
                            // we ran into timeout, offer the option to restart the check
                            if ($timeout) {
                                $returnstring = "<br>$timeoutIcon";
                                $returnstring .= get_string('timeout', 'plagiarism_plagaware');
                                $returnstring .= $restartString;
                            } // normal waiting for callback
                            else {
                                $returnstring = "<br>$waitingicon";
                                $returnstring .= get_string('wait_for_report', 'plagiarism_plagaware');
                            }
                            return $debugstring . $returnstring;
                        }

                        // callback received and everything is ok
                        $returnstring = "<br>$okIcon";
                        $returnstring .= "PlagAware: <a href='https://my.plagaware.com/permalink/$reportid->reporturl' target='_blank'>";
                        $returnstring .= $reportid->result . "%";
                        $returnstring .= "</a>";
                        return $debugstring . $returnstring;
                    } // File has not been sent to PlagAware yet
                    else {
                        $returnstring = "<br>$submitIcon";
                        $returnstring .= "<a href='$submissionUrl'>" . get_string('send_file', 'plagiarism_plagaware') . "</a>";
                        return $debugstring . $returnstring;
                    }
                }
            }
        }
        return "";
    }
}


function plagiarism_plagaware_coursemodule_standard_elements($formwrapper, $mform)
{
    global $DB, $CFG;

    // Only show plugin settings for assignments
    $modulename = $formwrapper->get_current()->modulename;
    if ($modulename !== 'assign') {
        return;
    }

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

    $configsettings = get_config('plagiarism_plagaware');
    $mform->addElement('header', 'plagaware', get_string('pluginname', 'plagiarism_plagaware'));
    if (!$configsettings->enabled) {
        $mform->addElement('static', 'info', '', get_string('assign_globally_disabled', 'plagiarism_plagaware'));
    } else {
        $mform->addElement('checkbox', 'enabled', get_string('enabled', 'plagiarism_plagaware'));
        $mform->setDefault('enabled', $checked);
        $mform->addElement('checkbox', 'plagaware_auto', get_string('plagaware_auto_post', 'plagiarism_plagaware'));
        $mform->setDefault('plagaware_auto', $autochecked);
    }
}


function plagiarism_plagaware_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    // Only apply to assignment modules
    if ($data->modulename !== 'assign') {
        return $data;
    }

    $configsettings = get_config('plagiarism_plagaware');
    if (isset($configsettings->enabled)) {
        $currentassignmentconfig = $DB->get_record('plagiarism_plagaware_assign', ['assignid' => $data->instance]);

        $record = new stdClass();
        $record->assignid = $data->instance;
        $record->enabled = isset($data->enabled) ? $data->enabled : 0;
        $record->autoenabled = isset($data->plagaware_auto) ? $data->plagaware_auto : 0;

        // Don't allow auto submit if PlagAware is switched off for this assignment (does not make sense and is misleading)
        if ($record->autoenabled && !$record->enabled) 
            $record->autoenabled = 0;

        if ($currentassignmentconfig) {
            $record->id = $currentassignmentconfig->id;
            $DB->update_record('plagiarism_plagaware_assign', $record);
        } else {
            $DB->insert_record('plagiarism_plagaware_assign', $record);
        }
    }

    return $data;
}

function create_numeric_array_in($sequence)
{
    if (empty($sequence)) {
        return [];
    }

    // Split on comma and filter to keep only valid integers
    $ids = array_filter(
        array_map('trim', explode(',', $sequence)),
        fn($id) => ctype_digit($id) // only unsigned integers
    );

    // Convert to integer array
    return array_map('intval', $ids);
}
