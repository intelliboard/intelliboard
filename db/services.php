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
 *
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

// We defined the web service functions to install.
$functions = array(
        'local_intelliboard_database_query' => array(
                'classname'   => 'local_intelliboard_external',
                'methodname'  => 'database_query',
                'classpath'   => 'local/intelliboard/externallib.php',
                'description' => 'Return JSON db records',
                'type'        => 'write',
        ),
        'local_intelliboard_save_assigns' => array(
                'classname'   => 'local_intelliboard_assign',
                'methodname'  => 'save_assigns',
                'classpath'   => 'local/intelliboard/classes/assignlib.php',
                'description' => 'Save intelliboard assigns',
                'type'        => 'write',
        ),
        'local_intelliboard_delete_assigns' => array(
                'classname'   => 'local_intelliboard_assign',
                'methodname'  => 'delete_assigns',
                'classpath'   => 'local/intelliboard/classes/assignlib.php',
                'description' => 'Delete intelliboard assigns',
                'type'        => 'write',
        ),
        'local_intelliboard_run_report' => array(
                'classname'   => 'local_intelliboard_report',
                'methodname'  => 'run_report',
                'classpath'   => 'local/intelliboard/classes/reportlib.php',
                'description' => 'Run intelliboard custom report',
                'type'        => 'read',
        ),
        'local_intelliboard_save_report' => array(
                'classname'   => 'local_intelliboard_report',
                'methodname'  => 'save_report',
                'classpath'   => 'local/intelliboard/classes/reportlib.php',
                'description' => 'Save intelliboard custom report',
                'type'        => 'write',
        ),
        'local_intelliboard_delete_report' => array(
                'classname'   => 'local_intelliboard_report',
                'methodname'  => 'delete_report',
                'classpath'   => 'local/intelliboard/classes/reportlib.php',
                'description' => 'Delete intelliboard custom report',
                'type'        => 'write',
        ),
        'local_intelliboard_get_param_values' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'get_param_values',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Get field values from DB that match condition',
            'type'        => 'read',
        ),
        'local_intelliboard_get_data_by_query' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'get_data_by_query',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Get scenario and return db data',
            'type'        => 'read',
        ),
        'local_intelliboard_extract_db_params_from_sentence' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'extract_db_params_from_sentence',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Get sentence and extract all db parameters which it has',
            'type'        => 'read',
        ),
        'local_intelliboard_process_auto_complete_db' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'process_auto_complete_db',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Get sentence, remove given parameter from it, and return word count of parameter',
            'type'        => 'read',
        ),
        'local_intelliboard_check_installed_plugins' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'check_installed_plugins',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Check, if required plugins has been installed',
            'type'        => 'read',
        ),
        'local_intelliboard_get_gradebook_fields' => array(
            'classname'   => 'local_intelliboard_search',
            'methodname'  => 'get_gradebook_fields',
            'classpath'   => 'local/intelliboard/classes/searchlib.php',
            'description' => 'Return gradebook fields',
            'type'        => 'read',
        ),
        'local_intelliboard_send_notifications' => array(
            'classname'   => 'local_intelliboard_notificationlib',
            'methodname'  => 'send_notifications',
            'classpath'   => 'local/intelliboard/classes/notificationlib.php',
            'description' => 'Work with notifications;',
            'type'        => 'read',
        ),
        'local_intelliboard_save_notification' => array(
            'classname'   => 'local_intelliboard_notificationlib',
            'methodname'  => 'save_notification',
            'classpath'   => 'local/intelliboard/classes/notificationlib.php',
            'description' => 'Save event notification;',
            'type'        => 'write',
        ),
        'local_intelliboard_delete_notification' => array(
            'classname'   => 'local_intelliboard_notificationlib',
            'methodname'  => 'delete_notification',
            'classpath'   => 'local/intelliboard/classes/notificationlib.php',
            'description' => 'Delete event notification;',
            'type'        => 'write',
        ),
        'local_intelliboard_get_history' => array(
            'classname'   => 'local_intelliboard_notificationlib',
            'methodname'  => 'get_history',
            'classpath'   => 'local/intelliboard/classes/notificationlib.php',
            'description' => 'Get notification history;',
            'type'        => 'read',
        ),
        'local_intelliboard_clear_notifications' => array(
            'classname'   => 'local_intelliboard_notificationlib',
            'methodname'  => 'clear_notifications',
            'classpath'   => 'local/intelliboard/classes/notificationlib.php',
            'description' => 'Clear all saved notifications and history;',
            'type'        => 'write',
        ),
        'local_intelliboard_attendance_api' => array(
            'classname'   => 'local_intelliboard_attendancelib',
            'methodname'  => 'attendance_api',
            'classpath'   => 'local/intelliboard/classes/attendancelib.php',
            'description' => 'Attendance API',
            'type'        => 'read',
        ),
        'local_intelliboard_save_instructor_courses' => array(
            'classname'   => 'local_intelliboard_instructorlib',
            'methodname'  => 'save_instructor_courses',
            'classpath'   => 'local/intelliboard/classes/instructorlib.php',
            'description' => 'Save instructor courses',
            'type'        => 'write',
            'ajax'        => true
        ),
    'local_intelliboard_setup_base_settings' => array(
        'classname'   => 'local_intelliboard_setuplib',
        'methodname'  => 'save_base_settings',
        'classpath'   => 'local/intelliboard/classes/setuplib.php',
        'description' => 'Save settings',
        'type'        => 'write',
        'ajax'        => true
    ),
    'local_intelliboard_setup_login' => array(
        'classname'   => 'local_intelliboard_setuplib',
        'methodname'  => 'login',
        'classpath'   => 'local/intelliboard/classes/setuplib.php',
        'description' => 'Intelliboard login',
        'type'        => 'write',
        'ajax'        => true
    ),
    'local_intelliboard_setup_register' => array(
        'classname'   => 'local_intelliboard_setuplib',
        'methodname'  => 'register',
        'classpath'   => 'local/intelliboard/classes/setuplib.php',
        'description' => 'Intelliboard register',
        'type'        => 'write',
        'ajax'        => true
    ),
    'local_intelliboard_setup_check_email' => array(
        'classname'   => 'local_intelliboard_setuplib',
        'methodname'  => 'check_email',
        'classpath'   => 'local/intelliboard/classes/setuplib.php',
        'description' => 'Check email',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_intelliboard_setup_apikey' => array(
        'classname'   => 'local_intelliboard_apikeylib',
        'methodname'  => 'save_apikey',
        'classpath'   => 'local/intelliboard/classes/apikeylib.php',
        'description' => 'Save API key',
        'type'        => 'write'
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'IntelliBoard service' => array(
        'functions' => array (
            'local_intelliboard_database_query',
            'local_intelliboard_save_assigns',
            'local_intelliboard_delete_assigns',
            'local_intelliboard_run_report',
            'local_intelliboard_save_report',
            'local_intelliboard_delete_report',
            'local_intelliboard_get_param_values',
            'local_intelliboard_get_data_by_query',
            'local_intelliboard_extract_db_params_from_sentence',
            'local_intelliboard_process_auto_complete_db',
            'local_intelliboard_check_installed_plugins',
            'local_intelliboard_get_gradebook_fields',
            'local_intelliboard_send_notifications',
            'local_intelliboard_save_notification',
            'local_intelliboard_delete_notification',
            'local_intelliboard_get_history',
            'local_intelliboard_clear_notifications',
            'local_intelliboard_attendance_api',
            'local_intelliboard_save_instructor_courses',
            'local_intelliboard_setup_base_settings',
            'local_intelliboard_setup_login',
            'local_intelliboard_setup_register',
            'local_intelliboard_setup_check_email',
            'local_intelliboard_setup_apikey',
        ),
        'restrictedusers' => 1,
        'enabled'=>1,
    )
);
