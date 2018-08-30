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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 * @package    local_intelliboard
 * @copyright  2018 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_intelliboard_report extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function run_report_parameters() {
        return new external_function_parameters(
            array(
                'report' => new external_single_structure(
                    array(
                        'appid' => new external_value(PARAM_INT, 'External app ID'),
                        'debug' => new external_value(PARAM_INT, 'Debug Mode'),
                        'start' => new external_value(PARAM_INT, 'Report pagination'),
                        'length' => new external_value(PARAM_INT, 'Report pagination'),
                    )
                )
            )
        );
    }

    /**
     * Create one report
     *
     * @param array $report.
     * @return array An array of arrays
     * @since Moodle 2.5
     */
    public static function run_report($report) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::run_report_parameters(), array('report' => $report));

        $transaction = $DB->start_delegated_transaction();

        self::validate_context(context_system::instance());

        $params = (object) $params['report'];

        $data = [];

        if ($report = $DB->get_record('local_intelliboard_reports', ['status' => 1, 'appid' => $params->appid])) {
            if ($report->sqlcode) {
                $query = base64_decode($report->sqlcode);

                if ($params->debug === 1) {
                    $CFG->debug = (E_ALL | E_STRICT);
                    $CFG->debugdisplay = 1;
                }

                if ($params->debug === 2) {
                    $data = $report->sqlcode;
                } elseif(isset($params->start) and $params->length != 0 and $params->length != -1){
                    $data = $DB->get_records_sql($query, [], $params->start, $params->length);
                } else {
                    $data = $DB->get_records_sql($query);
                }
            }
        }

        $transaction->allow_commit();

        return ['jsondata' => json_encode($data)];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function run_report_returns() {
       return new external_single_structure(
            array(
                'jsondata' => new external_value(PARAM_RAW, 'Report data'),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function save_report_parameters() {
        return new external_function_parameters(
            array(
                'report' => new external_single_structure(
                    array(
                        'appid' => new external_value(PARAM_INT, 'External app ID'),
                        'name' => new external_value(PARAM_TEXT, 'Report name'),
                        'sqlcode' => new external_value(PARAM_BASE64, 'SQL code of custom report')
                    )
                )
            )
        );
    }

    /**
     * Create one report
     *
     * @param array $report.
     * @return array An array of arrays
     * @since Moodle 2.5
     */
    public static function save_report($report) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::save_report_parameters(), array('report' => $report));

        $transaction = $DB->start_delegated_transaction();

        self::validate_context(context_system::instance());

        $report = (object) $params['report'];

        if ($data = $DB->get_record('local_intelliboard_reports', ['appid' => $report->appid])) {
            $report->id = $data->id;
            $DB->update_record('local_intelliboard_reports', $report);
        } else {
            $report->timecreated = time();
            $DB->insert_record('local_intelliboard_reports', $report);
        }

        $transaction->allow_commit();

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function save_report_returns() {
       return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function delete_report_parameters() {
        return new external_function_parameters(
            array (
                'appid' => new external_value(PARAM_INT, 'External app ID')
            )
        );
    }

    /**
     *
     * @param array $report
     * @return null
     * @since Moodle 2.5
     */
    public static function delete_report($params) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::delete_report_parameters(), array('appid' => $params));

        $transaction = $DB->start_delegated_transaction();

        self::validate_context(context_system::instance());

        $DB->delete_records('local_intelliboard_reports', ['appid' => $params['appid']]);

        $transaction->allow_commit();

        return null;
    }

    public static function delete_report_returns() {
        return null;
    }
}
