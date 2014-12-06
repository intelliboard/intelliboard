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
 * IntelliBoard.net
 *
 *
 * @package    local_intelliboard
 * @copyright  2014 SEBALE LLC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");

set_time_limit(0);

class local_intelliboard_external extends external_api {

	var $users = 0;
	
	var $courses = 0;
	
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function database_query_parameters() {
		return new external_function_parameters(
            array('params' => new external_multiple_structure(
					new external_single_structure(
                        array(
                            'timestart' => new external_value(PARAM_RAW, 'timestart'),
                            'timefinish' => new external_value(PARAM_RAW, 'Moodle timefinish'),
                            'function' => new external_value(PARAM_RAW, 'Moodle DB function'),
                            'start' => new external_value(PARAM_INT, 'Moodle param', VALUE_OPTIONAL),
                            'length' => new external_value(PARAM_INT, 'Moodle param', VALUE_OPTIONAL),
                            'order_column' => new external_value(PARAM_INT, 'Moodle param', VALUE_OPTIONAL),
                            'order_dir' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'filter' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'Moodle param', VALUE_OPTIONAL),
                        )
                    )
				)
            )
        );
    }

    public static function database_query($params) {
        global $USER, $CFG, $DB;

        $params = self::validate_parameters(self::database_query_parameters(),
                array('params' => $params));

		$transaction = $DB->start_delegated_transaction();
		$obj = new local_intelliboard_external();

		if(count($params['params']) > 1){
			$data = array();
			foreach($params['params'] as $value){
				$value = (object)$value;
				$value->timestart = (isset($value->timestart)) ? $value->timestart : 0;
				$value->timefinish = (isset($value->timefinish)) ? $value->timefinish : 0;
				$function = (isset($value->function)) ? $value->function : false;
				if($function){
					$result = $obj->{$function}($value);
					$data[$function] = json_encode($result);
				}
			}
		}else{
			$params = (object)reset($params['params']);
			$params->timestart = (isset($params->timestart)) ? $params->timestart : 0;
			$params->timefinish = (isset($params->timefinish)) ? $params->timefinish : 0;
			$function = (isset($params->function)) ? $params->function : false;
			if($function){
				$data = $obj->{$function}($params);
			}
		}

		$transaction->allow_commit();
		
		return json_encode($data);
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function database_query_returns() {
        return new external_value(PARAM_RAW, 'Moodle DB records');
    }

	
	function get_limit_sql($params)
	{
		return (isset($params->start) and $params->length != 0 and $params->length != -1) ? "LIMIT $params->start, $params->length" : "";
	}
	function get_order_sql($params, $columns)
	{
		return (isset($params->order_column) and isset($columns[$params->order_column]) and $params->order_dir) ? "ORDER BY ".$columns[$params->order_column]." $params->order_dir" : "";
	}
	function get_filter_sql($filter, $columns)
	{
		if($filter and !empty($columns)){
			$sql_arr = array();
			foreach($columns as $column){
				$sql_arr[] = "$column LIKE '%$filter%'";
			}
			return "AND (" . implode(" OR ", $sql_arr) . ")";
		}else{
			return "";
		}
	}
	function get_userinfo($params){
		global $USER, $CFG, $DB;
		
		return $DB->get_record_sql("SELECT id, firstname, lastname, email, city, country
			FROM {$CFG->prefix}user WHERE id = ".$params->filter);
	}
	function get_tutors($params){
		global $USER, $CFG, $DB;
		
		return $DB->get_records_sql("SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) as name, u.email 
			FROM {$CFG->prefix}user u
				LEFT JOIN {$CFG->prefix}role_assignments a ON a.userid = u.id  
				WHERE (a.roleid = 3 OR a.roleid = 4) AND u.deleted = 0 AND u.confirmed = 1 GROUP BY u.id");
	}
	function report1($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS ue.id, ccc.cohorts, c.enablecompletion, ue.timecreated as enrolled, gc.avarage, cc.timecompleted as complete, u.id as uid, CONCAT(u.firstname, ' ', u.lastname) as name, u.email, c.id as cid, c.fullname as course, c.timemodified as start_date 
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (SELECT userid, GROUP_CONCAT( CAST( cc.cohortid AS CHAR )) AS cohorts FROM {$CFG->prefix}cohort_members cc GROUP BY cc.userid) ccc ON ccc.userid = u.id
								WHERE u.id > 0 $sql AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, e.courseid");
	}
	
	function report2($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.enablecompletion, c.timecreated as created, c.fullname as course_name, e.leaners, cc.aleaners, gc.agrade, cm.modules
						FROM {$CFG->prefix}course as c
							LEFT JOIN (SELECT course, count( id ) AS modules FROM {$CFG->prefix}course_modules WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS agrade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id
							LEFT JOIN (SELECT e.courseid, count( ue.enrolid ) AS leaners FROM {$CFG->prefix}user_enrolments ue,{$CFG->prefix}enrol e WHERE ue.enrolid = e.id AND ue.timemodified BETWEEN $params->timestart AND $params->timefinish  GROUP BY e.courseid) e ON e.courseid = c.id
							LEFT JOIN (SELECT course, count(id) as aleaners FROM {$CFG->prefix}course_completions WHERE timecompleted BETWEEN $params->timestart AND $params->timefinish  GROUP BY course) as cc ON cc.course = c.id	
								WHERE c.visible=1 $sql AND c.category > 0");
	}
	function report3($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		$report3 = $DB->get_records_sql("SELECT gg.id, ccc.cohorts, cmc.completionstate, iq.name as iqname, isc.name as isname, gg.timemodified as completion_date, cm.id as cmid, cm.instance, cm.completion, c.id as cid, c.fullname, u.id AS uid, CONCAT(u.firstname, ' ', u.lastname) as learner, u.email, m.name AS module_name, gg.finalgrade, gg.timecreated as start_time
						FROM {$CFG->prefix}course_modules cm
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.iteminstance = cm.instance
							LEFT JOIN {$CFG->prefix}grade_grades gg ON gg.itemid = gi.id
							LEFT JOIN {$CFG->prefix}user as u ON u.id = gg.userid
							LEFT JOIN {$CFG->prefix}modules m ON m.id = cm.module
							LEFT JOIN {$CFG->prefix}course as c ON c.id=cm.course
							LEFT JOIN {$CFG->prefix}quiz as iq ON iq.id = cm.instance
							LEFT JOIN {$CFG->prefix}scorm as isc ON isc.id = cm.instance
							LEFT JOIN {$CFG->prefix}course_modules_completion as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
							LEFT JOIN (SELECT userid, GROUP_CONCAT( CAST( cc.cohortid AS CHAR )) AS cohorts FROM {$CFG->prefix}cohort_members cc GROUP BY cc.userid) ccc ON ccc.userid = u.id
								WHERE (m.name = 'quiz' OR m.name = 'scorm') $sql AND gg.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY gg.id");

		if(!empty($report3)){
			foreach($report3 as &$wrow){
				if($wrow->module_name == 'quiz'){
					$wrow->iname = $wrow->iqname;
				}elseif($wrow->module_name == 'scorm'){
					$wrow->iname = $wrow->isname;
				}else{
					$wrow->iname = 'Undefined';
				}
			}
		}
		return $report3;
	}
	function report4($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");
		
		return $DB->get_records_sql("SELECT u.id, ccc.cohorts, com.compl, cmcn.not_activities, u.firstaccess as registered, ue.courses, gc.avarage, cm.completed_courses, cmc.completed_activities,  CONCAT(u.firstname, ' ', u.lastname) as learner, u.email 
						FROM {$CFG->prefix}user as u
							LEFT JOIN (SELECT ue.userid, count(distinct(e.courseid)) AS courses FROM {$CFG->prefix}user_enrolments AS ue, {$CFG->prefix}enrol AS e WHERE e.id = enrolid GROUP BY ue.userid) as ue ON ue.userid = u.id
							LEFT JOIN (SELECT ue.userid, count(distinct(e.courseid)) AS compl FROM {$CFG->prefix}user_enrolments AS ue, {$CFG->prefix}enrol AS e, {$CFG->prefix}course c WHERE e.id = enrolid AND e.courseid = c.id AND c.enablecompletion = 1 GROUP BY ue.userid) as com ON com.userid = u.id
							LEFT JOIN (SELECT userid, count(id) as completed_courses FROM {$CFG->prefix}course_completions WHERE timecompleted > 0 GROUP BY userid) as cm ON cm.userid = u.id
							LEFT JOIN (SELECT cmc.userid, count(cmc.id) as completed_activities FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.completion > 0 GROUP BY cmc.userid) as cmc ON cmc.userid = u.id
							LEFT JOIN (SELECT cmc.userid, count(cmc.id) as not_activities FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.completion = 0 GROUP BY cmc.userid) as cmcn ON cmcn.userid = u.id
							LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
							LEFT JOIN (SELECT userid, GROUP_CONCAT( CAST( cc.cohortid AS CHAR )) AS cohorts FROM {$CFG->prefix}cohort_members cc GROUP BY cc.userid) ccc ON ccc.userid = u.id							
							WHERE u.id > 0 $sql AND u.firstaccess BETWEEN $params->timestart AND $params->timefinish");
	}
	function report5($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) as teacher, a.roleid, ue.courses, ff.videos, l1.urls, l0.evideos, l2.assignments, l3.quizes, l4.forums, l5.attendances
			FROM {$CFG->prefix}user u
				LEFT JOIN {$CFG->prefix}role_assignments a ON a.userid = u.id
				LEFT JOIN (SELECT ue.userid, count(e.courseid) as courses FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e, {$CFG->prefix}context cxt WHERE e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4) GROUP BY ue.userid) as ue ON ue.userid = u.id
				LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {$CFG->prefix}files f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {$CFG->prefix}log l WHERE l.module = 'url' AND l.action = 'add' GROUP BY l.userid) as l1 ON l1.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {$CFG->prefix}log l WHERE l.module = 'page' AND l.action = 'add' GROUP BY l.userid) as l0 ON l0.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {$CFG->prefix}log l WHERE l.module = 'assignment' AND l.action = 'add' GROUP BY l.userid) as l2 ON l2.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {$CFG->prefix}log l WHERE l.module = 'quiz' AND l.action = 'add' GROUP BY l.userid) as l3 ON l3.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {$CFG->prefix}log l WHERE l.module = 'forum' AND l.action = 'add' GROUP BY l.userid) as l4 ON l4.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {$CFG->prefix}log l WHERE l.module = 'attendance' AND l.action = 'add' GROUP BY l.userid) as l5 ON l5.userid = u.id
				WHERE (a.roleid = 3 OR a.roleid = 4) GROUP BY u.id");
	}
	function report6($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT ue.id, ccc.gradepass, cmc.cmcnums, l.views, c.enablecompletion, ue.timecreated as enrolled, gc.avarage, cc.timecompleted as complete, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, c.id as cid, c.fullname as course, c.timemodified as start_date 
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid
							LEFT JOIN {$CFG->prefix}course_completion_criteria as ccc ON ccc.course = e.courseid AND ccc.criteriatype = 6
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (SELECT l.userid, l.course, count(id) as views FROM {$CFG->prefix}log l WHERE l.course > 1 and l.time BETWEEN $params->timestart AND $params->timefinish GROUP BY l.course, l.userid) as l ON l.course = c.id AND l.userid = u.id
								WHERE u.id > 0 $sql AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, e.courseid");
	}
	function report7($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT ue.id, c.enablecompletion, ((cmca.cmcnuma / cma.cmnuma)*100 ) as assigments, ((cmc.cmcnums / cmx.cmnumx)*100 ) as completed, ((lcm.viewed / cm.cmnums)*100 ) as visited, ue.timecreated as enrolled, gc.avarage, cc.timecompleted as complete, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, c.id as cid, c.fullname as course, c.timemodified as start_date 
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnums FROM {$CFG->prefix}course_modules cv WHERE cv.visible  =  1 GROUP BY cv.course) as cm ON cm.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnumx FROM {$CFG->prefix}course_modules cv WHERE cv.completion  =  1 GROUP BY cv.course) as cmx ON cmx.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnuma FROM {$CFG->prefix}course_modules cv WHERE cv.module  =  1 GROUP BY cv.course) as cma ON cma.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
							LEFT JOIN (SELECT l.userid, l.course, count(DISTINCT(l.cmid)) as viewed FROM {$CFG->prefix}log l WHERE l.cmid > 0 GROUP BY l.course, l.userid) as lcm ON lcm.course = c.id AND lcm.userid = u.id
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
								WHERE u.id > 0 $sql AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, e.courseid");
	}
	function report8($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("
		SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) name, a.roleid, ue.courses, ue.leaners, ui.activeleanres, ux.compleatedleanres, uz.grade
			FROM {$CFG->prefix}role_assignments a, {$CFG->prefix}user u
				LEFT JOIN (
					SELECT ue.userid, count(e.courseid) as courses, SUM(sx.users) as leaners
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
							LEFT JOIN (
								SELECT e.courseid, count(ue.userid) as users
									FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 5)
											GROUP BY e.courseid)  sx
							ON sx.courseid = e.courseid
						WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4)  
							GROUP BY ue.userid) as ue
					ON ue.userid = u.id
				LEFT JOIN (
					SELECT ue.userid, SUM(sx.users) as activeleanres 
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
							LEFT JOIN (
								SELECT e.courseid, count(ue.userid) as users 
									FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}user u, {$CFG->prefix}enrol e
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND u.id = ra.userid AND u.lastaccess > ".strtotime('-30 days')." AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 5)
											GROUP BY e.courseid)  sx 
							ON sx.courseid = e.courseid
						WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4)  
							GROUP BY ue.userid) as ui 
					ON ui.userid = u.id
				LEFT JOIN (
					SELECT ue.userid, SUM(sx.users) as compleatedleanres 
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
							LEFT JOIN (
								SELECT e.courseid, count(ue.userid) as users 
									FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}course_completions cc, {$CFG->prefix}enrol e
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND cc.userid = ra.userid AND cc.course = e.courseid AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 5)
											GROUP BY e.courseid)  sx 
							ON sx.courseid = e.courseid
						WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4)  
							GROUP BY ue.userid) as ux
					ON ux.userid = u.id
				LEFT JOIN (
					SELECT ue.userid, AVG( sx.avarage ) AS grade 
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
							LEFT JOIN (
								SELECT e.courseid, AVG( (gg.finalgrade/gg.rawgrademax)*100 ) AS avarage 
									FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}grade_grades gg, {$CFG->prefix}enrol e, {$CFG->prefix}grade_items gi
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND gg.userid = ue.userid AND gi.iteminstance = e.courseid AND gi.itemname != '' AND gg.itemid = gi.id AND gg.finalgrade IS NOT NULL AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 5)
											GROUP BY e.courseid)  sx 
							ON sx.courseid = e.courseid
						WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4)  
							GROUP BY ue.userid) as uz 
					ON uz.userid = u.id
			WHERE a.userid = u.id AND (a.roleid = 3 OR a.roleid = 4)
				GROUP BY u.id");
	}
	function report9($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "q.course", "courses");
		
		return $DB->get_records_sql("SELECT q.id, q.name, q.questions, q.timemodified, q.timeopen, q.timeclose, q.course, qa.attempts, qs.duration, qg.avgrade
			FROM {$CFG->prefix}quiz q
				LEFT JOIN (SELECT qa.quiz, count(qa.id) attempts FROM {$CFG->prefix}quiz_attempts qa GROUP BY qa.quiz) qa ON qa.quiz = q.id
				LEFT JOIN (SELECT qa.quiz, sum(qa.timefinish - qa.timestart) duration FROM {$CFG->prefix}quiz_attempts qa  GROUP BY qa.quiz) qs ON qs.quiz = q.id
				LEFT JOIN (SELECT qg.quiz, avg( (qg.grade/q.grade)*100 ) avgrade FROM  {$CFG->prefix}quiz q, {$CFG->prefix}quiz_grades qg WHERE q.id = qg.quiz GROUP BY qg.quiz) qg ON qg.quiz = q.id
			WHERE q.id > 0 $sql and q.timemodified BETWEEN $params->timestart AND $params->timefinish");
	}
	function report10($params)
	{
		global $USER, $CFG, $DB;
			
		if($CFG->version < 2012120301){
			return $DB->get_records_sql("SELECT qa.*, q.name, q.course, CONCAT(u.firstname, ' ', u.lastname) username, u.email, (qa.sumgrades/q.sumgrades*100) as grade
				FROM {$CFG->prefix}quiz_attempts qa
					LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
					LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
				WHERE qa.id > 0 and qa.timestart BETWEEN $params->timestart AND $params->timefinish");
		}else{
			return $DB->get_records_sql("SELECT qa.id, q.name, q.course, qa.timestart, qa.timefinish, qa.state, CONCAT(u.firstname, ' ', u.lastname) username, u.email, (qa.sumgrades/q.sumgrades*100) as grade
				FROM {$CFG->prefix}quiz_attempts qa
					LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
					LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
				WHERE qa.id > 0 and qa.timestart BETWEEN $params->timestart AND $params->timefinish");
		}
	}
	function report11($params)
	{
		global $USER, $CFG, $DB;
			
		return $DB->get_records_sql("SELECT ue.id, c.enablecompletion, ue.timecreated as enrolled, gc.avarage, cc.timecompleted as complete, u.id as uid, CONCAT(u.firstname, ' ', u.lastname) username, u.email, c.id as cid, c.fullname as course, c.timemodified as start_date 
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid AND cc.userid = u.id
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
								WHERE u.id > 0 AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, e.courseid");
	}

	function report12($params)
	{
		global $USER, $CFG, $DB;
			
		return $DB->get_records_sql("SELECT c.id, c.fullname, e.leaners, gc.grade, v.visits
						FROM {$CFG->prefix}course as c
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id
							LEFT JOIN (SELECT e.courseid, count( ue.enrolid ) AS leaners FROM {$CFG->prefix}user_enrolments ue,{$CFG->prefix}enrol e WHERE ue.enrolid = e.id AND ue.timemodified BETWEEN $params->timestart AND $params->timefinish  GROUP BY e.courseid) e ON e.courseid = c.id
							LEFT JOIN (SELECT l.course, count(l.id) as visits FROM {$CFG->prefix}log l WHERE l.time BETWEEN $params->timestart AND $params->timefinish GROUP BY l.course) v ON v.course = c.id	
								WHERE c.visible=1 AND c.category > 0");
	}
	
	function report13($params)
	{
		global $USER, $CFG, $DB;
			
		return $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) name, a.roleid, ue.courses, ue.leaners, ui.visits
			FROM {$CFG->prefix}role_assignments a, {$CFG->prefix}user u
				LEFT JOIN (
					SELECT ue.userid, count(e.courseid) as courses, SUM(sx.users) as leaners
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
							LEFT JOIN (
								SELECT e.courseid, count(ue.userid) as users
									FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user_enrolments ue, {$CFG->prefix}context cxt, {$CFG->prefix}enrol e
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 5)
											GROUP BY e.courseid)  sx
							ON sx.courseid = e.courseid
						WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND e.id = ue.enrolid AND cxt.instanceid = e.courseid AND ra.contextid = cxt.id AND ra.userid = ue.userid AND (ra.roleid = 3 OR ra.roleid = 4)  
							GROUP BY ue.userid) as ue
					ON ue.userid = u.id
				LEFT JOIN (
					SELECT l.userid, count(l.id) as visits
						FROM {$CFG->prefix}log l
							WHERE l.time BETWEEN $params->timestart AND $params->timefinish
								GROUP BY l.userid) as ui 
					ON ui.userid = u.id
			WHERE a.userid = u.id AND (a.roleid = 3 OR a.roleid = 4)
				GROUP BY u.id");
	}
	
	
	function report14($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");
		
		return $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) name, ue.courses, gc.avarage, ccc.visits
						FROM {$CFG->prefix}user as u
							LEFT JOIN (SELECT ue.userid, count(distinct(e.courseid)) AS courses FROM {$CFG->prefix}user_enrolments AS ue, {$CFG->prefix}enrol AS e WHERE e.id = enrolid AND ue.timemodified BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid) as ue ON ue.userid = u.id
							LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS avarage FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.timemodified BETWEEN $params->timestart AND $params->timefinish GROUP BY g.userid) as gc ON gc.userid = u.id
							LEFT JOIN (SELECT l.userid, count(l.id) as visits FROM {$CFG->prefix}log l WHERE l.time BETWEEN $params->timestart AND $params->timefinish GROUP BY l.userid) ccc ON ccc.userid = u.id							
							WHERE u.id > 0 $sql");
	}
	function report15($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "e.courseid", "courses");
		
		return $DB->get_records_sql("SELECT e.id, e.enrol, count(e.courseid) as courses, ue.users 
										FROM {$CFG->prefix}enrol e 
											LEFT JOIN (SELECT e.enrol, count(ue.userid) as users FROM {$CFG->prefix}enrol e,{$CFG->prefix}user_enrolments ue 
										WHERE ue.enrolid = e.id $sql GROUP BY e.enrol) ue ON ue.enrol = e.enrol WHERE e.id > 0 GROUP BY e.enrol");
	}
	
	function report16($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, v.visits, d.discussions, p.posts, COUNT(*) AS total, (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid = 3 AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher
											FROM {$CFG->prefix}course c
												LEFT JOIN {$CFG->prefix}forum f ON f.course = c.id
												LEFT JOIN (SELECT course, count(*) visits FROM {$CFG->prefix}log where module='forum' group by course) v ON v.course = c.id
												LEFT JOIN (SELECT course, count(*) discussions FROM {$CFG->prefix}forum_discussions group by course) d ON d.course = c.id
												LEFT JOIN (SELECT fd.course, count(*) posts FROM {$CFG->prefix}forum_discussions fd, {$CFG->prefix}forum_posts fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
												WHERE c.visible = 1 $sql GROUP BY f.course ORDER BY c.fullname ASC");
	}
	function report17($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT f.id as forum, c.id, c.fullname,f.name, f.type 
						,(SELECT COUNT(id) FROM {$CFG->prefix}forum_discussions AS fd WHERE f.id = fd.forum) AS Discussions
						,(SELECT COUNT(DISTINCT fd.userid) FROM {$CFG->prefix}forum_discussions AS fd WHERE fd.forum = f.id) AS UniqueUsersDiscussions
						,(SELECT COUNT(fp.id) FROM {$CFG->prefix}forum_discussions fd JOIN {$CFG->prefix}forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS Posts
						,(SELECT COUNT(DISTINCT fp.userid) FROM {$CFG->prefix}forum_discussions fd JOIN {$CFG->prefix}forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS UniqueUsersPosts
						,(SELECT COUNT( ra.userid ) AS Students
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid =5
						AND ctx.instanceid = c.id
						) AS StudentsCount
						,(SELECT COUNT( ra.userid ) AS Teachers
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid =3
						AND ctx.instanceid = c.id
						) AS teacherscount
						,(SELECT COUNT( ra.userid ) AS Users
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid IN (3,5)
						AND ctx.instanceid = c.id
						) AS UserCount
						, (SELECT (UniqueUsersDiscussions / StudentsCount )) AS StudentDissUsage
						, (SELECT (UniqueUsersPosts /StudentsCount)) AS StudentPostUsage
						FROM {$CFG->prefix}forum AS f 
						JOIN {$CFG->prefix}course AS c ON f.course = c.id
						WHERE c.id > 0 $sql
						ORDER BY StudentPostUsage DESC");
	}
	
	
	function report18($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "fd.course", "courses");
		
		return $DB->get_records_sql("SELECT u.id+f.id, fd.course, CONCAT(u.firstname,' ',u.lastname) as user, f.name, count(fp.id) as posts, fpl.created FROM
					{$CFG->prefix}forum_discussions fd
					LEFT JOIN {$CFG->prefix}forum f ON f.id = fd.forum
					LEFT JOIN {$CFG->prefix}forum_posts fp ON fp.discussion = fd.id
					LEFT JOIN {$CFG->prefix}user u ON u.id = fp.userid
					LEFT JOIN {$CFG->prefix}forum_posts as fpl ON fpl.id = 
							(
							   SELECT MAX(fdx.id) 
							   FROM {$CFG->prefix}forum_posts fpx, {$CFG->prefix}forum_discussions fdx
							   WHERE fpx.discussion = fdx.id AND fdx.forum = fd.forum AND fpx.userid = fpl.userid
							)
					WHERE f.id > 0 AND fpl.created BETWEEN $params->timestart AND $params->timefinish $sql
					GROUP BY u.id, f.id");
	}
	
	function report19($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, count(s.id) as scorms, (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid = 3 AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{$CFG->prefix}course c
										LEFT JOIN {$CFG->prefix}scorm s ON s.course = c.id
										WHERE c.visible = 1 $sql GROUP BY c.id ORDER BY c.fullname asc");
	}
	function report20($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "s.course", "courses");
		
		return $DB->get_records_sql("SELECT s.id, s.course, s.name, s.timemodified, count(sst.id) as attempts, sl.visits, sm.duration
										FROM {$CFG->prefix}scorm s 
										LEFT JOIN {$CFG->prefix}scorm_scoes_track sst ON sst.scormid = s.id AND sst.element = 'x.start.time' 
										LEFT JOIN (SELECT cm.instance, count(l.id) as visits 
											FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm, {$CFG->prefix}log l 
											WHERE m.name='scorm' and cm.module = m.id and l.cmid=cm.id AND l.action LIKE '%view%' 
											GROUP BY cm.instance) sl ON sl.instance = s.id
										LEFT JOIN (SELECT scormid, SEC_TO_TIME(SUM(TIME_TO_SEC(value))) AS duration FROM {$CFG->prefix}scorm_scoes_track where element = 'cmi.core.total_time' GROUP BY scormid) AS sm ON sm.scormid =s.id
										WHERE s.id > 0 AND s.timemodified BETWEEN $params->timestart AND $params->timefinish $sql
										GROUP BY s.id ");
	}
	function report21($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "sc.course", "courses");
		
		return $DB->get_records_sql("SELECT u.id+st.scormid+st.timemodified as id, CONCAT(u.firstname,' ',u.lastname) as user, st.userid, st.scormid, sc.name, sc.course, count(DISTINCT(st.attempt)) as attempts,sm.duration, sv.visits, round(sg.score, 0) as score
					FROM {$CFG->prefix}scorm_scoes_track AS st 
					LEFT JOIN {$CFG->prefix}user AS u ON st.userid=u.id 
					LEFT JOIN {$CFG->prefix}scorm AS sc ON sc.id=st.scormid 
					LEFT JOIN (SELECT userid, scormid, SEC_TO_TIME( SUM( TIME_TO_SEC( value ) ) ) AS duration FROM {$CFG->prefix}scorm_scoes_track where element = 'cmi.core.total_time' GROUP BY userid, scormid) AS sm ON sm.scormid =st.scormid and sm.userid=st.userid 
					LEFT JOIN (SELECT l.userid, cm.instance, count(l.id) as visits FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm, {$CFG->prefix}log l WHERE m.name='scorm' and cm.module = m.id and l.cmid=cm.id AND l.action LIKE '%view%' GROUP BY cm.instance, l.userid) AS sv ON sv.instance =st.scormid and sv.userid=st.userid
					LEFT JOIN (SELECT gi.iteminstance, AVG( (gg.finalgrade/gg.rawgrademax)*100) AS score, gg.userid FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades gg WHERE gi.itemmodule='scorm' and gg.itemid=gi.id ) AS sg ON sg.iteminstance =st.scormid and sg.userid=st.userid 
					WHERE sc.id > 0 $sql
					GROUP BY st.userid, st.scormid");
	}
	function report22($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, count(q.id) as quizzes, qa.attempts, qg.grade, qv.visits, (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid = 3 AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM 
						{$CFG->prefix}course c
						LEFT JOIN {$CFG->prefix}quiz q ON q.course = c.id
						LEFT JOIN (SELECT quiz, count(id) as attempts FROM {$CFG->prefix}quiz_attempts GROUP BY quiz) qa ON qa.quiz = q.id
						LEFT JOIN (SELECT gi.iteminstance, gg.finalgrade as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades gg WHERE gi.itemmodule='quiz' and gg.itemid=gi.id) qg ON qg.iteminstance = q.id
						LEFT JOIN (SELECT cm.instance, cm.module, count(l.id) as visits FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm, {$CFG->prefix}log l WHERE m.name='quiz' and cm.module = m.id and l.cmid=cm.id AND l.action LIKE '%view%' GROUP BY cm.instance) qv ON qv.instance = q.id 
						GROUP BY c.id");
	}
	function report23($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, count(r.id) as resources, (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid = 3 AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{$CFG->prefix}course c
										LEFT JOIN {$CFG->prefix}resource r ON r.course = c.id
										WHERE c.visible = 1 $sql GROUP BY c.id ORDER BY c.fullname asc");
	}
	function report24($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "r.course", "courses");
		
		return $DB->get_records_sql("SELECT r.id, r.course, r.name, r.timemodified, sl.visits
										FROM {$CFG->prefix}resource r 
										LEFT JOIN (SELECT cm.instance, count(l.id) as visits 
											FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm, {$CFG->prefix}log l 
											WHERE m.name='resource' and cm.module = m.id and l.cmid=cm.id AND l.action LIKE '%view%' 
											GROUP BY cm.instance) sl ON sl.instance = r.id
										WHERE r.id > 0 AND r.timemodified BETWEEN $params->timestart AND $params->timefinish $sql
										GROUP BY r.id ");
	}
	function report25($params)
	{
		global $USER, $CFG, $DB;
		return $DB->get_records_sql("SELECT id, component, count(id) as files, sum(filesize) as filesize FROM {$CFG->prefix}files group by component");
	}
	
	
	
	function get_scormattempts($params)
	{
		global $USER, $CFG, $DB;
			
		return $DB->get_records_sql("SELECT sst.attempt, 
				(SELECT s.value as starttime FROM {$CFG->prefix}scorm_scoes_track s WHERE element = 'x.start.time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as starttime,
				(SELECT s.value as starttime FROM {$CFG->prefix}scorm_scoes_track s WHERE element = 'cmi.core.score.raw' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as score, 
				(SELECT s.value as starttime FROM {$CFG->prefix}scorm_scoes_track s WHERE element = 'cmi.core.lesson_status' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as status 
			FROM {$CFG->prefix}scorm_scoes_track sst 
			WHERE sst.userid = " . intval($params->userid) . "  and sst.scormid = " . intval($params->filter) . "  
			GROUP BY attempt");
	}
	function get_questions($params)
	{
		global $USER, $CFG, $DB;
			
		return $DB->get_records_sql("SELECT qa.id, ROUND(((qa.maxmark * qas.fraction) * q.grade / q.sumgrades),2) as grade, qa.slot, qu.id as attempt, q.name as quiz, que.name as question, que.questiontext, qas.userid, qas.state, qas.timecreated, FORMAT(((LENGTH(q.questions) - LENGTH(REPLACE(q.questions, ',', '')) + 1)/2), 0) as questions
		FROM 
					{$CFG->prefix}question_attempts qa,
					{$CFG->prefix}question_attempt_steps qas,
					{$CFG->prefix}question_usages qu,
					{$CFG->prefix}question que,
					{$CFG->prefix}quiz q,
                    {$CFG->prefix}quiz_attempts qat,
					{$CFG->prefix}context cx,
					{$CFG->prefix}course_modules cm
					WHERE qat.id = " . intval($params->filter) . " 
							AND q.id = qat.quiz 
							AND cm.instance = q.id 
							AND cx.instanceid = cm.id 
							AND qu.contextid = cx.id 
							AND qa.questionusageid = qu.id
							AND qas.questionattemptid = qa.id 
							AND que.id = qa.questionid 
							AND qas.state != 'todo' 
							AND qas.state != 'complete' 
							AND qas.userid = qat.userid
						ORDER BY qas.timecreated DESC
					");
	}

	
	function get_daily_report($params)
	{
		global $USER, $CFG, $DB;

		$params->timestart = strtotime('-1 day');
		$params->timefinish = time();

		$sql1 = $this->get_teacher_sql($params, "id", "courses");
		$sql2 = $this->get_teacher_sql($params, "id", "users");
		$sql3 = $this->get_teacher_sql($params, "userid", "users");
		
		return $DB->get_record_sql("SELECT 
			(SELECT count(*) FROM {$CFG->prefix}user WHERE timemodified BETWEEN $params->timestart AND $params->timefinish $sql2) as registered,
			(SELECT count(*) FROM {$CFG->prefix}course WHERE timemodified BETWEEN $params->timestart AND $params->timefinish $sql1) as courses,
			(SELECT count(*) FROM {$CFG->prefix}log WHERE time BETWEEN $params->timestart AND $params->timefinish $sql3) as logs,
			(SELECT count(*) FROM {$CFG->prefix}log WHERE action = 'view' AND module = 'course' AND time BETWEEN $params->timestart AND $params->timefinish $sql3) as hours,
			(SELECT count(*) FROM {$CFG->prefix}course_completions WHERE timecompleted BETWEEN $params->timestart AND $params->timefinish $sql3) as completed,
			(SELECT count(*) FROM {$CFG->prefix}user WHERE suspended = 1 AND timemodified BETWEEN $params->timestart AND $params->timefinish $sql2) as suspended");
	}
	function get_activity_users($params)
	{
		global $USER, $CFG, $DB;

		$params->timestart = strtotime('-30 days');
		$params->timefinish = time();
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT ue.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, ue.timecreated, cx.id as context, c.id as cid, c.fullname
					FROM {$CFG->prefix}user_enrolments ue
						LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
						LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
						LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
						LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
							WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql GROUP BY ue.id ORDER BY ue.timecreated DESC LIMIT 10");
	}	
	function get_activity_registrants($params)
	{
		global $USER, $CFG, $DB;

		$params->timestart = strtotime('-30 days');
		$params->timefinish = time();
		
		$sql = $this->get_teacher_sql($params, "u.id", "users");
		
		return $DB->get_records_sql("SELECT u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.timecreated, cx.id as context
					FROM {$CFG->prefix}user u
						LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
							WHERE u.timecreated BETWEEN $params->timestart AND $params->timefinish $sql ORDER BY u.timecreated DESC LIMIT 10");
	}	
	function get_total_info($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");
		$sql2 = $this->get_teacher_sql($params, "id", "users");
		$sql3 = $this->get_teacher_sql($params, "id", "courses");
		
		return $DB->get_record_sql("SELECT 
			(SELECT count(*) FROM {$CFG->prefix}user WHERE id > 0 $sql2) as users,
			(SELECT count(*) FROM {$CFG->prefix}course WHERE id > 0 $sql3) as courses,
			(SELECT count(*) FROM {$CFG->prefix}user WHERE lastaccess > 0 $sql2) as active,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE id > 0 $sql) as space,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE component='user' $sql) as userspace,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE filearea='content' $sql) as coursespace");
	}	
	function get_system_users($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_record_sql("SELECT 
			(SELECT count(*) FROM {$CFG->prefix}user u WHERE u.deleted = 0 $sql) as users,
			(SELECT count(*) FROM {$CFG->prefix}user u WHERE u.lastaccess > 0 $sql) as active,
			(SELECT count(*) FROM {$CFG->prefix}user u WHERE u.confirmed = 0 OR u.deleted = 1 $sql) as deactive,
			(SELECT count(*) FROM {$CFG->prefix}user u WHERE u.lastlogin > 0 $sql) as returned,
			(SELECT count(*) FROM {$CFG->prefix}user u WHERE u.suspended = 1 $sql) as suspended,
			(SELECT count(DISTINCT (c.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}course_completions c WHERE u.id = c.id $sql) as graduated,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e WHERE ee.id = e.enrolid AND e.userid=u.id $sql) as enrolled,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e WHERE ee.enrol = 'cohort' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_cohort,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e WHERE ee.enrol = 'manual' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_manual,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e WHERE ee.enrol = 'self' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_self");
	}
	function get_system_courses($params)
	{
		global $USER, $CFG, $DB;

		$sql1 = $this->get_teacher_sql($params, "course", "courses");
		$sql2 = $this->get_teacher_sql($params, "id", "courses");
		$sql3 = $this->get_teacher_sql($params, "cm.course", "courses");
		$sql4 = $this->get_teacher_sql($params, "userid", "users");
		
		return $DB->get_record_sql("SELECT
			(SELECT count(*) FROM {$CFG->prefix}course_completions WHERE timecompleted > 0 $sql1) as graduates,
			(SELECT count(*) FROM {$CFG->prefix}course_modules WHERE visible = 1 $sql1) as modules,
			(SELECT count(*) FROM {$CFG->prefix}course WHERE visible = 1 AND category > 0 $sql2) as visible,
			(SELECT count(*) FROM {$CFG->prefix}course WHERE visible = 0 AND category > 0 $sql2) as hidden,
			(SELECT count(DISTINCT (userid)) FROM {$CFG->prefix}user_enrolments WHERE status = 1 $sql4) as expired,
			(SELECT count(DISTINCT (userid)) FROM {$CFG->prefix}role_assignments WHERE roleid = 5 $sql4 GROUP BY roleid) as students,
			(SELECT count(DISTINCT (userid)) FROM {$CFG->prefix}role_assignments WHERE roleid = 3 $sql4 GROUP BY roleid) as tutors,
			(SELECT count(*) FROM {$CFG->prefix}course_modules_completion WHERE completionstate > 0 $sql4) as completed,
			(SELECT COUNT(DISTINCT (userid)) FROM {$CFG->prefix}log WHERE cmid > 0 $sql1) as reviewed,
			(SELECT count(cm.id) FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE m.name = 'certificate' AND cm.module = m.id $sql3) as certificates");
	}

	function get_system_load($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "userid", "users");
		
		return $DB->get_record_sql("SELECT 
			(SELECT count(*) FROM {$CFG->prefix}log WHERE id > 0 $sql) as site,
			(SELECT count(*) FROM {$CFG->prefix}log WHERE module = 'course' $sql) as course,
            (SELECT count(*) FROM {$CFG->prefix}log WHERE cmid > 0 $sql) as activity");
	}

	function get_module_visits($params)
	{
		global $USER, $CFG, $DB;
		 
		return $DB->get_records_sql("SELECT m.id, l.module, (count(l.id) / (select count(*) FROM {$CFG->prefix}log WHERE  userid > 2 AND time BETWEEN $params->timestart AND $params->timefinish)) * 100  as visits FROM {$CFG->prefix}log l, {$CFG->prefix}modules m WHERE m.name = l.module and userid > 2 AND time BETWEEN $params->timestart AND $params->timefinish GROUP by module");
	}
	
	function get_users_count($params)
	{
		global $USER, $CFG, $DB;
		
		return $DB->get_record_sql("SELECT 
			(SELECT count(*) FROM {$CFG->prefix}user WHERE timecreated BETWEEN $params->timestart AND $params->timefinish) as registered,
			(SELECT count(*) FROM {$CFG->prefix}user WHERE lastaccess BETWEEN $params->timestart AND $params->timefinish) as active,
			(SELECT count(*) FROM {$CFG->prefix}user WHERE lastlogin BETWEEN $params->timestart AND $params->timefinish) as returned,
			(SELECT count(u.id) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e WHERE ee.id = e.enrolid AND e.userid=u.id AND e.timemodified BETWEEN $params->timestart AND $params->timefinish) as enrolled");
	}
	

	function get_most_visited_courses($params)
	{
		 global $USER, $CFG, $DB;
		 
		$sql = $this->get_teacher_sql($params, "l.course", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, count(l.id) as nums , gc.grade
				FROM {$CFG->prefix}log l 
				LEFT JOIN {$CFG->prefix}course c ON c.id = l.course 
				LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemname != '' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id
					WHERE l.course > 1 $sql AND l.time BETWEEN $params->timestart AND $params->timefinish 
						GROUP BY l.course 
							ORDER BY nums DESC 
								LIMIT 10");
	}
	function get_no_visited_courses($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, l.nums, c.timecreated FROM {$CFG->prefix}course c LEFT JOIN (SELECT l.course, count( l.course ) AS nums FROM {$CFG->prefix}log l WHERE l.course >0 $sql AND l.time BETWEEN $params->timestart AND $params->timefinish GROUP BY l.course)l ON l.course = c.id WHERE l.nums < 3 LIMIT 10");
	}
	function get_active_users($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "u.id", "users");
		
		return $DB->get_records_sql("
					SELECT u.id, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.lastaccess, u.email, ue.courses, l.visits
						FROM {$CFG->prefix}user u
							LEFT JOIN (SELECT userid, COUNT(id) as courses FROM {$CFG->prefix}user_enrolments WHERE id > 0 GROUP BY userid) ue ON ue.userid = u.id
							LEFT JOIN (SELECT userid, COUNT(id) as visits FROM  {$CFG->prefix}log l WHERE id > 0 GROUP BY userid) l ON l.userid = u.id
								WHERE u.lastaccess BETWEEN $params->timestart AND $params->timefinish AND l.visits > 0 $sql
									ORDER BY l.visits DESC
										LIMIT 10");
	}

	function get_enrollments_per_course($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {$CFG->prefix}course c, {$CFG->prefix}enrol e, {$CFG->prefix}user_enrolments ue WHERE e.courseid = c.id AND ue.enrolid =e.id $sql AND ue.timemodified BETWEEN $params->timestart AND $params->timefinish GROUP BY c.id");
	}	
	function get_size_courses($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.timecreated, c.fullname, fs.coursesize, l.visits
				FROM {$CFG->prefix}course c
					LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize FROM {$CFG->prefix}files f, {$CFG->prefix}context c WHERE c.id = f.contextid AND f.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY c.instanceid) fs ON fs.course = c.id
					LEFT JOIN (SELECT course, count(id) AS visits FROM {$CFG->prefix}log WHERE time BETWEEN $params->timestart AND $params->timefinish GROUP BY course) l ON l.course = c.id
						WHERE c.category > 0 $sql LIMIT 20");
		
	}
	function get_active_ip_users($params, $limit = 10)
	{
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT l.id, l.ip, u.lastaccess as time, count(l.id) as visits, CONCAT( u.firstname, ' ', u.lastname ) AS name
					FROM {$CFG->prefix}log l,  {$CFG->prefix}user u
						WHERE u.id = l.userid AND l.time BETWEEN $params->timestart AND $params->timefinish 
							GROUP BY l.ip 
								ORDER BY visits  DESC 
									LIMIT 10");
	}

	function get_active_courses_per_day($params)
	{
		global $USER, $CFG, $DB;
		
		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 3){
			$ext = 3600; //by hour
		}elseif($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		
		$sql = $this->get_teacher_sql($params, "course", "courses");
		
		$data = $DB->get_records_sql("(SELECT floor(time / $ext) * $ext as time, COUNT(DISTINCT (course)) as courses
				FROM {$CFG->prefix}log
					WHERE module='course' and action='view' $sql AND time BETWEEN $params->timestart AND $params->timefinish 
						GROUP BY floor(time / $ext) * $ext
							ORDER BY time DESC)");	
						
						
		$response = array();
		foreach($data as $item){
			$response[] = $item->time.'.'.$item->courses;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_unique_sessions($params)
	{
		global $USER, $CFG, $DB;
		
		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 3){
			$ext = 3600; //by hour
		}elseif($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		
		$data = $DB->get_records_sql("(SELECT floor(lastaccess / $ext) * $ext as time, COUNT(id) as users
				FROM {$CFG->prefix}user
					WHERE lastaccess BETWEEN $params->timestart AND $params->timefinish 
						GROUP BY floor(lastaccess / $ext) * $ext
							ORDER BY lastaccess DESC)");	
						
						
		$response = array();
		foreach($data as $item){
			$response[] = $item->time.'.'.$item->users;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_new_courses_per_day($params)
	{
		global $USER, $CFG, $DB;
		
		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 3){
			$ext = 3600; //by hour
		}elseif($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		
		$data = $DB->get_records_sql("(SELECT floor(timecreated / $ext) * $ext as time, COUNT(id) as courses
				FROM {$CFG->prefix}course
					WHERE category > 0 AND  timecreated BETWEEN $params->timestart AND $params->timefinish 
						GROUP BY floor(timecreated / $ext) * $ext
							ORDER BY timecreated DESC)");			
						
						
		$response = array();
		foreach($data as $item){
			$response[] = $item->time.'.'.$item->courses;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;	
		
	}
	function get_users_per_day($params)
	{
		global $USER, $CFG, $DB;
		
		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 3){
			$ext = 3600; //by hour
		}elseif($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		$sql = $this->get_teacher_sql($params, "id", "users");
		
		$data = $DB->get_records_sql("(SELECT floor(timecreated / $ext) * $ext as time, COUNT(id) as users
				FROM {$CFG->prefix}user
					WHERE timecreated BETWEEN $params->timestart AND $params->timefinish $sql 
						GROUP BY floor(timecreated / $ext) * $ext
							ORDER BY timecreated DESC)");
						
		$response = array();
		$response[] = ($params->timefinish+86400).'.0';
		foreach($data as $item){
			$response[] = $item->time.'.'.$item->users;
		}
		$response[] = ($params->timestart-86400).'.0';
		
		
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_active_users_per_day($params)
	{
		global $USER, $CFG, $DB;
				
		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 3){
			$ext = 3600; //by hour
		}elseif($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		$sql = $this->get_teacher_sql($params, "userid", "users");
		
		$data = $DB->get_records_sql("
			(SELECT floor(time / $ext) * $ext as time, COUNT(DISTINCT (userid)) as users
				FROM {$CFG->prefix}log
					WHERE userid>0 AND time BETWEEN $params->timestart AND $params->timefinish $sql
						GROUP BY floor(time / $ext) * $ext
							ORDER BY time DESC)");
		$response = array();
		$response[] = ($params->timefinish+86400).'.0';
		foreach($data as $item){
			$response[] = $item->time.'.'.$item->users;
		}
		$response[] = ($params->timestart-86400).'.0';
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}

	function get_get_users_time($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "l.userid", "users");
		
		return $DB->get_record_sql("SELECT
				(SELECT count(*) as logs FROM {$CFG->prefix}log l WHERE l.time BETWEEN $params->timestart AND $params->timefinish $sql) as site,
				(SELECT count(*) as logs FROM {$CFG->prefix}log l WHERE l.time $sql AND l.module = 'course' AND l.time BETWEEN $params->timestart AND $params->timefinish) as course,
				(SELECT count(*) as logs FROM {$CFG->prefix}log l WHERE l.time $sql AND l.cmid > 0 AND l.time BETWEEN $params->timestart AND $params->timefinish) as activity");
	}
	function db_search_users($params)
	{
		global $USER, $CFG, $DB;
		
		$sql = $this->get_teacher_sql($params, "id", "users");
		
		$sql_u = "firstname LIKE '%$params->filter%' OR lastname LIKE '%$params->filter%' OR email LIKE '%$params->filter%'";
		$array = explode(" ", $params->filter);
		if(is_array($array)){
			foreach ($array as $s){
				$sql_u .= " OR firstname LIKE '%$s%' OR lastname LIKE '%$s%'";
			}
		}
		
		return $DB->get_records_sql("SELECT id, firstname, lastname, email, lastaccess FROM {user} WHERE id > 1 AND ($sql_u) $sql LIMIT 0, 10");
	}
	function db_search_courses($params)
	{
		global $USER, $CFG, $DB;
		
		$sql_c = "fullname LIKE '%$params->filter%' OR summary LIKE '%$params->filter%'";
		$array = explode(" ", $params->filter);
		if(is_array($array)){
			foreach ($array as $s){
				$sql_c .= " OR fullname LIKE '%$s%' OR summary LIKE '%$s%'";
			}
		}
		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		
		return $DB->get_records_sql("SELECT c.id, c.fullname, c.timemodified, l.size
							FROM {course} c
								LEFT JOIN (SELECT l.course, count( l.course ) AS size
									FROM {log} l WHERE l.course >0 GROUP BY l.course)l ON l.course = c.id WHERE $sql_c $sql LIMIT 0, 10");
	}
	function get_markers($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");
		
		return $DB->get_records_sql("SELECT l.userid, l.ip, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email
				FROM {$CFG->prefix}user u
					LEFT JOIN {$CFG->prefix}log l ON l.userid = u.id AND l.ip != '0:0:0:0:0:0:0:1'
						WHERE u.confirmed = 1 AND u.deleted = 0 AND l.ip != '' $sql
							GROUP BY u.id
								ORDER BY u.id DESC LIMIT 50");
	}
	function get_countries($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "id", "users");
		
		return $DB->get_records_sql("SELECT country, count(*) as users
				FROM {$CFG->prefix}user u
					WHERE country != '' $sql GROUP BY country");
	}
	function get_cohorts($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT id, name FROM {$CFG->prefix}cohort ORDER BY name");
	}
	function get_info($params){
		global $USER, $CFG, $DB;
		
		require_once($CFG->libdir.'/adminlib.php');
		
		return array('version' => get_component_version('local_intelliboard'));
	}
	function get_courses($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "id", "courses");
		
		$sql_filter = ($params->filter) ? " AND fullname LIKE '%$params->filter%'" : "";
		$sql_limit = ($params->length or $params->start) ? "  LIMIT $params->start, $params->length" : "";
		
		return $DB->get_records_sql("SELECT id, fullname FROM {$CFG->prefix}course WHERE category > 0 $sql $sql_filter ORDER BY fullname $sql_limit");
	}
	
	
	function get_teacher_sql($params, $column, $type)
	{
		$sql = '';
		if($params->userid){
			if($type == "users"){
				$courses = $this->get_teacher_courses($params, true);
				$users = $this->get_teacher_leaners($params, true, $courses);
				$sql = "AND $column IN($users)";
			}elseif($type == "courses"){
				$courses = $this->get_teacher_courses($params, true);
				$sql = "AND $column IN($courses)";
			}
		}
		return $sql;
	}
	function get_teacher_leaners($params, $format = false, $courses)
	{
		global $USER, $CFG, $DB;

		if($this->users){
			$users = $this->users;
		}else{
			$users = $this->users = $DB->get_records_sql("SELECT u.id FROM {$CFG->prefix}user AS u
							JOIN {$CFG->prefix}role_assignments AS ra ON u.id = ra.userid
							JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
							JOIN {$CFG->prefix}course AS c ON c.id = ctx.instanceid
								WHERE ra.roleid =5 AND ctx.instanceid = c.id AND c.visible=1 AND c.id IN($courses)
									GROUP BY u.id");
		}
		if($format){
			$ids = array();
			foreach($users as $users){
				$ids[] = $users->id;
			}
			return ($ids) ? implode(",", $ids) : 0;
		}else{
			return $users;
		}
	}
	function get_teacher_courses($params, $format = false)
	{
		global $USER, $CFG, $DB;
		
		if($this->courses){
			$courses = $this->courses;
		}else{		
			$courses = $this->courses = $DB->get_records_sql("SELECT c.id, c.fullname FROM {$CFG->prefix}course AS c, {$CFG->prefix}role_assignments AS ra
				JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
				WHERE c.visible=1 and ctx.instanceid = c.id AND ra.userid = $params->userid AND (ra.roleid = 3 OR ra.roleid = 4)");
		}
		if($format){	
			$ids = array();
			foreach($courses as $course){
				$ids[] = $course->id;
			}
			return ($ids) ? implode(",", $ids) : 0;
		}else{
			return $courses;
		}
		
	}
}
