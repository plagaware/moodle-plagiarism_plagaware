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

$string['pluginname']                   = 'PlagAware Plagiatsprüfung';
$string['plagaware']                    = 'PlagAware';
$string['usercode']                   	= 'User Code';
$string['enabled']                      = 'PlagAware Aktivieren';
$string['plagaware_auto_post']          = 'Automatische Übergabe an PlagAware';
$string['enabled_help']                 = 'Plagiatsprüfung von PlagAware in Aufgaben ermöglichen';
$string['saved_plagaware_settings']     = 'Einstellungen erfolgreich gespeichert';
$string['plagaware_settings_header']    = 'PlagAware Einstellungen';
$string['view_report']                  = 'Ansehen';
$string['view_report_pdf']              = 'PDF';
$string['wait_for_report']              = 'Warte auf PlagAware...';
$string['send_file']                    = 'Plagiatsprüfung...';
$string['submited']                     = 'Plagiatsprüfung gestartet';
$string['plagawareuse']                 = 'PlagAware Aktivieren'; //plagaware_use
$string['error']                        = 'Plagiatsprüfung fehlgeschlagen';
$string['timeout']                      = 'Timeout';
$string['restart']                      = 'Plagiatsprüfung neu starten...';
$string['submission_error']             = 'Ein oder mehrere Dateien konnten nicht an PlagAware übergeben werden';
$string['debugmode']                    = 'Debug-Modus';

$string['privacy:metadata:table'] = 'Speichert Plagiatsergebnisse für vom Benutzer eingereichte Dateien.';
$string['privacy:metadata:userid'] = 'Die ID des Benutzers, der die Datei eingereicht hat.';
$string['privacy:metadata:assignid'] = 'Die ID der Aufgabe, für die die Datei eingereicht wurde.';
$string['privacy:metadata:fileid'] = 'Die ID der Moodle-Datei.';
$string['privacy:metadata:result'] = 'Der berechnete Ähnlichkeitswert der Einreichung.';

$string['taskname'] = 'Geplante Aufgaben für das PlagAware Plugin';

$string['common_header'] = 'Allgemeine Einstellungen';
$string['lc_header'] = 'Synchronisation mit der Bibliothek';
$string['lc_text'] = 'Hinweis: Die Synchronisation der eingereichten Arbeiten mit der PlagAware Bibliothek benötigt die Installation des PlagAware LibCrawlers. Für weitere Details setzen Sie sich bitte mit Ihrem IT-Support und/oder dem PlagAware Support in Verbindung.';
$string['lc_file_count'] = 'Zu übertragende Dateien: %s';
$string['lc_exclude_assignments'] = 'Auszuschließende Assignments';
$string['lc_exclude_assignments_help'] = 'Komma-separierte Liste der Assignment Ids, welche von der Datei-Synchronisation ausgeschlossen werden sollen.';
$string['lc_include_assignments'] = 'Einzuschließende Assignments';
$string['lc_include_assignments_help'] = 'Komma-separierte Liste der Assignmment Ids, auf welche die Datei-Synchronisation beschränkt werden soll.';
$string['create_json_index_file'] = 'Index-File erstellen und Synchronisierung aktivieren';
$string['create_json_index_file_short'] = 'Synchronisierung aktivieren';
$string['index_file_grace_seconds_after_cutoff'] = 'Vergangene Dauer nach Cutoff-Datum, ab dem Texte hochgeladen werden';
$string['index_file_index_path'] = 'Pfad und Name des Index-Files, relativ zum Datenverzeichnis (/moodledata)';
$string['index_file_log_path'] = 'Pfad und Name des LibCrawler-Logfiles, relativ zum Datenverzeichnis (/moodledata)';
$string['index_file_rows_per_batch'] = 'Maximale Anzahl der hochzuladenden Dokumente pro Batch';
$string['expert_header'] = 'Experten-Einstellungen und Debugging';