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
 * @website    https://intelliboard.net/
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/lib.php');

$action = optional_param('action', '', PARAM_TEXT);

require_login();
$PAGE->set_context(context_system::instance());

if($action == 'user_courses_list'){
	$courses = enrol_get_users_courses($USER->id, true, 'id, fullname');

	$html = '<option value=""></option>';
	foreach($courses as $course){
		$html .= '<option value="'.$course->id.'">'.format_string($course->fullname).'</option>';
	}
	die($html);
}elseif($action == 'course_users'){
	$courseid = optional_param('courseid', 1, PARAM_INT);
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	$context = context_course::instance($course->id, MUST_EXIST);
	if ($course->id == SITEID) {
	    throw new moodle_exception('invalidcourse');
	}
	require_capability('moodle/course:enrolreview', $context);
	$users = get_enrolled_users($context, '', 0);

	$html = '<option value=""></option>';
	foreach($users as $user){
		$html .= '<option value="'.$user->id.'">'.fullname($user).'</option>';
	}
	die($html);
}elseif($action == 'user_course_quizes_list'){
	$courseid = optional_param('courseid', 0, PARAM_INT);
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	$params = array('userid'=>$USER->id, 'courseid'=>$courseid);
	$quizes = $DB->get_records_sql("SELECT q.id, q.name
					FROM {quiz} q, {quiz_attempts} qa
					WHERE qa.userid = :userid AND q.id = qa.quiz AND q.course = :courseid
					GROUP BY q.id ORDER BY q.name ASC", $params);

	$html = '<option value=""></option>';
	foreach($quizes as $quiz){
		$html .= '<option value="'.$quiz->id.'">'.s($quiz->name).'</option>';
	}
	die($html);
}elseif($action == 'user_fields'){
	$custom = optional_param('custom', 0, PARAM_INT);
	$items = $DB->get_records("user_info_field", array(), "name ASC", "id, name");
	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'" '.(($custom==$item->id)?"selected=selected":"").'>'.s($item->name).'</option>';
	}
	die($html);
}elseif($action == 'user_groups_list'){
	$mode = optional_param('mode', 1, PARAM_INT);
	if($mode){
		$items = $DB->get_records("local_elisprogram_uset", array(), "name ASC", "id, name");
	}else{
		$items = $DB->get_records("cohort", array(), "name ASC", "id, name");
	}
	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'">'.s($item->name).'</option>';
	}
	die($html);
}else{
	local_intelliboard_insert_tracking(true);
}
