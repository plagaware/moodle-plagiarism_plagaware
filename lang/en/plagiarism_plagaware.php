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

$string['pluginname']                   = 'PlagAware Plagiarism Checker';
$string['plagaware']                    = 'PlagAware';
$string['usercode']                   	= 'User Code for PlagAware API. Please get the user code from your PlagAware user profile.';
$string['enabled']                      = 'Enable PlagAware';
$string['enabled_long']                 = 'Enable PlagAware globally in this Moodle instance';
$string['plagaware_auto_post']          = 'Auto submit to PlagAware';
$string['enabled_help']                 = 'Enable PlagAware to enable plagiarism checking of assignments';
$string['saved_plagaware_settings']     = 'Settings succesfully saved';
$string['plagaware_settings_header']    = 'PlagAware Settings';
$string['view_report']                  = 'View';
$string['view_report_pdf']              = 'PDF';
$string['wait_for_report']              = 'Waiting for PlagAware...';
$string['send_file']                    = 'Plagiarism Check...';
$string['submitted']                     = 'Plagiarism check started'; 
$string['plagawareuse']                 = 'Enable PlagAware'; //plagaware_use
$string['error']                        = 'Plagiarism check failed';
$string['timeout']                      = 'Timeout';
$string['restart']                      = 'Restart Plagiarism Check...';
$string['submission_error']             = 'One or more files could not be submitted to PlagAware';
$string['debugmode']                    = 'Debug-Mode';

$string['privacy:metadata:table'] = 'Stores plagiarism results for user-submitted files.';
$string['privacy:metadata:userid'] = 'The ID of the user who submitted the file.';
$string['privacy:metadata:assignid'] = 'The ID of the assignment the file was submitted to.';
$string['privacy:metadata:fileid'] = 'The ID of the Moodle file.';
$string['privacy:metadata:result'] = 'The calculated similarity score of the submission.';


$string['taskname'] = 'Scheduled Task for PlagAware Plugin';

$string['common_header'] = 'General Settings';
$string['lc_header'] = 'Synchronization with the library';
$string['lc_text'] = 'Note: The synchronization of the submitted documents with the PlagAware library requires the installation of PlagAware LibCrawler. For further details, please contact your IT support and/or PlagAware support.';
$string['lc_file_count'] = 'Files to be transferred: %s';
$string['lc_exclude_assignments'] = 'Assignments to be excluded';
$string['lc_exclude_assignments_help'] = 'Comma-separated list of assignment IDs to be excluded from file synchronization.';
$string['lc_include_assignments'] = 'Assignments to be included';
$string['lc_include_assignments_help'] = 'Comma-separated list of the assignment IDs to which file synchronization is to be limited.';
$string['create_json_index_file'] = 'Create index file and activate synchronization';
$string['create_json_index_file_short'] = 'Activate synchronization';
$string['index_file_grace_seconds_after_cutoff'] = 'Past duration after cutoff date from which texts are uploaded';
$string['index_file_index_path'] = 'Path and name of the index file, relative to the data directory (/moodledata)';
$string['index_file_log_path'] = 'Path and name of the LibCrawler log file, relative to the data directory (/moodledata)';
$string['index_file_rows_per_batch'] = 'Maximum number of documents to be uploaded per batch';
$string['expert_header'] = 'Expert settings and debugging';
$string['assign_globally_disabled'] = 'Note: PlagAware is switched off globally in this Moodle instance.';



