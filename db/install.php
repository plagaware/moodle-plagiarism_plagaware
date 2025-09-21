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

function xmldb_plagiarism_plagaware_install() {
    
    // Ensure plagiarism system is enabled globally.
    set_config('enableplagiarism', 1);

    // basic 
    set_config('enabled', 0, 'plagiarism_plagaware');
    set_config('plagaware_auto_post', 0, 'plagiarism_plagaware');
    set_config('usercode', '', 'plagiarism_plagaware');
    set_config('debugmode', 0, 'plagiarism_plagaware');

    // new for upload moodle files to library functionality
    set_config('create_json_index_file', 0, 'plagiarism_plagaware');
    set_config('index_file_grace_seconds_after_cutoff', 30 * 24 * 60 * 60, 'plagiarism_plagaware');
    set_config('index_file_rows_per_batch', 1000, 'plagiarism_plagaware');
    set_config('index_file_index_path', '/filedir/index.json', 'plagiarism_plagaware');             // relative to data dir
    set_config('index_file_log_path', '/lc-cache/fileslog.json', 'plagiarism_plagaware');           // relative to data dir
    set_config('lc_include_assignment_ids', '', 'plagiarism_plagaware');
    set_config('lc_exclude_assignment_ids', '', 'plagiarism_plagaware');

}
