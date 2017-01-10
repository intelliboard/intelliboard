<?php
define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/lib.php');

$action = optional_param('action', '', PARAM_TEXT);

if($action == 'user_courses_list'){
	require_login();

	$courses = $DB->get_records_sql("SELECT c.id, c.fullname
									FROM {user_enrolments} ue
										LEFT JOIN {enrol} e ON e.id = ue.enrolid
										LEFT JOIN {course} c ON c.id = e.courseid
									WHERE ue.userid = $USER->id AND c.visible = 1
									GROUP BY c.id
									ORDER BY c.fullname ASC");
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
	$courseid = optional_param('courseid', 0, PARAM_INT);
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	$context = context_course::instance($course->id, MUST_EXIST);
	if ($course->id == SITEID) {
	    throw new moodle_exception('invalidcourse');
	}
	require_login($course);
	$quizes = $DB->get_records_sql("SELECT q.id, q.name
									FROM {quiz} q, {quiz_attempts} qa
									WHERE qa.userid = $USER->id AND q.id = qa.quiz AND q.course = $courseid
									GROUP BY q.id
									ORDER BY q.name ASC");

	$html = '<option value=""></option>';
	foreach($quizes as $quiz){
		$html .= '<option value="'.$quiz->id.'">'.$quiz->name.'</option>';
	}
	die($html);
}elseif($action == 'user_fields'){
	$custom = optional_param('custom', 0, PARAM_INT);

	require_login();

	$items = $DB->get_records_sql("SELECT id, name FROM {user_info_field} ORDER BY name");

	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'" '.(($custom==$item->id)?"selected=selected":"").'>'.$item->name.'</option>';
	}
	die($html);
}elseif($action == 'cm_completions'){
	$id = optional_param('id', 0, PARAM_INT);
	$cm = $DB->get_record('course_modules', array('id'=>$id), '*', MUST_EXIST);
	$module = $DB->get_record('modules', array('id'=>$cm->module), '*', MUST_EXIST);
	$instance = $DB->get_record($module->name, array('id'=>$cm->instance), '*', MUST_EXIST);
	$learner_roles = get_config('local_intelliboard', 'filter11');

	require_login();
	require_capability('moodle/course:manageactivities', context_module::instance($cm->id));

	$items = $DB->get_records_sql("SELECT c.id, c.completionstate, c.timemodified, c.userid, u.firstname, u.lastname, u.email, (g.finalgrade/g.rawgrademax)*100 as grade
		FROM {course_modules_completion c
			LEFT JOIN {course_modules} cm ON cm.id = c.coursemoduleid
		    LEFT JOIN {modules} m ON m.id = cm.module
			LEFT JOIN {user} u ON u.id = c.userid
		    LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.iteminstance = cm.instance
			LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL
		    WHERE c.coursemoduleid = $id AND c.userid IN (SELECT ra.userid FROM {role_assignments} AS ra JOIN {context} AS ctx ON ra.contextid = ctx.id WHERE ra.roleid IN ($learner_roles) AND ctx.instanceid = $cm->course)");

	$html = '<h2>'.$instance->name.' Completions</h2>';
	$html .= '<table class="table table-hover table-striped">';
	$html .= '<thead><tr>';
	$html .= '<th>User Name</th>';
	$html .= '<th>Email</th>';
	$html .= '<th>Completion status</th>';
	$html .= '<th>Score</th>';
	$html .= '<th></th>';
	$html .= '</tr></thead>';
	foreach($items as $item){
		$html .= '<tr>';
		$html .= '<td><a href="value="'.$user->userid.'">'.fullname($item).'</a></td>';
		$html .= '<td>'. $item->email .'</td>';
		$html .= '<td>'. (($item->completionstate==1)?'Completed on '.date('m/d/Y', $item->timemodified):'Incomplete') .'</td>';
		$html .= '<td>'. round($item->grade, 2) .'</td>';
		$html .= '</tr>';
	}
	$html .= '</table>';
	die($html);
}elseif($action == 'user_groups_list'){
	$mode = optional_param('mode', 1, PARAM_INT);

	require_login();

	if($mode){
		$items = $DB->get_records_sql("SELECT id, name FROM {local_elisprogram_uset} ORDER BY name");
	}else{
		$items = $DB->get_records_sql("SELECT id, name FROM {cohort} ORDER BY name");
	}

	$html = '<option value=""></option>';
	foreach($items as $item){
		$html .= '<option value="'.$item->id.'">'.$item->name.'</option>';
	}
	die($html);
}else{
	local_intelliboard_insert_tracking(true);
}
