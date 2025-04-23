<?php

/**
 * Upgrade script for the Moodle "plagiarism_plagaware" module.
 *
 * @package    plagiarism_plagaware
 * @copyright  2025 Dirk Malthan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mymodule upgrade from one version to another.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Success/Failure.
 */
function xmldb_plagiarism_plagaware_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade step 3: Create a new table 'plagiarism_plagaware_library'.
    if ($oldversion < 2025031307) {
        $table = new xmldb_table('plagiarism_plagaware_library');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('plagaware_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contenthash', XMLDB_KEY_FOREIGN, ['contenthash'], 'files', ['contenthash']);
        $table->add_index('date', XMLDB_INDEX_NOTUNIQUE, ['date']);
        $table->add_index('plagaware_id', XMLDB_INDEX_NOTUNIQUE, ['plagaware_id']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        // Create the table if it does not exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        set_config('create_json_index_file', 0, 'plagiarism_plagaware');
        set_config('index_file_grace_seconds_after_cutoff', 30 * 24 * 60 * 60, 'plagiarism_plagaware');
        set_config('index_file_rows_per_batch', 1000, 'plagiarism_plagaware');
        set_config('index_file_index_path', '/filedir/index.json', 'plagiarism_plagaware');             // relative to data dir
        set_config('index_file_log_path', '/lc-cache/fileslog.json', 'plagiarism_plagaware');           // relative to data dir
        set_config('lc_include_assignment_ids', '', 'plagiarism_plagaware');
        set_config('lc_exclude_assignment_ids', '', 'plagiarism_plagaware');
    
        upgrade_plugin_savepoint(true, 2025031307, 'plagiarism', 'plagaware');
    }

    return true;
}
