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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/plagiarism/plagaware/lib.php');

require_login();
$url = new moodle_url('/plagiarism/plagaware/settings.php');
$context = context_system::instance();
$PAGE->set_url($url);
$PAGE->set_context($context);
$pagetitle = get_string('pluginname', 'plagiarism_plagaware');
$PAGE->set_title($pagetitle);

admin_externalpage_setup('plagiarismplagaware');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

require_once('plagaware_settings_form.php');

$mform = new plagaware_settings_form();

$mform->set_data(get_config('plagiarism_plagaware'));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
}

if (($data = $mform->get_data()) && confirm_sesskey()) {
    foreach ($data as $field => $value) {
        if (strpos($field, "submit") === false) {
            set_config($field, $value, 'plagiarism_plagaware');
        }
    }
    echo $OUTPUT->notification(get_string('saved_plagaware_settings', 'plagiarism_plagaware'), 'notifysuccess');
}


echo $OUTPUT->header();

echo "<h3>" . get_string('plagaware_settings_header', 'plagiarism_plagaware') . "</h3>\n";

echo $OUTPUT->box_start();

$mform->display();

$config = get_config('plagiarism_plagaware');
if ($config->debugmode) {
    echo "<h4>Debug Information</h4>";
    if ($config->create_json_index_file) {
        $file_count = get_lc_file_count();
        echo ("<p>" . sprintf(get_string('lc_file_count', 'plagiarism_plagaware'), $file_count) . "</p>");
    }
}


echo $OUTPUT->box_end();
echo $OUTPUT->footer();

 function get_lc_file_count()
    {
        global $DB;
        $config = get_config('plagiarism_plagaware');
        $graceSecondsAfterCutoffDate = $config->index_file_grace_seconds_after_cutoff;
        
        $inIncludeAssignmentIds = create_numeric_array_in($config->lc_include_assignment_ids);
        $inExcludeAssignmentIds = create_numeric_array_in($config->lc_exclude_assignment_ids);

        $sql = "SELECT COUNT(f.id) AS file_count 
                FROM {files} f
                JOIN {assign_submission} s ON f.itemid = s.id
                JOIN {assign} a ON s.assignment = a.id
                LEFT JOIN {plagiarism_plagaware_library} p on p.contenthash = f.contenthash
                WHERE COALESCE(a.cutoffdate, 0) > 0 
                    AND (UNIX_TIMESTAMP() - COALESCE(a.cutoffdate)) > $graceSecondsAfterCutoffDate
                    AND f.component='assignsubmission_file'
                    AND f.filearea='submission_files'
                    AND (f.mimetype LIKE 'application/%' OR f.mimetype LIKE 'text/plain')
                    AND (p.status IS NULL OR p.status = 'NEW')";
        if ($inIncludeAssignmentIds) {
            $sql .= " AND a.id IN $inIncludeAssignmentIds";
        }
        if ($inExcludeAssignmentIds) {
            $sql .= " AND a.id NOT IN $inExcludeAssignmentIds";
        }
        
        $row = $DB->get_record_sql($sql);
        return $row->file_count;
    }

