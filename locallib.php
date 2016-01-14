<?php
// IntelliBoard.net
//
// IntelliBoard.net is built to work with any LMS designed in Moodle
// with the goal to deliver educational data analytics to single dashboard instantly.
// With power to turn this analytical data into simple and easy to read reports,
// IntelliBoard.net will become your primary reporting tool.
//
// Moodle
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// IntelliBoard.net is built as a local plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	intelliboard
 * @copyright  	2015 IntelliBoard, Inc
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	IntelliBoard, Inc
 * @website		www.intelliboard.net
 */

class intelliboard_handler {
    public static function notify_leaner_created($user)
	{
        global $CFG, $SITE, $DB;

		$auth = get_config('local_intelliboard','auth');
		$auth_email = get_config('local_intelliboard','auth_email');
		$auth_subject = get_config('local_intelliboard','auth_subject');
		$auth_message = get_config('local_intelliboard','auth_message');

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



	public static function notify_leaner_enrolled($enroll) {
        global $CFG, $SITE, $DB;

		$user = $DB->get_record('user', array('id'=> $enroll->userid));
		$course = $DB->get_record('course', array('id'=> $enroll->courseid));

		$enrol = get_config('local_intelliboard','enrol');
		$enrol_email = get_config('local_intelliboard','enrol_email');
		$enrol_subject = get_config('local_intelliboard','enrol_subject');
		$enrol_message = get_config('local_intelliboard','enrol_message');

		if($enrol and $enrol_email and $enrol_subject and $enrol_message) {
			$enrol = explode(",", $enrol);
			if(!in_array($enroll->enrol, $enrol)){
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
