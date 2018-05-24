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
 * Privacy Subsystem implementation for local_intelliboard
 *
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intelliboard\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper as request_helper;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the intelliboard activity module.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider
{

    use subcontext_info;

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        // The 'local_intelliboard_tracking' table stores the metadata about what [managers] can see in the reports.
        $items->add_database_table('local_intelliboard_assign', [
            'userid' => 'privacy:metadata:local_intelliboard_assign:userid',
            'rel' => 'privacy:metadata:local_intelliboard_assign:rel',
            'type' => 'privacy:metadata:local_intelliboard_assign:type',
            'instance' => 'privacy:metadata:local_intelliboard_assign:instance',
            'timecreated' => 'privacy:metadata:local_intelliboard_assign:timecreated',
        ], 'privacy:metadata:local_intelliboard_assign');

        // The 'local_intelliboard_details' table stores the metadata about timespent per-hour.
        $items->add_database_table('local_intelliboard_details', [
            'logid' => 'privacy:metadata:local_intelliboard_details:logid',
            'visits' => 'privacy:metadata:local_intelliboard_details:visits',
            'timespend' => 'privacy:metadata:local_intelliboard_details:timespend',
            'timepoint' => 'privacy:metadata:local_intelliboard_details:timepoint',
        ], 'privacy:metadata:local_intelliboard_details');

        // The 'local_intelliboard_logs' table stores information about timespent per-day.
        $items->add_database_table('local_intelliboard_logs', [
            'trackid' => 'privacy:metadata:local_intelliboard_logs:trackid',
            'visits' => 'privacy:metadata:local_intelliboard_logs:visits',
            'timespend' => 'privacy:metadata:local_intelliboard_logs:timespend',
            'timepoint' => 'privacy:metadata:local_intelliboard_logs:timepoint',
        ], 'privacy:metadata:local_intelliboard_logs');

        // The 'local_intelliboard_totals' table stores information about totals on a site.
        $items->add_database_table('local_intelliboard_totals', [
            'sessions' => 'privacy:metadata:local_intelliboard_totals:sessions',
            'courses' => 'privacy:metadata:local_intelliboard_totals:courses',
            'visits' => 'privacy:metadata:local_intelliboard_totals:visits',
            'timespend' => 'privacy:metadata:local_intelliboard_totals:timespend',
            'timepoint' => 'privacy:metadata:local_intelliboard_totals:timepoint',
        ], 'privacy:metadata:local_intelliboard_totals');

        // The 'local_intelliboard_tracking' table stores the metadata about visits and time.
        $items->add_database_table('local_intelliboard_tracking', [
            'userid' => 'privacy:metadata:local_intelliboard_tracking:userid',
            'courseid' => 'privacy:metadata:local_intelliboard_tracking:courseid',
            'page' => 'privacy:metadata:local_intelliboard_tracking:page',
            'param' => 'privacy:metadata:local_intelliboard_tracking:param',
            'visits' => 'privacy:metadata:local_intelliboard_tracking:visits',
            'timespend' => 'privacy:metadata:local_intelliboard_tracking:timespend',
            'firstaccess' => 'privacy:metadata:local_intelliboard_tracking:firstaccess',
            'lastaccess' => 'privacy:metadata:local_intelliboard_tracking:lastaccess',
            'useragent' => 'privacy:metadata:local_intelliboard_tracking:useragent',
            'useros' => 'privacy:metadata:local_intelliboard_tracking:useros',
            'userlang' => 'privacy:metadata:local_intelliboard_tracking:userlang',
            'userip' => 'privacy:metadata:local_intelliboard_tracking:userip',
        ], 'privacy:metadata:local_intelliboard_tracking');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of intelliboard, that is any intelliboard where the user has made any post, rated any content, or has any preferences.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        return new contextlist();
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;


        $user = $contextlist->get_user();

        $records = $DB->get_records_sql("SELECT (CASE
                WHEN d.id > 0 THEN d.id*l.id*t.id
                WHEN l.id > 0 THEN l.id*t.id*t.id
                    ELSE t.id
            END) AS unid, t.*,
            l.timepoint AS day_time,
            l.visits AS day_visits,
            l.timespend AS day_timespent,
            l.timepoint AS hour_time,
            d.visits AS hour_visits,
            d.timespend AS hour_timespent
            FROM {local_intelliboard_tracking} t
            LEFT JOIN {local_intelliboard_logs} l ON l.trackid = t.id
            LEFT JOIN {local_intelliboard_details} d ON d.logid = l.id
            WHERE t.userid = :userid", ['userid' => $user->id]);

        if (!empty($records)) {
            \core_privacy\local\request\writer::with_context($context)
                    ->export_data([], (object) [
                        'records' => $records,
                    ]);
        }

        $records = $DB->get_records_sql("(
            SELECT id,rel,type,timecreated FROM {local_intelliboard_assign} WHERE userid = :userid)
            UNION
            (SELECT id, rel,type,timecreated FROM {local_intelliboard_assign} WHERE type = 'users' AND instance = :instance)", ['userid' => $user->id, 'instance' => $user->id]);

        if (!empty($records)) {
            \core_privacy\local\request\writer::with_context($context)
                    ->export_data([], (object) [
                        'records' => $records,
                    ]);
        }
    }


    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        return;
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        $DB->delete_records('local_intelliboard_assign', [
            'userid' => $userid,
        ]);
        $DB->delete_records('local_intelliboard_assign', [
            'type' => 'users',
            'instance' => $userid,
        ]);
        $items = $DB->get_records("local_intelliboard_tracking", ['userid' => $userid]);

        foreach ($items as $item) {
            $logs = $DB->get_records("local_intelliboard_logs", ['trackid' => $item->id]);

            foreach ($logs as $log) {
                $DB->delete_records('local_intelliboard_details', [
                    'logid' => $log->id,
                ]);
            }
            $DB->delete_records('local_intelliboard_logs', [
                'trackid' => $item->id,
            ]);
        }
        $DB->delete_records('local_intelliboard_tracking', [
            'userid' => $userid,
        ]);
    }
}
