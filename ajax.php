<?php
define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$action = optional_param('action', '', PARAM_TEXT);

if($action == 'user_courses_list'){
	require_login();

	$courses = $DB->get_records_sql("SELECT c.id, c.fullname
					FROM {$CFG->prefix}user_enrolments ue
						LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
						LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
						WHERE ue.userid = $USER->id AND c.visible = 1 GROUP BY c.id ORDER BY c.fullname ASC");
	$html = '<option value=""></option>';
	foreach($courses as $course){
		$html .= '<option value="'.$course->id.'">'.$course->fullname.'</option>';
	}
	die($html);
}elseif($action == 'course_users'){
	$courseid = optional_param('courseid', 1, PARAM_INT);
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	$context = context_course::instance($course->id, MUST_EXIST);
	if ($course->id == SITEID) {
	    throw new moodle_exception('invalidcourse');
	}
	require_login($course);
	require_capability('moodle/course:enrolreview', $context);
	$users = get_enrolled_users($context, '', 0);

	$html = '<option value=""></option>';
	foreach($users as $user){
		$html .= '<option value="'.$user->id.'">'.fullname($user).'</option>';
	}
	die($html);
}elseif($action == 'user_course_quizes_list'){
	$courseid = optional_param('courseid', 1, PARAM_INT);
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	$context = context_course::instance($course->id, MUST_EXIST);
	if ($course->id == SITEID) {
	    throw new moodle_exception('invalidcourse');
	}
	require_login($course);
	$quizes = $DB->get_records_sql("SELECT q.id, q.name FROM {$CFG->prefix}quiz q, {$CFG->prefix}quiz_attempts qa
					WHERE qa.userid = $USER->id AND q.id = qa.quiz AND q.course = $courseid GROUP BY q.id ORDER BY q.name ASC");

	$html = '<option value=""></option>';
	foreach($quizes as $quiz){
		$html .= '<option value="'.$quiz->id.'">'.$quiz->name.'</option>';
	}
	die($html);
}elseif($action == 'user_fields'){
	$custom = optional_param('custom', 0, PARAM_INT);

	require_login();

	$items = $DB->get_records_sql("SELECT id, name FROM {$CFG->prefix}user_info_field ORDER BY name");

	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'" '.(($custom==$item->id)?"selected=selected":"").'>'.$item->name.'</option>';
	}
	die($html);
}elseif($action == 'user_groups_list'){
	$mode = optional_param('mode', 1, PARAM_INT);

	require_login();

	if($mode){
		$items = $DB->get_records_sql("SELECT id, name FROM {$CFG->prefix}local_elisprogram_uset ORDER BY name");
	}else{
		$items = $DB->get_records_sql("SELECT id, name FROM {$CFG->prefix}cohort ORDER BY name");
	}

	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'">'.$item->name.'</option>';
	}
	die($html);
}else{
	insert_intelliboard_tracking(true);
}
