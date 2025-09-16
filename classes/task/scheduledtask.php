<?php

namespace plagiarism_plagaware\task;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class scheduledtask extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('taskname', 'plagiarism_plagaware');
    }

    public function execute()
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/plagiarism/plagaware/lib.php');

        $config = get_config('plagiarism_plagaware');

        if (!$config->create_json_index_file) {
            mtrace("File synchronization not activated in global settings.");
            return true;
        }

        // Number of maximum records to create in one batch (max. lines in index files)
        $maxNumberOfRowsPerBatch = $config->index_file_rows_per_batch;
        // Seconds after past cutoff date for an assignment after which files will be uploaded (prevent false positives)
        $graceSecondsAfterCutoffDate = $config->index_file_grace_seconds_after_cutoff;
        // file name of log file created by PlagAware LibCrawler docker container
        $logFilesPath = $CFG->dataroot . $config->index_file_log_path;
        // file name of index file used to point PlagAware LibCrawler to the files to be uploaded
        $indexFilePath = $CFG->dataroot . $config->index_file_index_path;
        // Include assignment ids
        $inIncludeAssignmentIds = create_numeric_array_in($config->lc_include_assignment_ids);
        // Exclude assignment ids
        $inExcludeAssignmentIds = create_numeric_array_in($config->lc_exclude_assignment_ids);

        mtrace('Scheduled task for PlagAware Plugin is running...');

        // Scan the log file of libcrawler and enter the processed files into the plagiarism_plagaware_library table 
        mtrace("===== Scanning $logFilesPath for files recently processed by LibCrawler...");
        $addedCount = $this->scan_file_reverse($logFilesPath, function ($line) {

            if (!$line)
                return false;

            $metadata = json_decode($line);
            if (!$metadata) {
                mtrace("Failed to decode metadata: $line");
                return false;
            }

            $contenthash = substr($metadata->file, 6);
            if (!$contenthash) {
                mtrace("Contenthash id is not valid.");
                return false;
            }

            // add newly processed files in database
            global $DB;
            $libtext = $DB->get_record("plagiarism_plagaware_library", ['contenthash' => $contenthash]);
            if ($libtext) {
                return false;
            }
            $newRecord = new stdClass();
            $newRecord->contenthash = $contenthash;
            $newRecord->status = $metadata->status;
            $newRecord->date = time();
            $newRecord->plagaware_id = $metadata->library_id;
            $DB->insert_record("plagiarism_plagaware_library", $newRecord);
            return true;
        });
        mtrace("Added $addedCount new records from LibCrawler");

        mtrace("===== Querying not yet uploaded files...");
        $now = time(); // Current timestamp in PHP
        $params = [
            'now' => $now,
            'grace' => $graceSecondsAfterCutoffDate,
            'component' => 'assignsubmission_file',
            'filearea' => 'submission_files',
        ];

        $sql = "SELECT f.id AS file_id, f.filename, f.contenthash, f.pathnamehash, f.userid,
               a.name AS assignment_name, a.cutoffdate, s.timemodified, p.status
                FROM {files} f
                JOIN {assign_submission} s ON f.itemid = s.id
                JOIN {assign} a ON s.assignment = a.id
                LEFT JOIN {plagiarism_plagaware_library} p ON p.contenthash = f.contenthash
                WHERE COALESCE(a.cutoffdate, 0) > 0
                AND (:now - COALESCE(a.cutoffdate, 0)) > :grace
                AND f.component = :component
                AND f.filearea = :filearea
                AND (f.mimetype LIKE 'application/%' OR f.mimetype = 'text/plain')
                AND (p.status IS NULL OR p.status = 'NEW')";

        // Optional filters
        if ($inIncludeAssignmentIds) {
            [$insql, $inparams] = $DB->get_in_or_equal($inIncludeAssignmentIds, SQL_PARAMS_NAMED, 'inc');
            $sql .= " AND a.id $insql";
            $params += $inparams;
        }
        if ($inExcludeAssignmentIds) {
            [$notsql, $notparams] = $DB->get_in_or_equal($inExcludeAssignmentIds, SQL_PARAMS_NAMED, 'exc', false);
            $sql .= " AND a.id $notsql";
            $params += $notparams;
        }

        // Fetch with Moodle’s pagination (cross-DB safe)
        $rows = $DB->get_records_sql($sql, $params, 0, $maxNumberOfRowsPerBatch);


        $files = [];
        if ($rows && is_array($rows)) {
            mtrace("Found " . count($rows) . " new files to be processed by LibCrawler");

            foreach ($rows as $row) {
                $relpath = substr($row->contenthash, 0, 2) . '/' . substr($row->contenthash, 2, 2) . '/' . $row->contenthash;
                $fullpath = $CFG->dataroot . '/filedir/' . $relpath;
                if (!file_exists($fullpath)) {
                    mtrace("File $row->id ($fullpath) does not exist");
                    continue;
                }
                $comment = sprintf("Moodle File Id: %s, Assignment Name: %s, User Id: %s", $row->file_id, $row->assignment_name, $row->userid, $row->userid);
                $files[$relpath] = ["id" => $row->file_id, "name" => $row->filename, "date" => $row->timemodified, "author" => "Moodle user #$row->userid", "project" => null, "comment" => $comment];
            }
        } else {
            mtrace("Found no new files to be processed by LibCrawler");
        }

        // Write JSON dump to the index file
        $jsonDump = json_encode($files);
        file_put_contents($indexFilePath, $jsonDump, LOCK_EX);
        mtrace("Created JSON export file: " . $indexFilePath);

        mtrace('Scheduled task for PlagAware Plugin completed.');
    }

    private function scan_file_reverse($file, $fn)
    {
        if (!file_exists($file)) {
            mtrace("File not found: $file");
            return 0;
        }
        $fp = fopen($file, "r");
        if (!$fp) {
            mtrace("Unable to open file file for reading: $file");
            return 0;
        }
        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            mtrace("Unable to get read lock on $file");
            return 0;
        }
        fseek($fp, 0, SEEK_END); // Move to the end

        $pos = ftell($fp);
        $buffer = "";
        $addedCount = 0;
        $skippedCount = 0;
        $maxSkippedCount = 5;   // maximum number of files skipped in a row before we discontinue to look for new records to be added
        while (($pos > 0) && ($skippedCount < $maxSkippedCount)) {
            $pos--;
            fseek($fp, $pos);
            $char = fgetc($fp);

            if ($char === "\n") {
                $line = trim($buffer);
                if ($line) {
                    $wasNew = $fn($line);
                    if (!$wasNew) {  // object was already existing, we reached the end of new entries and quit
                        $skippedCount++;
                    } else {
                        $addedCount++;
                        $skippedCount = 0;
                    }
                }
                $buffer = "";
            } else {
                $buffer = $char . $buffer;
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return $addedCount;
    }
}
