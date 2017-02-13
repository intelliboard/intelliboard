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

defined('MOODLE_INTERNAL') || die();

class local_intelliboard_observer
{
    public static function user_created(\core\event\user_created $event)
    {
        global $DB;

		$auth = get_config('local_intelliboard','auth');
		$auth_email = get_config('local_intelliboard','auth_email');
		$auth_subject = get_config('local_intelliboard','auth_subject');
		$auth_message = get_config('local_intelliboard','auth_message');

		$user = $DB->get_record('user', array('id'=>$event->objectid));

		if($auth and $auth_email and $auth_subject and $auth_message) {
			$auth = explode(",", $auth);
			if(!in_array($user->auth, $auth)){
				return;
			}
			$sender = get_admin();
			$manager = get_admin();
			$manager->email = $auth_email;

			$subject = str_replace('[[user]]', fullname($user), $auth_subject);
			$message = str_replace('[[user]]', fullname($user), $auth_message);

			email_to_user($manager,$sender,$subject,$message,$message);
		}
    }
    public static function user_enrolment_created(\core\event\user_enrolment_created $event)
    {
        global $DB;

		$user = $DB->get_record('user', array('id'=>$event->relateduserid));
		$course = $DB->get_record('course', array('id'=>$event->contextinstanceid));

		$enrol = get_config('local_intelliboard','enrol');
		$enrol_email = get_config('local_intelliboard','enrol_email');
		$enrol_subject = get_config('local_intelliboard','enrol_subject');
		$enrol_message = get_config('local_intelliboard','enrol_message');

		if($enrol and $enrol_email and $enrol_subject and $enrol_message) {
			$enrol = explode(",", $enrol);
			if(!in_array($event->other['enrol'], $enrol)){
				return;
			}
			$sender = get_admin();
			$manager = get_admin();
			$manager->email = $enrol_email;

			$subject = str_replace(array('[[user]]', '[[course]]'), array(fullname($user), $course->fullname), $enrol_subject);
			$message = str_replace(array('[[user]]', '[[course]]'), array(fullname($user), $course->fullname), $enrol_message);

			email_to_user($manager,$sender,$subject,$message,$message);
		}
		return true;
    }
}
