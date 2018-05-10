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
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_intelliboard_search extends external_api {

    public static function get_param_values_parameters() {
        return new external_function_parameters(array(
            'options' => new external_single_structure(array(
                'table'  => new external_value(PARAM_ALPHANUMEXT, 'Table name'),
                'column' => new external_value(PARAM_ALPHANUMEXT, 'Column name'),
                'params' => new external_single_structure(self::intelliboard_params()),
                'length' => new external_value(PARAM_ALPHANUM, 'How many values return', VALUE_OPTIONAL, 0),
                'like'  => new external_value(PARAM_ALPHANUM, 'Like filter', VALUE_OPTIONAL),
                'id' => new external_value(PARAM_ALPHANUM, 'Search between existing ids', VALUE_OPTIONAL, null),
                'filters' => new external_value(PARAM_TEXT, 'additionalFilters', VALUE_OPTIONAL, array()),
                'additionalFields' => new external_value(PARAM_TEXT, 'Additional Filters', VALUE_OPTIONAL, '[]')
            ))
        ));
    }


    public static function get_param_values($options) {
        global $CFG;
        require_once($CFG->dirroot . '/local/intelliboard/search/src/autoload.php');

        extract($options);
        $filters = json_decode($filters, true);
        $additionalFields = json_decode($additionalFields, true);
        $params['length'] = $length;
        $values = Helpers\DB::getParamsFromDB($table, $column, $params, $length, $like, $id, 0, $filters, $additionalFields);

        return $values;
    }

    public static function get_param_values_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_TEXT, 'Found Value ID', VALUE_OPTIONAL),
                    'value' => new external_value(PARAM_TEXT, 'Found Value')
                )
            )
        );
    }

    public static function get_data_by_query_parameters() {
        return new external_function_parameters(
            array(
                'scenarios' => new external_value(PARAM_TEXT, 'DB requests'),
                'arguments' => new external_value(PARAM_TEXT, 'DB arguments'),
                'debug'     => new external_value(PARAM_BOOL, 'Should plugin return debug info?', VALUE_OPTIONAL)
            )
        );
    }

    public static function get_data_by_query($scenarios, $arguments, $debug) {
        global $CFG;
        require_once($CFG->dirroot . '/local/intelliboard/search/src/autoload.php');

        $scenarios = json_decode($scenarios, true);
        $arguments = json_decode($arguments, true);
        $extractor = new DataExtractor($scenarios, $arguments, compact( 'debug'), $CFG->dbtype);

        $response = $extractor->extract();

        $response['response'] = json_encode($response['response']);
        $response['debug'] = json_encode($response['debug']);

        return $response;
    }

    public static function get_data_by_query_returns() {
        return new external_single_structure(array(
            'response' => new external_value(PARAM_TEXT, 'DB records'),
            'debug' => new external_value(PARAM_RAW, 'Debug info', VALUE_OPTIONAL)
        ));
    }

    public static function extract_db_params_from_sentence_parameters() {
        return new external_function_parameters(
            array(
                'patterns' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'table'  => new external_value(PARAM_ALPHANUMEXT, 'Table name'),
                            'column' => new external_value(PARAM_ALPHANUMEXT, 'Column name'),
                        )
                    )
                ),
                'sentence' => new external_value(PARAM_TEXT, 'Sentence where parameters will be found'),
                'params' => new external_single_structure(self::intelliboard_params()),
                'pluralize' => new external_value(PARAM_INT, 'Pluralize values or not', VALUE_OPTIONAL, 0),
                'escape_system' => new external_value(PARAM_INT, 'Escape system words or not', VALUE_OPTIONAL, 0),
                'additionalFields' => new external_value(PARAM_TEXT, 'Additional Filters', VALUE_OPTIONAL, '[]')
            )
        );
    }

    public static function extract_db_params_from_sentence($patterns, $sentence, $params, $pluralize = 0, $escapeSystem = 0, $additionalFields = array()){
        global $CFG;
        require_once($CFG->dirroot . '/local/intelliboard/search/src/autoload.php');

        $response = array('result' => array(), 'sentence' => $sentence);

        foreach($patterns as $pattern) {

            $response = Helpers\DB::extractParamsFromSentence($pattern['table'], $pattern['column'], $sentence, $params, $pluralize, $escapeSystem, json_decode($additionalFields, true));

            if (!empty($response['result'])) {
                return $response;
            }

        }

        return $response;
    }

    public static function extract_db_params_from_sentence_returns() {
        return new external_single_structure(
            array(
                'sentence' => new external_value(PARAM_TEXT, 'Sentence after extracting parameter'),
                'result' => new external_value(PARAM_TEXT, 'Result JSON')
            )
        );
    }

    public static function process_auto_complete_db_parameters() {
        return new external_function_parameters(
            array(
                'table'  => new external_value(PARAM_ALPHANUMEXT, 'Table name'),
                'column' => new external_value(PARAM_ALPHANUMEXT, 'Column name'),
                'remainder' => new external_value(PARAM_TEXT, 'Remainder from Sentence'),
                'params' => new external_single_structure(self::intelliboard_params()),
            )
        );
    }

    public static function process_auto_complete_db($table, $column, $remainder, $params){
        global $CFG;
        require_once($CFG->dirroot . '/local/intelliboard/search/src/autoload.php');

        return Helpers\DB::processAutoCompleteDb($table, $column, $remainder, $params);
    }

    public static function process_auto_complete_db_returns() {
        return new external_single_structure(
            array(
                'maxShift' => new external_value(PARAM_INT, 'Determine number of words, auto complete must shift pointer, to pass this argument'),
                'endings'  => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Possible ending to this argument, if argument is bigger than remainder of sentence')
                ),
                'found' => new external_value(PARAM_TEXT, 'Found value')
            )
        );
    }


    public static function check_installed_plugins_parameters() {
        return new external_function_parameters(
            array(
                'plugins' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Plugin name')
                ),
            )
        );
    }

    public static function check_installed_plugins($plugins){
        $pluginManager = core_plugin_manager::instance();

        return array_filter($plugins, function($plugin) use($pluginManager) {
            return !$pluginManager->get_plugin_info($plugin);
        });
    }

    public static function check_installed_plugins_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_TEXT, 'Plugin name')
        );
    }

    public static function get_gradebook_fields_parameters() {
        return new external_function_parameters(
            array(
                'course' => new external_value(PARAM_INT, 'Course ID'),
            )
        );
    }

    public static function get_gradebook_fields($course){
        global $DB;

        $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1");
        $sql_columns = '';
        foreach($modules as $module){
            $sql_columns .= " WHEN gi.itemmodule='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = gi.iteminstance)";
        }
        $sql_columns .=  " WHEN gi.itemtype='category' THEN (SELECT fullname FROM {grade_categories} WHERE id = gi.iteminstance)";
        $sql_columns = ($sql_columns)? "CASE $sql_columns ELSE 'NONE' END AS field" : "'' AS field";

        $fields = $DB->get_records_sql("SELECT gi.id, $sql_columns FROM {grade_items} AS gi WHERE (gi.itemtype = 'mod' OR gi.itemtype = 'category') AND gi.courseid=:course ", array('course'=>$course));

        return $fields;
    }

    public static function get_gradebook_fields_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Activity ID'),
                'field' => new external_value(PARAM_TEXT, 'Field name'),
            ))
        );
    }

    protected static function intelliboard_params() {
        return array(
            'filter_user_deleted'       => new external_value(PARAM_INT, 'filter_user_deleted', VALUE_OPTIONAL, 0),
            'filter_user_suspended'     => new external_value(PARAM_INT, 'filter_user_suspended', VALUE_OPTIONAL, 0),
            'filter_user_guest'         => new external_value(PARAM_INT, 'filter_user_guest', VALUE_OPTIONAL, 0),
            'filter_course_visible'     => new external_value(PARAM_INT, 'filter_course_visible', VALUE_OPTIONAL, 0),
            'filter_enrolmethod_status' => new external_value(PARAM_INT, 'filter_enrolmethod_status', VALUE_OPTIONAL, 0),
            'filter_enrol_status'       => new external_value(PARAM_INT, 'filter_enrol_status', VALUE_OPTIONAL, 0),
            'filter_enrolled_users'     => new external_value(PARAM_INT, 'filter_enrolled_users', VALUE_OPTIONAL, 0),
            'filter_module_visible'     => new external_value(PARAM_INT, 'filter_module_visible', VALUE_OPTIONAL, 0),
            'learner_roles'             => new external_value(PARAM_SEQUENCE, 'Learner Roles', VALUE_OPTIONAL, '5'),
            'teacher_roles'             => new external_value(PARAM_SEQUENCE, 'Teacher Roles', VALUE_OPTIONAL, '1,2,3'),
            'external_id'               => new external_value(PARAM_INT, 'Intelliboard User ID', VALUE_OPTIONAL, null),
        );
    }

}
