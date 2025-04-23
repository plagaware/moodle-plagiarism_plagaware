<?php

namespace plagiarism_plagaware\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use context_module;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\core_userlist_provider;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class provider implements
    metadata_provider,
    plugin_provider,
    core_userlist_provider
{

    /**
     * Describe the personal data stored by this plugin.
     */
    public static function get_metadata(collection $items): collection
    {
        $items->add_database_table('plagiarism_plagaware', [
            'userid' => 'privacy:metadata:userid',
            'assignid' => 'privacy:metadata:assignid',
            'fileid' => 'privacy:metadata:fileid',
            'result' => 'privacy:metadata:result',
        ], 'privacy:metadata:table');

        return $items;
    }

    /**
     * Get contexts that contain user information.
     */
    public static function get_contexts_for_userid(int $userid): contextlist
    {
        global $DB;

        $sql = "SELECT ctx.id
                  FROM {plagiarism_plagaware} p
                  JOIN {assign} a ON a.id = p.assignid
                  JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = :moduleid
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE p.userid = :userid";

        $params = [
            'userid' => $userid,
            'moduleid' => self::get_module_id('assign'),
            'contextlevel' => CONTEXT_MODULE,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export user data from the plugin.
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $cmid = $context->instanceid;

            // Find assign id from cmid
            $sql = "SELECT a.id
                      FROM {assign} a
                      JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = :moduleid
                     WHERE cm.id = :cmid";
            $assignid = $DB->get_field_sql($sql, [
                'moduleid' => self::get_module_id('assign'),
                'cmid' => $cmid
            ]);

            if (!$assignid) {
                continue;
            }

            $records = $DB->get_records('plagiarism_plagaware', [
                'assignid' => $assignid,
                'userid' => $userid
            ]);

            if ($records) {
                writer::with_context($context)->export_data(['Plagiarism results'], (object)['records' => array_values($records)]);
            }
        }
    }

    /**
     * Delete all user data in the given context.
     */
    public static function delete_data_for_all_users_in_context(\context $context)
    {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $assignid = self::get_assign_id_from_context($context);
        if ($assignid) {
            $DB->delete_records('plagiarism_plagaware', ['assignid' => $assignid]);
        }
    }

    /**
     * Delete data for a single user in the given context.
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist)
    {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $assignid = self::get_assign_id_from_context($context);
            if ($assignid) {
                $DB->delete_records('plagiarism_plagaware', [
                    'assignid' => $assignid,
                    'userid' => $userid
                ]);
            }
        }
    }

    /**
     * Get a list of user IDs who have data in the given context.
     */
    public static function get_userids_from_context(userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $assignid = self::get_assign_id_from_context($context);
        if (!$assignid) {
            return;
        }

        $userids = $DB->get_fieldset_select('plagiarism_plagaware', 'userid', 'assignid = ?', [$assignid]);

        $userlist->add_users($userids);
    }

    /**
     * Delete data for multiple users in a given context.
     */
    public static function delete_data_for_users(approved_userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $assignid = self::get_assign_id_from_context($context);
        if (!$assignid) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['assignid'] = $assignid;

        $DB->delete_records_select('plagiarism_plagaware', "assignid = :assignid AND userid $sqlin", $params);
    }

    /**
     * Resolve the assign ID from a context.
     */
    private static function get_assign_id_from_context(\context $context): ?int
    {
        global $DB;

        $sql = "SELECT a.id
                  FROM {assign} a
                  JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = :moduleid
                 WHERE cm.id = :cmid";
        return $DB->get_field_sql($sql, [
            'moduleid' => self::get_module_id('assign'),
            'cmid' => $context->instanceid,
        ]);
    }

    /**
     * Get the module ID for a given module name.
     */
    private static function get_module_id(string $modname): int
    {
        global $DB;
        static $ids = [];

        if (!isset($ids[$modname])) {
            $ids[$modname] = (int)$DB->get_field('modules', 'id', ['name' => $modname]);
        }

        return $ids[$modname];
    }

    /**
     * Return the list of users who have data in the given context.
     *
     * @param \core_privacy\local\request\userlist $userlist The userlist to append to.
     */
    public static function get_users_in_context(userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $assignid = self::get_assign_id_from_context($context);
        if (!$assignid) {
            return;
        }

        $userids = $DB->get_fieldset_select('plagiarism_plagaware', 'userid', 'assignid = ?', [$assignid]);

        if (!empty($userids)) {
            $userlist->add_users($userids);
        }
    }
}
