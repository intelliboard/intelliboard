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

class local_intelliboard_notificationlib extends external_api {

    public static function send_notifications_parameters() {
        return new external_function_parameters(
            array(
                'notifications'  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'         => new external_value(PARAM_INT, 'Notification id'),
                            'type'       => new external_value(PARAM_INT, 'Notification type'),
                            'name'       => new external_value(PARAM_TEXT, 'Notification name'),
                            'userid'     => new external_value(PARAM_INT, 'User that created notification'),
                            'email'      => new external_value(PARAM_TEXT, 'Email where this notification should to go'),
                            'subject'    => new external_value(PARAM_TEXT, 'Notification subject'),
                            'message'    => new external_value(PARAM_RAW, 'Notification message'),
                            'attachment' => new external_value(PARAM_TEXT, 'Notification attachment', VALUE_OPTIONAL, ''),
                            'params'     => new external_value(PARAM_TEXT, 'Notification dynamic params', VALUE_OPTIONAL, '{}'),
                            'tags'       => new external_value(PARAM_TEXT, 'Notification tags', VALUE_OPTIONAL, '{}'),
                            'frequency'  => new external_value(PARAM_INT, 'Notification frequency'),
                        )
                    )
                )
            )
        );
    }

    public static function send_notifications($notifications) {
        $notifications = array_map(function($notification) {
            $notification['params'] = json_decode($notification['params'], true);
            $notification['tags'] = json_decode($notification['tags'], true);
            return $notification;
        }, $notifications);

        $notification = new local_intelliboard_notification();
        $notification->send_notifications($notifications);

        return array('state' => true);
    }

    public static function send_notifications_returns() {
        return new external_single_structure(
            array(
                'state' => new external_value(PARAM_BOOL, 'State'),
            )
        );
    }

}