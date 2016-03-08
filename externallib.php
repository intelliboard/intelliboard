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

require_once($CFG->libdir . "/externallib.php");

set_time_limit(0);

class local_intelliboard_external extends external_api {

	var $users = 0;
	var $courses = 0;
	var $learner_roles = 5;
	var $teacher_roles = 3;

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function database_query_parameters() {
		return new external_function_parameters(
            array('params' => new external_multiple_structure(
					new external_single_structure(
                        array(
                            'timestart' => new external_value(PARAM_RAW, 'Time start param'),
                            'timefinish' => new external_value(PARAM_RAW, 'Time finish param'),
                            'function' => new external_value(PARAM_RAW, 'Function name'),
                            'start' => new external_value(PARAM_INT, 'Pagination start', VALUE_OPTIONAL),
                            'length' => new external_value(PARAM_INT, 'Pagination length', VALUE_OPTIONAL),
                            'order_column' => new external_value(PARAM_INT, 'Order column param', VALUE_OPTIONAL),
                            'order_dir' => new external_value(PARAM_RAW, 'Order direction param', VALUE_OPTIONAL),
                            'filter' => new external_value(PARAM_RAW, 'Filter var', VALUE_OPTIONAL),
                            'custom' => new external_value(PARAM_RAW, 'Custom var', VALUE_OPTIONAL),
                            'columns' => new external_value(PARAM_RAW, 'Profile columns', VALUE_OPTIONAL),
                            'notification_enrol' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'notification_auth' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'notification_email' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'notification_subject' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'notification_message' => new external_value(PARAM_RAW, 'Moodle param', VALUE_OPTIONAL),
                            'teacher_roles' => new external_value(PARAM_RAW, 'Teacher roles', VALUE_OPTIONAL),
                            'learner_roles' => new external_value(PARAM_RAW, 'Learner roles', VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
                            'courseid' => new external_value(PARAM_RAW, 'Course ID', VALUE_OPTIONAL),
                            'cohortid' => new external_value(PARAM_RAW, 'Cohort ID', VALUE_OPTIONAL)
                        )
                    )
				)
            )
        );
    }

    public static function database_query($params) {
        global $USER, $CFG, $DB;

        $params = self::validate_parameters(self::database_query_parameters(), array('params' => $params));

				$transaction = $DB->start_delegated_transaction();
				$obj = new local_intelliboard_external();

				if(count($params['params']) > 1){
					$data = array();
					foreach($params['params'] as $value){
						$value = (object)$value;
						$value->userid = isset($value->userid) ? $value->userid : 0;
						$value->courseid = isset($value->courseid) ? $value->courseid : 0;
						$value->cohortid = isset($value->cohortid) ? $value->cohortid : 0;
						$value->start = isset($value->start) ? $value->start : 0;
						$value->length = isset($value->length) ? $value->length : 50;
						$value->filter = isset($value->filter) ? $value->filter : '';
						$value->custom = isset($value->custom) ? $value->custom : '';
						$value->timestart = (isset($value->timestart)) ? $value->timestart : 0;
						$value->timefinish = (isset($value->timefinish)) ? $value->timefinish : 0;

						$obj->teacher_roles = (isset($value->teacher_roles)) ? $value->teacher_roles : 3;
						$obj->learner_roles = (isset($value->learner_roles)) ? $value->learner_roles : 5;

						$function = (isset($value->function)) ? $value->function : false;
						if($function){
							$result = $obj->{$function}($value);
							$data[$function] = json_encode($result);
						}
					}
				}else{
					$params = (object)reset($params['params']);
					$params->userid = isset($params->userid) ? $params->userid : 0;
					$params->courseid = isset($params->courseid) ? $params->courseid : 0;
					$params->cohortid = isset($params->cohortid) ? $params->cohortid : 0;
					$params->start = isset($params->start) ? $params->start : 0;
					$params->length = isset($params->length) ? $params->length : 50;
					$params->filter = isset($params->filter) ? $params->filter : '';
					$params->custom = isset($params->custom) ? $params->custom : '';
					$params->timestart = (isset($params->timestart)) ? $params->timestart : 0;
					$params->timefinish = (isset($params->timefinish)) ? $params->timefinish : 0;

					$obj->teacher_roles = (isset($params->teacher_roles) and $params->teacher_roles) ? $params->teacher_roles : 3;
					$obj->learner_roles = (isset($params->learner_roles) and $params->learner_roles) ? $params->learner_roles : 5;

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
			return " HAVING " . implode(" OR ", $sql_arr);
		}
		return "";
	}
	function get_filter_columns($params)
	{
		if(!empty($params->columns)){
			$data = array();
			$columns = explode(",", $params->columns);
			foreach($columns as $column){
				$data[] = "field$column";
			}
			return $data;
		}else{
			return array();
		}
	}
	function get_columns($params, $field = "u.id")
	{
		if(!empty($params->columns)){
			$data = "";
			$columns = explode(",", $params->columns);
			foreach($columns as $column){
				$data .= ", (SELECT d.data FROM {user_info_data} d, {user_info_field} f WHERE f.id = $column AND d.fieldid = f.id AND d.userid = $field) AS field$column";
			}
			return $data;
		}else{
			return "";
		}
	}
	function getQuizAttemptsSql($type = "attempts")
	{
		global $CFG;

		if($type == "grade"){
			$sql = "avg((qa.sumgrades/q.sumgrades)*100) as $type";
		}elseif($type == "duration"){
			$sql = "sum(qa.timefinish - qa.timestart) $type";
		}else{
			$sql = "count(distinct(qa.id)) $type";
		}

		return "SELECT qa.quiz, $sql
						FROM
							{$CFG->prefix}quiz q,
							{$CFG->prefix}quiz_attempts qa,
							(".$this->getUsersEnrolsSql().") ue
						WHERE
							qa.quiz = q.id AND
							q.course = ue.courseid AND
							qa.userid = ue.userid AND
							qa.timefinish > 0 AND
							qa.timestart > 0
						GROUP BY qa.quiz";
	}
	function getModGradeSql($grage = 'grade')
	{
		global $CFG;

		return "SELECT gi.iteminstance, gi.itemmodule, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS $grage
								FROM
									{$CFG->prefix}grade_items gi,
									{$CFG->prefix}grade_grades g
								WHERE
									gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
								GROUP BY gi.iteminstance, gi.itemmodule";
	}
	function getCourseUserGradeSql($grage = 'grade', $round = 0){

		global $CFG;

		return "SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
				FROM
					{$CFG->prefix}grade_items gi,
					{$CFG->prefix}grade_grades g
				WHERE
					gi.itemtype = 'course' AND
					g.itemid = gi.id
				GROUP BY gi.courseid, g.userid";
	}
	function getCourseGradeSql($grage = 'grade', $round = 0)
	{
		global $CFG;

		return "SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
					FROM
						{$CFG->prefix}grade_items gi,
						{$CFG->prefix}grade_grades g
					WHERE
						gi.itemtype = 'course' AND
						g.itemid = gi.id
					GROUP BY gi.courseid";
	}
	function getLearnerCoursesSql($courses  = 'courses')
	{
		global $CFG;

		return "SELECT ue.userid, COUNT(DISTINCT(ue.courseid)) AS $courses
					FROM
						(".$this->getUsersEnrolsSql().") ue
					GROUP BY ue.userid";
	}

	function getCourseLearnersSql($learners  = 'learners', $timestart = 0, $timefinish = 0)
	{
		global $CFG;

		$sql = ($timestart and $timefinish) ? "ue.timecreated BETWEEN $timestart AND $timefinish" : "1";

		return "SELECT ue.courseid, COUNT(DISTINCT(ue.userid)) AS $learners
					FROM
						(".$this->getUsersEnrolsSql().") ue
					WHERE $sql GROUP BY ue.courseid";
	}
	function getModCompletedSql($completed  = 'completed')
	{
		global $CFG;

		return "SELECT cm.id, count(DISTINCT(cmc.userid)) AS $completed
					FROM
						{$CFG->prefix}course_modules cm,
						{$CFG->prefix}course_modules_completion cmc,
						(".$this->getUsersEnrolsSql().") ue
					WHERE
						cm.course = ue.courseid AND
						cmc.completionstate = 1 AND
						cmc.coursemoduleid = cm.id AND
						cmc.userid = ue.userid
					GROUP BY cmc.coursemoduleid";
	}
	function getCourseCompletedSql($completed  = 'completed')
	{
		global $CFG;

		return "SELECT c.course, count(DISTINCT(c.userid)) AS $completed
					FROM
						{$CFG->prefix}course_completions c,
						(".$this->getUsersEnrolsSql().") ue
					WHERE
						c.timecompleted > 0 AND
						c.course = ue.courseid AND
						c.userid = ue.userid
					GROUP BY c.course";
	}
	function getCourseTimeSql($timespend  = 'timespend', $visits  = 'visits', $filter = '')
	{
		global $CFG;

		return "SELECT lit.courseid, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
			FROM
				{$CFG->prefix}local_intelliboard_tracking lit,
				(".$this->getUsersEnrolsSql().") l
			WHERE $filter
				lit.courseid = l.courseid AND
				lit.userid = l.userid
			GROUP BY lit.courseid";
	}

	function getModTimeSql($timespend  = 'timespend', $visits  = 'visits')
	{
		global $CFG;

		return "SELECT lit.param, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
			FROM
				{$CFG->prefix}local_intelliboard_tracking lit,
				(".$this->getUsersEnrolsSql().") l
			WHERE
				lit.page = 'module' AND
				lit.courseid = l.courseid AND
				lit.userid = l.userid
			GROUP BY lit.param";
	}
	function getCurseUserTimeSql($timespend  = 'timespend', $visits  = 'visits')
	{
		global $CFG;

		return "SELECT lit.userid, lit.courseid, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
					FROM
						{$CFG->prefix}local_intelliboard_tracking lit
					GROUP BY lit.courseid, lit.userid";
	}

	function getUsersEnrolsSql($roles = array(), $enrols = array())
	{
		global $CFG;

		if(empty($roles)){
			$roles = explode(",", $this->learner_roles);
		}

		$sql_filter = "";
		if($roles and $roles[0] != 0){
			$sql_roles = array();
			foreach($roles as $role){
				$sql_roles[] = "ra.roleid = $role";
			}
			$sql_filter .= " AND (".implode(" OR ", $sql_roles).")";
		}
		if($enrols){
			$sql_enrols = array();
			foreach($enrols as $enrol){
				$sql_enrols[] = "e.enrol = '$enrol'";
			}
			$sql_filter .= " AND (".implode(" OR ", $sql_enrols).")";
		}

		return "SELECT ue.id, ra.roleid, e.courseid, ue.userid, ue.timecreated, GROUP_CONCAT( DISTINCT e.enrol) AS enrols
					FROM
						{$CFG->prefix}user_enrolments ue,
						{$CFG->prefix}enrol e,
						{$CFG->prefix}role_assignments ra,
						{$CFG->prefix}context ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
	}

	function report1($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("name", "u.email", "c.fullname", "ue.enrols", "l.visits", "l.timespend", "gc.grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));

		$sql_filter = "";
		$sql_join = "";

		if(isset($params->filter) and  strrpos($params->filter, 'pfields') !== false){
			$d = explode('pfields', $params->filter);
			$params->filter = $d[0];
			if(!empty($d[1])){
				$f = explode(',', htmlspecialchars($d[1], ENT_QUOTES));
				$s = array();
				foreach($f as $l){
					$s[] = "'$l'";
				}
				$f = implode(',', $s);
				$sql_filter .= " AND u.id IN (SELECT distinct(userid) FROM {$CFG->prefix}user_info_data WHERE data IN ($f))";
			}
		}
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if(isset($params->custom) and  strrpos($params->custom, ',') !== false){
			$sql_filter .= " AND u.id IN($params->custom)";
			$filterColumn = 'ue.timecreated';
		}elseif(isset($params->custom) and $params->custom == 1){
			$filterColumn = 'cc.timecompleted';
		}else{
			$filterColumn = 'ue.timecreated';
		}
		if($params->cohortid){
			$sql_join = "LEFT JOIN {$CFG->prefix}cohort_members cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			u.id as uid, u.email,
			CONCAT(u.firstname, ' ', u.lastname) as name,
			ue.enrols,
			l.timespend,
			l.visits,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date
			$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
							$sql_join
								WHERE $filterColumn BETWEEN $params->timestart AND $params->timefinish $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report2($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("course", "e.learners", "cm.modules", "cc.completed", "lit.visits", "lit.timespend", "gc.grade", "c.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS c.id,
				c.fullname as course,
				c.timecreated as created,
				c.enablecompletion,
				e.learners,
				cc.completed,
				gc.grade,
				cm.modules,
				lit.timespend,
				lit.visits
				$sql_columns
					FROM {$CFG->prefix}course as c
						LEFT JOIN (SELECT course, count( id ) AS modules FROM {$CFG->prefix}course_modules WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
						LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id
						LEFT JOIN (".$this->getCourseLearnersSql().") e ON e.courseid = c.id
						LEFT JOIN (".$this->getCourseCompletedSql().") as cc ON cc.course = c.id
						LEFT JOIN (".$this->getCourseTimeSql().") as lit ON lit.courseid = c.id
							WHERE c.visible=1 AND c.category > 0 $sql_filter $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report3($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("m.name", "cmc.completed", "l.visits", "l.timespend", "gc.grade", "cm.added"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND cm.course  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_select = "";
		$sql_join = "";

		if($modules = $this->get_mudules()){
			foreach($modules as $module){
				$sql_join .= " LEFT JOIN {$CFG->prefix}{$module->name} as mod_{$module->name} ON mod_{$module->name}.id = cm.instance";
				$sql_select .= ", mod_{$module->name}.name as {$module->name}";
			}
		}
		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS cm.id,
				m.name AS module,
				cm.added,
				cm.completion,
				cmc.completed,
				gc.grade,
				l.timespend,
				l.visits
				$sql_select
					FROM {$CFG->prefix}course_modules cm
						LEFT JOIN {$CFG->prefix}modules m ON m.id = cm.module
						LEFT JOIN (".$this->getModCompletedSql().") cmc ON cmc.id = cm.id
						LEFT JOIN (".$this->getModTimeSql().") l ON l.param = cm.id
						LEFT JOIN (".$this->getModGradeSql().") as gc ON gc.itemmodule = m.name AND gc.iteminstance = cm.instance
						$sql_join
							WHERE cm.visible = 1 AND cm.added BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY cm.id $sql_having $sql_orger $sql_limit");
		if($modules){
			foreach($data as $item){
				foreach($modules as $module){
					if(isset($item->{$module->name}) and $item->module == $module->name){
						$item->module = $item->{$module->name};
						unset($item->{$module->name});
					}else{
						unset($item->{$module->name});
					}
				}
			}
		}
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report4($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("learner","u.email","registered","ue.courses","cmc.completed_activities","cm.completed_courses","lit.visits","lit.timespend","gc.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {$CFG->prefix}cohort_members chm ON chm.userid = u.id";
			$sql_filter .= " AND chm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email,
				u.timecreated as registered,
				ue.courses,
				gc.grade,
				cm.completed_courses,
				cmc.completed_activities,
				lit.timespend,
				lit.visits
				$sql_columns
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user as u
							$sql_join
							LEFT JOIN (".$this->getLearnerCoursesSql().") as ue ON ue.userid = u.id
							LEFT JOIN (SELECT cc.userid, count(cc.id) as completed_courses FROM {$CFG->prefix}course_completions cc, (".$this->getUsersEnrolsSql().") as lc WHERE cc.course = lc.courseid AND cc.userid = lc.userid AND cc.timecompleted > 0 GROUP BY cc.userid) as cm ON cm.userid = u.id
							LEFT JOIN (SELECT cmc.userid, count(cmc.id) as completed_activities FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc, (".$this->getUsersEnrolsSql().") as lc WHERE cm.course = lc.courseid AND cmc.userid = lc.userid AND cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY cmc.userid) as cmc ON cmc.userid = u.id
							LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g, (".$this->getUsersEnrolsSql().") as lc WHERE gi.courseid = lc.courseid AND g.userid = lc.userid AND gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
							LEFT JOIN (SELECT l.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking l, (".$this->getUsersEnrolsSql().") as lc WHERE l.courseid = lc.courseid AND l.userid = lc.userid GROUP BY l.userid) as lit ON lit.userid = u.id
							WHERE ra.roleid IN ($this->learner_roles) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 $sql_filter AND u.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY u.id $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}


	function report5($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("teacher","courses","ff.videos","l1.urls","l0.evideos","l2.assignments","l3.quizes","l4.forums","l5.attendances"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($CFG->version < 2014051200){
			$table = "log";
			$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) as teacher,
					count(ue.courseid) as courses,
					ff.videos,
					l1.urls,
					l0.evideos,
					l2.assignments,
					l3.quizes,
					l4.forums,
					l5.attendances
					$sql_columns
					FROM (".$this->getUsersEnrolsSql(explode(",", $this->teacher_roles)).") as ue
						LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {$CFG->prefix}files f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {$CFG->prefix}$table l WHERE l.module = 'url' AND l.action = 'add' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {$CFG->prefix}$table l WHERE l.module = 'page' AND l.action = 'add' GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {$CFG->prefix}$table l WHERE l.module = 'assignment' AND l.action = 'add' GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {$CFG->prefix}$table l WHERE l.module = 'quiz' AND l.action = 'add' GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {$CFG->prefix}$table l WHERE l.module = 'forum' AND l.action = 'add' GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {$CFG->prefix}$table l WHERE l.module = 'attendance' AND l.action = 'add' GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter GROUP BY ue.userid $sql_having $sql_orger $sql_limit");
		}else{
			$table = "logstore_standard_log";
					$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) as teacher,
					count(ue.courseid) as courses,
					f1.files,
					ff.videos,
					l1.urls,
					l0.evideos,
					l2.assignments,
					l3.quizes,
					l4.forums,
					l5.attendances
					$sql_columns
					FROM
						(".$this->getUsersEnrolsSql(explode(",", $this->teacher_roles)).") as ue
						LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) files FROM {$CFG->prefix}files f WHERE filearea = 'content' GROUP BY f.userid) as f1 ON f1.userid = u.id
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {$CFG->prefix}files f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'url' AND l.action = 'created' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'page' AND l.action = 'created'GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'assignment' AND l.action = 'created'GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'quiz' AND l.action = 'created'GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'forum' AND l.action = 'created'GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {$CFG->prefix}$table l,{$CFG->prefix}course_modules cm, {$CFG->prefix}modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'attendance' AND l.action = 'created'GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter GROUP BY ue.userid $sql_having $sql_orger $sql_limit");
		}


		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report6($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("student", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			cri.gradepass,
			ue.userid,
			ue.timecreated as started,
			c.id as cid,
			c.fullname,
			git.average,
			AVG((g.finalgrade/g.rawgrademax)*100) AS grade,
			cmc.completed,
			CONCAT(u.firstname, ' ', u.lastname) AS student,
			lit.timespend,
			lit.visits,
			c.enablecompletion,
			cc.timecompleted as complete
			$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {$CFG->prefix}course_completion_criteria as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = c.id AND lit.userid = u.id
							LEFT JOIN (".$this->getCourseGradeSql('average').") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {$CFG->prefix}course_modules_completion cmc, {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, ue.courseid $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report7($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("learner", "course", "visits", "participations", "assignments", "grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS ue.id, ue.userid,
					((cmca.cmcnuma / cma.cmnuma)*100 ) as assignments,
					((cmc.cmcnums / cmx.cmnumx)*100 ) as participations,
					((count(lit.id) / cm.cmnums)*100 ) as visits,
					cma.cmnuma as assigns,
					gc.grade,
					c.fullname as course,
					CONCAT( u.firstname, ' ', u.lastname ) AS learner
					$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}local_intelliboard_tracking lit ON lit.courseid = c.id AND lit.page = 'module' AND lit.userid = u.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnums FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 GROUP BY cv.course) as cm ON cm.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnumx FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 and cv.completion = 1 GROUP BY cv.course) as cmx ON cmx.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnuma FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 and cv.module = 1 GROUP BY cv.course) as cma ON cma.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, ue.courseid $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report8($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("teacher","courses","learners","activelearners","completedlearners","grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) teacher,
					count(ue.courseid) as courses,
					sum(l.learners) as learners,
					sum(ls.learners) as activelearners,
					sum(c.completed) as completedlearners,
					AVG( g.grade ) AS grade
					$sql_columns
				FROM
					(".$this->getUsersEnrolsSql(explode(",", $this->teacher_roles)).") as ue
					LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseLearnersSql().") l ON l.courseid = ue.courseid
					LEFT JOIN (".$this->getCourseLearnersSql('learners', strtotime('-30 days'), time()).") ls ON ls.courseid = ue.courseid
					LEFT JOIN (".$this->getCourseCompletedSql().") c ON c.course = ue.courseid
					LEFT JOIN (".$this->getCourseGradeSql().") g ON g.courseid = ue.courseid
				WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY u.id $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report9($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("q.name", "c.fullname", "q.questions", "q.timeopen", "qa.attempts", "qs.duration", "qg.grade", "q.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND q.course  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($CFG->version < 2014051200){
			$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS q.id,
				q.name,
				q.course,
				c.fullname,
				q.questions,
				q.timemodified,
				q.timeopen,
				q.timeclose,
				qa.attempts,
				qs.duration,
				qg.grade
			FROM {$CFG->prefix}quiz q
				LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
				LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
			WHERE q.course > 0 $sql_filter GROUP BY q.id $sql_having $sql_orger $sql_limit");
			foreach($data as &$item){
				$item->questions = count(array_diff(explode(',', $item->questions), array(0)));
			}
		}else{
			$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS q.id,
				q.name,
				q.course,
				c.fullname,
				ql.questions,
				q.timemodified,
				q.timeopen,
				q.timeclose,
				qa.attempts,
				qs.duration,
				qg.grade
			FROM {$CFG->prefix}quiz q
				LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
				LEFT JOIN (SELECT quizid, count(*) questions FROM {$CFG->prefix}quiz_slots GROUP BY quizid) ql ON ql.quizid = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
			WHERE q.course > 0 $sql_filter GROUP BY q.id $sql_having $sql_orger $sql_limit");
		}



		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report10($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("q.name","learner", "c.fullname", "qa.state", "qa.timestart", "qa.timefinish", "duration", "grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND q.course  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS qa.id,
				q.name,
				q.course,
				c.fullname,
				qa.timestart,
				qa.timefinish,
				qa.state,
				(qa.timefinish - qa.timestart) as duration,
				(qa.sumgrades/q.sumgrades*100) as grade,
				CONCAT(u.firstname, ' ', u.lastname) learner
				$sql_columns
				FROM {$CFG->prefix}quiz_attempts qa
					LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
					LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
					LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
					LEFT JOIN {$CFG->prefix}context ctx ON ctx.instanceid = c.id
					LEFT JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id AND ra.userid = u.id
				WHERE ra.roleid  IN ($this->learner_roles) $sql_filter and qa.timestart BETWEEN $params->timestart AND $params->timefinish $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report11($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("learner", "course", "u.email", "enrolled", "complete", "grade", "complete"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "ue.courseid", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS ue.id,
					ue.timecreated as enrolled,
					cc.timecompleted as complete,
					(g.finalgrade/g.rawgrademax)*100 AS grade,
					u.id as uid,
					u.email,
					CONCAT(u.firstname, ' ', u.lastname) learner,
					c.id as cid,
					c.enablecompletion,
					c.fullname as course
					$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = u.id
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = ue.courseid AND gi.itemtype = 'course'
							LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report12($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.fullname", "e.leaners", "v.visits", "v.timespend", "gc.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS c.id,
					c.fullname,
					e.learners,
					gc.grade,
					v.visits,
					v.timespend
						FROM {$CFG->prefix}course as c
							LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id
							LEFT JOIN (".$this->getCourseLearnersSql().") e ON e.courseid = c.id
							LEFT JOIN (".$this->getCourseTimeSql().") v ON v.courseid = c.id
								WHERE c.visible=1 AND c.category > 0 $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}


	function report13($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("name", "visits", "timespend", "courses", "learners"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) name,
					count(ue.courseid) as courses,
					sum(l.learners) as learners,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
					$sql_columns
				FROM
					(".$this->getUsersEnrolsSql(explode(",", $this->teacher_roles)).") as ue
					LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseLearnersSql().") l ON l.courseid = ue.courseid
					LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = ue.courseid AND lit.userid = u.id
				WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter GROUP BY u.id $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}


	function report14($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("name", "visits", "timespend", "courses", "grade", "grade", "u.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id, u.lastaccess,
					CONCAT(u.firstname, ' ', u.lastname) name,
					count(ue.courseid) as courses,
					avg(l.grade) as grade,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
					$sql_columns
				FROM
					(".$this->getUsersEnrolsSql().") as ue
					LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseUserGradeSql().") l ON l.courseid = ue.courseid AND l.userid = u.id
					LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = ue.courseid AND lit.userid = u.id
				WHERE u.deleted = 0 AND u.suspended = 0 AND u.lastaccess BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY u.id $sql_having $sql_orger $sql_limit");


			$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report15($params)
	{
		global $USER, $CFG, $DB;
		$columns = array_merge(array("enrol", "courses", "ue.users"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
						SQL_CALC_FOUND_ROWS e.id,
						e.enrol,
						count(e.courseid) as courses,
						ue.users
							FROM {$CFG->prefix}enrol e
								LEFT JOIN (SELECT e.enrol, count(ue.userid) as users FROM {$CFG->prefix}enrol e,{$CFG->prefix}user_enrolments ue
							WHERE ue.enrolid = e.id $sql_filter GROUP BY e.enrol) ue ON ue.enrol = e.enrol $sql_filter WHERE e.id > 0 GROUP BY e.enrol $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report16($params)
	{
		global $USER, $CFG, $DB;
		$columns = array_merge(array("c.fullname", "teacher", "total", "v.visits", "v.timespend", "p.posts", "d.discussions"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS c.id,
					c.fullname,
					v.visits,
					v.timespend,
					d.discussions,
					p.posts,
					COUNT(*) AS total,
					(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					  FROM {$CFG->prefix}role_assignments AS ra
					  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
					  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
					  WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher
						FROM {$CFG->prefix}course c
							LEFT JOIN {$CFG->prefix}forum f ON f.course = c.id
							LEFT JOIN (SELECT lit.courseid, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking lit, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='forum' GROUP BY lit.courseid) v ON v.courseid = c.id
							LEFT JOIN (SELECT course, count(*) discussions FROM {$CFG->prefix}forum_discussions group by course) d ON d.course = c.id
							LEFT JOIN (SELECT fd.course, count(*) posts FROM {$CFG->prefix}forum_discussions fd, {$CFG->prefix}forum_posts fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
							WHERE c.visible = 1 $sql_filter GROUP BY f.course $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report17($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.fullname", "f.name ", "f.type ", "Discussions", "UniqueUsersDiscussions", "Posts", "UniqueUsersPosts", "Students", "Teachers", "UserCount", "StudentDissUsage", "StudentPostUsage"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS f.id as forum, c.id, c.fullname,f.name, f.type
						,(SELECT COUNT(id) FROM {$CFG->prefix}forum_discussions AS fd WHERE f.id = fd.forum) AS Discussions
						,(SELECT COUNT(DISTINCT fd.userid) FROM {$CFG->prefix}forum_discussions AS fd WHERE fd.forum = f.id) AS UniqueUsersDiscussions
						,(SELECT COUNT(fp.id) FROM {$CFG->prefix}forum_discussions fd JOIN {$CFG->prefix}forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS Posts
						,(SELECT COUNT(DISTINCT fp.userid) FROM {$CFG->prefix}forum_discussions fd JOIN {$CFG->prefix}forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS UniqueUsersPosts
						,(SELECT COUNT( ra.userid ) AS Students
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid  IN ($this->learner_roles)
						AND ctx.instanceid = c.id
						) AS StudentsCount
						,(SELECT COUNT( ra.userid ) AS Teachers
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid IN ($this->teacher_roles)
						AND ctx.instanceid = c.id
						) AS teacherscount
						,(SELECT COUNT( ra.userid ) AS Users
						FROM {$CFG->prefix}role_assignments AS ra
						JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
						WHERE  ctx.instanceid = c.id
						) AS UserCount
						, (SELECT (UniqueUsersDiscussions / StudentsCount )) AS StudentDissUsage
						, (SELECT (UniqueUsersPosts /StudentsCount)) AS StudentPostUsage
						FROM {$CFG->prefix}forum AS f
						JOIN {$CFG->prefix}course AS c ON f.course = c.id
						WHERE c.id > 0 $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}


	function report18($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("f.name", "user","course", "fpl.created", "posts"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id+f.id,
					c.fullname as course,
					CONCAT(u.firstname,' ',u.lastname) as user,
					f.name,
					count(fp.id) as posts,
					fpl.created
					FROM
						{$CFG->prefix}forum_discussions fd
						LEFT JOIN {$CFG->prefix}course c ON c.id = fd.course
						LEFT JOIN {$CFG->prefix}forum f ON f.id = fd.forum
						LEFT JOIN {$CFG->prefix}forum_posts fp ON fp.discussion = fd.id
						LEFT JOIN {$CFG->prefix}user u ON u.id = fp.userid
						LEFT JOIN {$CFG->prefix}forum_posts as fpl ON fpl.id =
							(
							   SELECT MAX(fdx.id)
							   FROM {$CFG->prefix}forum_posts fpx, {$CFG->prefix}forum_discussions fdx
							   WHERE fpx.discussion = fdx.id AND fdx.forum = fd.forum AND fpx.userid = fpl.userid
							)
					WHERE f.id > 0 $sql_filter
					GROUP BY u.id, f.id  $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report19($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.fullname", "teacher", "scorms"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS c.id,
			c.fullname, count(s.id) as scorms,
			(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{$CFG->prefix}course c
										LEFT JOIN {$CFG->prefix}scorm s ON s.course = c.id
										WHERE c.visible = 1 AND c.category > 0 $sql_filter GROUP BY c.id $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report20($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("s.name", "c.fullname", "sl.visits", "sm.duration", "s.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS s.id,
					c.fullname,
					s.name,
					s.timemodified,
					count(sst.id) as attempts,
					sl.visits,
					sm.duration
						FROM {$CFG->prefix}scorm s
						LEFT JOIN {$CFG->prefix}scorm_scoes_track sst ON sst.scormid = s.id AND sst.element = 'x.start.time'
						LEFT JOIN {$CFG->prefix}course c ON c.id = s.course
						LEFT JOIN (SELECT cm.instance, sum(lit.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking lit, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='scorm' GROUP BY cm.instance) sl ON sl.instance = s.id
						LEFT JOIN (SELECT scormid, SEC_TO_TIME(SUM(TIME_TO_SEC(value))) AS duration FROM {$CFG->prefix}scorm_scoes_track where element = 'cmi.core.total_time' GROUP BY scormid) AS sm ON sm.scormid =s.id
						WHERE s.id > 0 AND s.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter
						GROUP BY s.id $sql_having $sql_orger $sql_limit");
			$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report21($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "sc.name", "c.fullname", "attempts", "sm.duration", "score"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS u.id+st.scormid+st.timemodified as id,
			CONCAT(u.firstname,' ',u.lastname) as user,
			st.userid,
			st.scormid,
			sc.name,
			c.fullname,
			count(DISTINCT(st.attempt)) as attempts,
			sm.duration,
			round(sg.score, 0) as score
			$sql_columns
					FROM {$CFG->prefix}scorm_scoes_track AS st
					LEFT JOIN {$CFG->prefix}user AS u ON st.userid=u.id
					LEFT JOIN {$CFG->prefix}scorm AS sc ON sc.id=st.scormid
					LEFT JOIN {$CFG->prefix}course c ON c.id = sc.course
					LEFT JOIN (SELECT userid, scormid, SEC_TO_TIME( SUM( TIME_TO_SEC( value ) ) ) AS duration FROM {$CFG->prefix}scorm_scoes_track where element = 'cmi.core.total_time' GROUP BY userid, scormid) AS sm ON sm.scormid =st.scormid and sm.userid=st.userid
					LEFT JOIN (SELECT gi.iteminstance, AVG( (gg.finalgrade/gg.rawgrademax)*100) AS score, gg.userid FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades gg WHERE gi.itemmodule='scorm' and gg.itemid=gi.id  GROUP BY gi.iteminstance, gg.userid) AS sg ON sg.iteminstance =st.scormid and sg.userid=st.userid
					WHERE sc.id > 0 $sql_filter
					GROUP BY st.userid, st.scormid $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report22($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.fullname", "teacher", "quizzes", "qa.attempts", "qv.visits", "qv.timespend", "qg.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS c.id,
				c.fullname,
				count(q.id) as quizzes,
				sum(qs.duration) as duration,
				sum(qa.attempts) as attempts,
				avg(qg.grade) as grade,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles)  AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
						{$CFG->prefix}quiz q
						LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
						LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
						LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
						LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
						WHERE  c.visible = 1 AND c.category > 0 $sql_filter
						GROUP BY c.id $sql_having $sql_orger $sql_limit");
			$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report23($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.fullname", "resources", "teacher"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS c.id,
			c.fullname,
			count(r.id) as resources,
			(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {$CFG->prefix}role_assignments AS ra
									  JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
									  JOIN {$CFG->prefix}context AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles)  AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{$CFG->prefix}course c
										LEFT JOIN {$CFG->prefix}resource r ON r.course = c.id
										WHERE c.visible = 1 $sql_filter GROUP BY c.id $sql_having $sql_orger $sql_limit");
			$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report24($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("r.name", "c.fullname", "sl.visits", "sl.timespend", "r.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS r.id,
				c.fullname,
				r.name,
				r.timemodified,
				sl.visits,
				sl.timespend FROM {$CFG->prefix}resource r
										LEFT JOIN {$CFG->prefix}course c ON c.id = r.course
										LEFT JOIN (SELECT cm.instance, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking lit, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='resource' GROUP BY cm.instance) sl ON sl.instance = r.id
										WHERE r.id > 0 AND r.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter
										GROUP BY r.id  $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report25($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("component", "files", "filesize"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS id,
				component,
				count(id) as files,
				sum(filesize) as filesize
				FROM {$CFG->prefix}files WHERE id > 0 $sql_filter GROUP BY component $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report26($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("course", "user", "enrolled", "cc.timecompleted", "score", "completed", "l.visits", "l.timespend"), $this->get_filter_columns($params));

		$sql_filter = ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS ue.id,
				u.id as uid,
				cmc.completed,
				cmm.modules,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				c.id as cid,
				c.fullname as course,
				ue.timecreated as enrolled,
				round(gc.score, 2) as score,
				l.timespend, l.visits,
				cc.timecompleted $sql_columns
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
                            LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid and cc.userid = ue.userid
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS score FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (SELECT lit.userid, lit.courseid, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) as l ON l.courseid = c.id AND l.userid = u.id
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {$CFG->prefix}course_modules cm WHERE cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
						WHERE ue.userid IN (SELECT com.userid as id FROM {$CFG->prefix}cohort_members com WHERE cohortid IN (SELECT com.cohortid as id FROM {$CFG->prefix}cohort_members com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." ) $sql_filter GROUP BY ue.userid, e.courseid  $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report27($params)
	{
		global $USER, $CFG, $DB;


		$sql_filter = ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($CFG->version < 2012120301){
			$columns = array_merge(array("course", "username", "email", "q.name", "qa.id", "qa.id", "qa.id", "qa.id", "grade"), $this->get_filter_columns($params));

			$sql_having = $this->get_filter_sql($params->filter, $columns);
			$sql_orger = $this->get_order_sql($params, $columns);

			$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS qa.id,
				qa.*,
				q.name,
				c.fullname as course,
				CONCAT(u.firstname, ' ', u.lastname) username,
				u.email,
				(qa.sumgrades/q.sumgrades*100) as grade
				$sql_columns
					FROM {$CFG->prefix}quiz_attempts qa
						LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
						LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
						LEFT JOIN {$CFG->prefix}course as c ON c.id = q.course
					WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {$CFG->prefix}cohort_members com WHERE cohortid IN (SELECT com.cohortid as id FROM {$CFG->prefix}cohort_members com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." )  and qa.timestart BETWEEN $params->timestart AND $params->timefinish $sql_filter
						GROUP BY qa.id $sql_having $sql_orger $sql_limit");
		}else{
			$columns = array_merge(array("course", "username", "email", "q.name", "qa.state", "qa.timestart", "qa.timefinish", "qa.timefinish", "grade"), $this->get_filter_columns($params));

			$sql_having = $this->get_filter_sql($params->filter, $columns);
			$sql_orger = $this->get_order_sql($params, $columns);

			$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS qa.id,
				q.name,
				c.fullname as course,
				qa.timestart,
				qa.timefinish,
				qa.state,
				CONCAT(u.firstname, ' ', u.lastname) username,
				u.email,
				(qa.sumgrades/q.sumgrades*100) as grade
				$sql_columns
					FROM {$CFG->prefix}quiz_attempts qa
						LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
						LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
						LEFT JOIN {$CFG->prefix}course as c ON c.id = q.course
					WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {$CFG->prefix}cohort_members com WHERE cohortid IN (SELECT com.cohortid as id FROM {$CFG->prefix}cohort_members com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." )   and qa.timestart BETWEEN $params->timestart AND $params->timefinish $sql_filter
							GROUP BY qa.id $sql_having $sql_orger $sql_limit");
		}
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report28($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("gi.itemname", "learner", "graduated", "grade", "completionstate", "timespend", "visits"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND cm.course  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS gg.id,
					gi.itemname,
					gg.userid,
					CONCAT(u.firstname, ' ', u.lastname) as learner,
					gg.timemodified as graduated,
					gg.finalgrade as grade,
					cm.completion,
					cmc.completionstate,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
					$sql_columns
						FROM {$CFG->prefix}grade_grades gg
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.id=gg.itemid
							LEFT JOIN {$CFG->prefix}user as u ON u.id = gg.userid
							LEFT JOIN {$CFG->prefix}modules m ON m.name = gi.itemmodule
							LEFT JOIN {$CFG->prefix}course_modules cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {$CFG->prefix}course as c ON c.id=cm.course
							LEFT JOIN {$CFG->prefix}course_modules_completion as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
							LEFT JOIN {$CFG->prefix}local_intelliboard_tracking lit ON lit.page = 'module' AND lit.param = cm.id AND lit.userid = u.id
								WHERE gi.itemtype = 'mod' $sql_filter AND gg.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY gg.id $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report29($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "course", "g.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);
			foreach($courses as $c){
				$data = explode("_", $c);
				$sql_courses[] = "(e.courseid = ".$data[1]." AND g.grade < ".intval($data[0]).")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "e.courseid > 0";
		}

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS ue.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				c.fullname as course,
				g.grade,
				gm.graded,
				cm.modules
						FROM {$CFG->prefix}user_enrolments as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
                            LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN (SELECT gi.courseid, gg.userid, (gg.finalgrade/gg.rawgrademax)*100 AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades gg WHERE gi.itemtype = 'course' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as g ON g.courseid = c.id AND g.userid = u.id
							LEFT JOIN (SELECT gi.courseid, gg.userid, count(gg.id) graded FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades gg WHERE gi.itemtype = 'mod' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as gm ON gm.courseid = c.id AND gm.userid = u.id
							LEFT JOIN (SELECT courseid, count(id) as modules FROM {$CFG->prefix}grade_items WHERE itemtype = 'mod' GROUP BY courseid) as cm ON cm.courseid = c.id
						WHERE (cc.timecompleted IS NULL OR cc.timecompleted = 0) AND gm.graded >= cm.modules AND $sql_courses $sql_filter GROUP BY ue.userid, e.courseid  $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report30($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "course", "enrolled", "cc.timecompleted"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);
			foreach($courses as $c){
				$data = explode("_", $c);
				$sql_courses[] = "(cc.course = ".$data[1]." AND cc.timecompleted > ".($data[0]/1000).")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "cc.course > 0";
		}

		$data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS cc.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, cc.timecompleted
					FROM
						{$CFG->prefix}course_completions cc,
						{$CFG->prefix}course c,
						{$CFG->prefix}user u
					WHERE u.id= cc.userid and c.id = cc.course and $sql_courses");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report31($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "course", "lit.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);
			foreach($courses as $c){
				$data = explode("_", $c);
				$sql_courses[] = "(lit.courseid = ".$data[1]." AND lit.lastaccess < ".(time()-($data[0]*86400)).")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "lit.courseid > 0";
		}

		$data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS u.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, lit.lastaccess
					FROM {$CFG->prefix}user u
						LEFT JOIN {$CFG->prefix}local_intelliboard_tracking lit on lit.userid = u.id AND lit.lastaccess = (
							SELECT MAX(lastaccess)
								FROM {$CFG->prefix}local_intelliboard_tracking
								WHERE userid = lit.userid and courseid = lit.courseid
							)
						LEFT JOIN {$CFG->prefix}course c ON c.id = lit.courseid
					WHERE $sql_courses GROUP BY lit.userid, lit.courseid");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report32($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "courses","lit1.timesite","lit2.timecourses","lit3.timeactivities","u.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.timecreated,
				count(DISTINCT (ue.courseid)) as courses,
				lit1.timesite,
				lit2.timecourses,
				lit3.timeactivities
				$sql_columns
						FROM (".$this->getUsersEnrolsSql().") ue
							LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
							LEFT JOIN (SELECT userid, sum(timespend) as timesite FROM {$CFG->prefix}local_intelliboard_tracking GROUP BY userid) as lit1 ON lit1.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timecourses FROM {$CFG->prefix}local_intelliboard_tracking WHERE courseid > 0 GROUP BY userid) as lit2 ON lit2.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timeactivities FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' GROUP BY userid) as lit3 ON lit3.userid = u.id
							WHERE u.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY u.id $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
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

	function report33($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user", "course", "ue.enrols", "l.visits", "l.timespend", "gc.grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));

		$sql_join = "";

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->cohortid){
			$sql_join = "LEFT JOIN {$CFG->prefix}cohort_members cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			u.id as uid,
			CONCAT(u.firstname, ' ', u.lastname) as user,
			ue.enrols,
			l.timespend,
			l.visits,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date,
			GROUP_CONCAT(DISTINCT  gr.name) AS groups
			$sql_columns
						FROM {$CFG->prefix}groups as gr, {$CFG->prefix}groups_members as grm, (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
							$sql_join
								WHERE gr.courseid = ue.courseid and grm.groupid = gr.id and grm.userid = ue.userid AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY ue.courseid, ue.userid $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report34($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("c.fullname", "ue.enrols", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			ue.enrols,
			l.timespend,
			l.visits,
			c.id as cid,
			ue.userid,
			c.fullname as course,
			c.timemodified as start_date,
			round(((cmc.completed/cmm.modules)*100), 0) as progress
						FROM (".$this->getUsersEnrolsSql(0).") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = ue.userid
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid=$params->userid GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
								WHERE ue.userid = $params->userid  $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

		function report35($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("gi.itemname", "graduated", "grade", "completionstate", "timespend", "visits");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = ($params->courseid) ? " AND gi.courseid  IN ($params->courseid) " : "";
		$sql_filter .= ($params->userid) ? " AND gg.userid = $params->userid " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS gg.id,
					gi.itemname,
					gi.courseid,
					cm.completionexpected,
					gg.userid,
					gg.timemodified as graduated,
					(gg.finalgrade/gg.rawgrademax)*100 as grade,
					cm.completion,
					cmc.completionstate,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
						FROM {$CFG->prefix}grade_grades gg
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.id=gg.itemid
							LEFT JOIN {$CFG->prefix}user as u ON u.id = gg.userid
							LEFT JOIN {$CFG->prefix}modules m ON m.name = gi.itemmodule
							LEFT JOIN {$CFG->prefix}course_modules cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {$CFG->prefix}course as c ON c.id=cm.course
							LEFT JOIN {$CFG->prefix}course_modules_completion as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
							LEFT JOIN {$CFG->prefix}local_intelliboard_tracking lit ON lit.page = 'module' AND lit.param = cm.id AND lit.userid = u.id
								WHERE itemtype = 'mod' $sql_filter GROUP BY gg.id $sql_having $sql_orger $sql_limit");

		if($data and intval($params->custom) == 1){
			require_once($CFG->libdir . "/gradelib.php");

			foreach($data as $course){
				$context = context_course::instance($course->courseid,IGNORE_MISSING);

				$letters = grade_get_letters($context);
				foreach($letters as $lowerboundary=>$value){
					if($course->grade >= $lowerboundary){
						$course->grade = $value;
						break;
					}
				}
			}
		}

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report36($params)
	{
		global $USER, $CFG, $DB;



		$columns = array("c.fullname", "l.page", "l.param", "l.visits", "l.timespend", "l.firstaccess", "l.lastaccess", "l.useragent", "l.useros", "l.userlang");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS l.id,
				c.fullname,
				l.page,
				l.param,
				l.visits,
				l.timespend,
				l.firstaccess,
				l.lastaccess,
				l.useragent,
				l.useros,
				l.userlang
						FROM {$CFG->prefix}local_intelliboard_tracking l
						LEFT JOIN {$CFG->prefix}course as c ON c.id = l.courseid
						WHERE l.userid = $params->userid $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report37($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("learner","u.email","u.id"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->custom){
			$sql_filter = " AND u.id IN($params->custom)";
		}

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email
				$sql_columns
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user as u
							WHERE ra.roleid  IN ($this->learner_roles) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 $sql_filter GROUP BY u.id $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");

		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report38($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("c.startdate", "ccc.timeend", "course", "learner", "e.enrol", "enrolstart", "enrolend", "complete", "complete"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			IF(ue.timestart = 0, ue.timecreated, ue.timecreated) as enrolstart,
			ue.timeend as enrolend,
			ccc.timeend,
			c.startdate,
			c.enablecompletion,
			cc.timecompleted as complete,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			ue.userid,
			e.courseid,
			e.enrol,
			c.fullname as course
			$sql_columns
						FROM
							{$CFG->prefix}user_enrolments ue
							LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
							LEFT JOIN {$CFG->prefix}context ctx ON ctx.instanceid = e.courseid
							LEFT JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN {$CFG->prefix}course_completion_criteria as ccc ON ccc.course = e.courseid AND ccc.criteriatype = 2
								WHERE ra.roleid IN ($this->learner_roles) AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY ue.id $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report39($params)
	{
		global $USER, $CFG, $DB;

		$columns = array_merge(array("user","u.timecreated","u.firstaccess","u.lastaccess","lit1.timespend_site","lit2.timespend_courses","lit3.timespend_activities"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				u.firstaccess,
				u.lastaccess,
				lit1.timespend_site,
				lit2.timespend_courses,
				lit3.timespend_activities
				$sql_columns
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user as u
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_site FROM {$CFG->prefix}local_intelliboard_tracking GROUP BY userid) as lit1 ON lit1.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_courses FROM {$CFG->prefix}local_intelliboard_tracking WHERE courseid > 0 GROUP BY userid) as lit2 ON lit2.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_activities FROM {$CFG->prefix}local_intelliboard_tracking WHERE page='module' GROUP BY userid) as lit3 ON lit3.userid = u.id
							WHERE ra.roleid IN ($this->learner_roles) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 $sql_filter AND u.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY u.id $sql_having $sql_orger $sql_limit");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report40($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("course", "learner", "email", "ue.enrols", "ue.timecreated", "la.lastaccess", "gc.grade");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			u.email,
			ue.timecreated as enrolled,
			ue.userid,
			ue.enrols,
			la.lastaccess,
			gc.grade,
			c.id as cid,
			c.fullname as course
			$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN {local_intelliboard_tracking} la ON la.courseid = c.id AND la.userid = ue.userid AND la.page = 'course'
							LEFT JOIN (SELECT t.id,t.userid,t.courseid FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id AND t.page = 'course' AND
								l.timepoint BETWEEN $params->timestart AND $params->timefinish GROUP BY t.courseid, t.userid) as l ON l.courseid = ue.courseid AND l.userid = ue.userid
							WHERE l.id IS NULL $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report41($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("course", "learner", "certificate", "ci.timecreated", "ci.code");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ci.id,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			u.email,
			ce.name as certificate,
			ci.timecreated,
			ci.code,
			ci.userid,
			c.id as cid,
			c.fullname as course
			$sql_columns
						FROM {$CFG->prefix}certificate_issues as ci
							LEFT JOIN {$CFG->prefix}certificate as ce ON ce.id = ci.certificateid
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ci.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ce.course
							WHERE ci.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report43($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("user", "completed_courses", "grade", "lit.visits", "lit.timespend", "u.timecreated");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS DISTINCT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				ue.courses,
				round(gc.grade, 2) as grade,
				cm.completed_courses,
				lit.timespend,
				lit.visits
				FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user as u
					LEFT JOIN (SELECT ue.userid, count(DISTINCT e.courseid) as courses FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid AND ue.status = 0 and e.status = 0 GROUP BY ue.userid) as ue ON ue.userid = u.id
					LEFT JOIN (SELECT userid, count(id) as completed_courses FROM {course_completions} cc WHERE timecompleted > 0 GROUP BY userid) as cm ON cm.userid = u.id
					LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
					LEFT JOIN (SELECT l.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) as lit ON lit.userid = u.id
					WHERE ra.roleid IN ($this->learner_roles) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 AND u.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report44($params)
	{
		global $USER, $CFG, $DB;
		$columns = array("c.fullname", "users", "cc.completed");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS DISTINCT c.id, c.fullname, count(*) users, cc.completed
						FROM {user_enrolments} ue
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN (SELECT course, count(*) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) cc ON cc.course = e.courseid
						WHERE ue.status = 0 and e.status = 0 AND c.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY e.courseid $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report45($params)
	{
		global $USER, $CFG, $DB;

		$columns = array("user", "u.email", "all_att", "lit.timespend", "highest_grade", "lowest_grade", "cmc.timemodified");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		if($params->custom == 1)
			$sql_having = (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)=0':str_replace(' HAVING ',' HAVING (',$sql_having). ') AND COUNT(DISTINCT qa.id)=0';
		elseif($params->custom == 2)
			$sql_having = (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)':str_replace(' HAVING ',' HAVING (',$sql_having).') AND COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)';

 		$data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				COUNT(DISTINCT qa.id) as all_att,
				(MAX(qa.sumgrades)/q.sumgrades)*100 as highest_grade,
				(MIN(qa.sumgrades)/q.sumgrades)*100 as lowest_grade,
				lit.timespend,
				cmc.timemodified
				$sql_columns
				FROM {quiz} q
					LEFT JOIN (".$this->getUsersEnrolsSql().") ue ON ue.courseid=q.course
					LEFT JOIN {user} u ON u.id=ue.userid
					LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.userid=ue.userid
					JOIN {modules} m ON m.name='quiz'
					LEFT JOIN {course_modules} cm ON cm.course=q.course AND cm.module=m.id AND cm.instance=q.id
					LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1 AND cmc.userid=ue.userid
					LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ue.userid AND lit.courseid=q.course AND lit.param=cm.id AND lit.page='module'
				WHERE q.id=$params->courseid $sql_where GROUP BY u.id $sql_filter $sql_having $sql_orger $sql_limit
				");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");

		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report42($params)
	{
		global $USER, $CFG, $DB;

		$sql_grades = '';
		$grades = array();
		if(!empty($params->custom)){
			foreach(explode(',',$params->custom) as $item){
				$grade = explode('-',$item);
				$grades[] = "(g.finalgrade/g.rawgrademax)*100 BETWEEN ".$grade[0]." AND ".$grade[1];
			}
			$sql_grades = '('.implode(' OR ',$grades).') AND ';
		}else{
			return array(
					"recordsTotal"    => 0,
					"recordsFiltered" => 0,
					"data"            => '');
		}

		$columns = array_merge(array("student", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			cri.gradepass,
			ue.userid,
			ue.timecreated as started,
			c.id as cid,
			c.fullname,
			git.average,
			AVG((g.finalgrade/g.rawgrademax)*100) AS `grade`,
			cmc.completed,
			CONCAT(u.firstname, ' ', u.lastname) AS student,
			lit.timespend,
			lit.visits,
			c.enablecompletion,
			cc.timecompleted as complete
			$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {$CFG->prefix}course_completion_criteria as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = c.id AND lit.userid = u.id
							LEFT JOIN (".$this->getCourseGradeSql('average').") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {$CFG->prefix}course_modules_completion cmc, {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
								WHERE $sql_grades u.deleted = 0 AND u.suspended = 0 $sql_filter AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish GROUP BY ue.userid, ue.courseid $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report46($params)
	{
		global $DB;

		$data = $DB->get_records_sql("SELECT gi.id,
											IF(gi.itemname != '', gi.itemname,
											  IF(gc.depth=1,c.fullname,gc.fullname)) as itemname,
										   gi.itemtype,
										   gi.itemmodule,
										   gi.iteminstance,
										   gi.courseid,
										   gc.id as cat_id,
										   gc.parent as cat_parent,
										   gc.depth,
										   gc.path,
										   ((gg.finalgrade-gg.rawgrademin)/(gg.rawgrademax-gg.rawgrademin))*100 as grade,
										   gg.rawgrademax as max_grade,
										   cc.timecompleted as course_completed,
										   gg.timemodified as timegraded,
										   IF(gi.itemmodule='assign',MAX(ass.timecreated),
											 IF(gi.itemmodule='quiz',MAX(qa.timefinish),NULL)) as lastsubmit

									FROM (".$this->getUsersEnrolsSql().") as ue
										LEFT JOIN {grade_items} gi ON gi.courseid = ue.courseid
										LEFT JOIN {grade_categories} gc ON IF(gi.itemtype='category' OR gi.itemtype='course' ,gc.id=gi.iteminstance,gc.id=gi.categoryid)
										LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=ue.userid
										LEFT JOIN {course} c ON c.id=gi.courseid
										LEFT JOIN {quiz_attempts} qa ON qa.quiz=gi.iteminstance AND qa.userid=ue.userid AND qa.state='finished'
										LEFT JOIN {assign_submission} ass ON ass.assignment=gi.iteminstance AND ass.userid=ue.userid AND ass.status='submitted'
										LEFT JOIN {course_completions} cc ON cc.course=gi.courseid AND cc.userid=ue.userid
									WHERE ue.userid = $params->userid AND gi.id IS NOT NULL
									GROUP BY gi.id
									ORDER BY gc.depth desc, gc.id ASC");
		return array('data'=>$data);
	}
	function report47($params)
	{
		global $DB, $CFG;

		if($CFG->version < 2014051200){
		   $table = "log";
		   $table_time = "time";
		   $table_course = "course";
		  }else{
		   $table = "logstore_standard_log";
		   $table_time = "timecreated";
		   $table_course = "courseid";
		  }

		$columns = array("user_related", "u_related.email", "course_name", "git.score", "ue.role", "lsl.all_count", "user_action", "action_role", "action", "timecreated");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_where = '';
		if($params->courseid){
			$sql_where = "WHERE ue.courseid IN ($params->courseid)";
		}

		$data = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS ue.id,
										ue.courseid,
										c.fullname as course_name,
										ue.role,
										lsl.all_count,
										IF(lsl.all_count>1,r.shortname,'-') as action_role,
										IF(lsl.all_count>1,log.action,'-') as action,
										IF(lsl.all_count>1,log.$table_time,'-') as timecreated,
										IF(lsl.all_count>1,CONCAT(u_action.firstname, ' ', u_action.lastname),'-') AS user_action,
										u_action.id as user_action_id,
										CONCAT(u_related.firstname, ' ', u_related.lastname) AS user_related,
										u_related.email,
										u_related.id as user_related_id,
										git.score
										$sql_columns
										FROM (SELECT ue.id, e.courseid, ue.userid, ue.timecreated, GROUP_CONCAT( DISTINCT r.shortname) AS role
														FROM
															{user_enrolments} ue,
															{enrol} e,
															{role_assignments} ra,
															{role} r,
															{context} ctx
														WHERE
															e.id = ue.enrolid AND
															ctx.instanceid = e.courseid AND
															ra.contextid = ctx.id AND
															ue.userid = ra.userid AND
															ra.roleid = r.id
														GROUP BY e.courseid, ue.userid) as ue
											LEFT JOIN ( SELECT
														lsl.$table_course,
														lsl.relateduserid,
														MAX(lsl.id) as last_change,
														COUNT(lsl.id) as all_count
													   FROM {".$table."} as lsl
													   WHERE (lsl.action='assigned' OR lsl.action='unassigned') AND lsl.target='role' AND lsl.contextlevel=50 GROUP BY lsl.$table_course,lsl.relateduserid
													  ) as lsl ON lsl.$table_course=ue.courseid AND lsl.relateduserid=ue.userid
											LEFT JOIN {".$table."} log ON log.id=lsl.last_change
											LEFT JOIN {role} r ON r.id=log.objectid
											LEFT JOIN {user} u_action ON u_action.id=log.userid
											LEFT JOIN {user} u_related ON u_related.id=log.relateduserid
											LEFT JOIN {course} c ON c.id=ue.courseid
											LEFT JOIN (".$this->getCourseGradeSql('score').") git ON git.courseid=c.id
											$sql_where $sql_having $sql_orger $sql_limit
										");
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function report58($params)
	{
		global $USER, $CFG, $DB;

		$sql_limit = $this->get_limit_sql($params);
		$sql_id = (int) $params->custom;

		$data = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS gi.id,
					gi.itemname,
					cm.id as cmid,
					cm.completionexpected,
					c.fullname,
					cm.completionexpected
						FROM {$CFG->prefix}grade_items gi
							LEFT JOIN {$CFG->prefix}course c ON c.id = gi.courseid
							LEFT JOIN {$CFG->prefix}modules m ON m.name = gi.itemmodule
							LEFT JOIN {$CFG->prefix}course_modules cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {$CFG->prefix}course_modules_completion as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $sql_id
								WHERE cm.visible = 1 AND gi.itemtype = 'mod' AND cm.completionexpected > ".time()."  AND (cmc.id IS NULL OR cmc.completionstate=0) ORDER BY cm.completionexpected ASC $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function report56($params)
	{
		global $USER, $CFG, $DB;

 		$columns = array("username", "c.fullname", "ue.enrols", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

		$sql_having = $this->get_filter_sql($params->filter, $columns);
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

  		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			CONCAT(u.firstname, ' ', u.lastname) as username,
			u.id as userid,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			ue.enrols,
			l.timespend,
			l.visits,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date,
			round(((cmc.completed/cmm.modules)*100), 0) as progress
						FROM (".$this->getUsersEnrolsSql(0).") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = ue.userid
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid=$params->userid GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
						WHERE ue.userid IN($params->custom) $sql_having $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}
	function analytic1($params)
	{
		global $USER, $CFG, $DB;

		if($CFG->version < 2014051200){
		   $table = "log";
		   $table_time = "time";
		   $table_course = "course";
		  }else{
		   $table = "logstore_standard_log";
		   $table_time = "timecreated";
		   $table_course = "courseid";
		  }

		$data = $DB->get_records_sql("SELECT
									  SQL_CALC_FOUND_ROWS id,
									  COUNT(id) AS count,
									   WEEKDAY(FROM_UNIXTIME($table_time,'%Y-%m-%d %T')) as day,
									   IF(FROM_UNIXTIME($table_time,'%H')>=6 && FROM_UNIXTIME($table_time,'%H')<12,'1',
										 IF(FROM_UNIXTIME($table_time,'%H')>=12 && FROM_UNIXTIME($table_time,'%H')<17,'2',
										 IF(FROM_UNIXTIME($table_time,'%H')>=17 && FROM_UNIXTIME($table_time,'%H')<=23,'3',
										 IF(FROM_UNIXTIME($table_time,'%H')>=0 && FROM_UNIXTIME($table_time,'%H')<6,'4','undef')))) as time_of_day
									 FROM {$CFG->prefix}{$table}
									 WHERE `$table_course` > 1 AND $table_time BETWEEN $params->timestart AND $params->timefinish GROUP BY day,time_of_day ORDER BY time_of_day, day");

		return array("data" => $data);
	}
	function analytic2($params)
	{
		global $USER, $CFG, $DB;
		if(isset($params->custom) && $params->custom == 'get_users'){
			$users = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS uid.id,
									  u.id AS user_id,
									  u.email,
									  u.firstname,
									  u.lastname,
									  uif.id AS fieldid,
									  uif.name AS fieldname,
									  uid.data AS fiedvalue
									 FROM {user_info_field} uif
										LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
										LEFT JOIN {user} u ON u.id=uid.userid
									 $where ");
			return array('users'=>$users);
		}

		$data = $DB->get_records_sql("SELECT
									  SQL_CALC_FOUND_ROWS uid.id,
									  uif.id AS fieldid,
									  uif.name,
									  COUNT(uid.userid) AS users,
									  uid.data
									 FROM {user_info_field} uif
										LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
									 WHERE uif.id>0 GROUP BY uid.data,uif.id");

		$users = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS uid.id,
									  u.id AS user_id,
									  u.email,
									  u.firstname,
									  u.lastname,
									  uif.id AS fieldid,
									  uif.name AS fieldname,
									  uid.data AS fiedvalue
									 FROM {user_info_field} uif
										LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
										LEFT JOIN {user} u ON u.id=uid.userid
									 WHERE uif.id>0 ");

		return array("data" => $data, 'users'=>$users);
	}

	function get_quizes($params){
		global $DB;
		$where = '';
		if(!empty($params->courseid))
			$where .= ' WHERE q.course IN('.$params->courseid.')';
		return array('data'=>$DB->get_records_sql("SELECT q.id, q.name, c.id as courseid, c.fullname as coursename
													FROM {quiz} q
														LEFT JOIN {course} c ON c.id=q.course
												$where"));
	}

	function analytic3($params)
	{
		global $USER, $CFG, $DB;
		$data = array();
		if(is_numeric($params->custom)){
			$where = '';
			if($params->custom > 0)
				$where .= ' AND q.id='.$params->custom;
			if($params->courseid > 0)
				$where .= " AND q.course=$params->courseid";

			$data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS qas.id,
											que.id,
											que.name,
											SUM(IF(qas.state LIKE '%partial' OR qas.state LIKE '%right',1,0)) as rightanswer,
											COUNT(qas.id) as allanswer
										FROM {quiz} q
											LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
											LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
											LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
											LEFT JOIN {question} que ON que.id=qua.questionid
										WHERE que.id IS NOT NULL $where GROUP BY que.id");

			$time = $DB->get_records_sql("SELECT
										  SQL_CALC_FOUND_ROWS qa.id,
										  COUNT(qa.id) AS count,
										   WEEKDAY(FROM_UNIXTIME(qa.timefinish,'%Y-%m-%d %T')) as day,
										   IF(FROM_UNIXTIME(qa.timefinish,'%H')>=6 && FROM_UNIXTIME(qa.timefinish,'%H')<12,'1',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=12 && FROM_UNIXTIME(qa.timefinish,'%H')<17,'2',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=17 && FROM_UNIXTIME(qa.timefinish,'%H')<=23,'3',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=0 && FROM_UNIXTIME(qa.timefinish,'%H')<6,'4','undef')))) as time_of_day
										 FROM {quiz} q
											LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.state='finished' AND qa.sumgrades IS NOT NULL
										 WHERE q.id>0 $where GROUP BY day,time_of_day ORDER BY time_of_day, day");

			$grades = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS gg.id,
											q.id AS quiz_id,
											q.name AS quiz_name,
											ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS gradepass,
											COUNT(DISTINCT gg.userid) AS users,
											ROUND(((gg.rawgrade - gg.rawgrademin)/(gg.rawgrademax - gg.rawgrademin))*100,0) AS grade
										 FROM {quiz} q
											LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
											LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid<>2 AND gg.rawgrade IS NOT NULL
										 WHERE gg.rawgrade IS NOT NULL $where GROUP BY ROUND(((gg.rawgrade - gg.rawgrademin)/(gg.rawgrademax - gg.rawgrademin))*100,0),quiz_id");
		}


		return array("data" => $data, "time"=>$time, "grades"=>$grades);
	}
	function analytic4($params)
	{
		global $USER, $CFG, $DB;

		if(!empty($params->custom)){
			if($params->custom == 'get_countries'){
				$students = 'ra.roleid='.str_replace(',',' OR ra.roleid=',$params->learner_roles);
				$countries = $DB->get_records_sql("SELECT
											SQL_CALC_FOUND_ROWS u.id,
											u.country,
											uid.data as state,
											COUNT(DISTINCT u.id) as users
										FROM {role_assignments} ra
											LEFT JOIN {user} u ON u.id=ra.userid
											LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
											LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ra.userid
										WHERE $students GROUP BY u.country,uid.data ");
				return array("countries" => $countries);
			}else{

				$columns = array_merge(array("u.firstname", "u.lastname", "u.email", "u.country", "state", "course", "ue.enrols", "gc.grade", "l.timespend", "complete"), $this->get_filter_columns($params));

				$where = array();
				$where_str = '';
				$custom = unserialize($params->custom);
				if(!empty($custom['country']))
					$where[] = "u.country='".$custom['country']."'";
				if(isset($custom['state']) && !empty($custom['state']))
					$where[] = "uid.data LIKE '%(".$custom['state'].")%'";
				if(!empty($where))
					$where_str = " AND ".implode(' AND ',$where);

				$where_sql = "WHERE u.id IS NOT NULL ".$where_str;
				$order_sql = $this->get_order_sql($params, $columns);
				$limit_sql = $this->get_limit_sql($params);
				$sql_columns = $this->get_columns($params, "u.id");


				if(empty($custom['enrol']))
					$getUsersEnrolsSql = $this->getUsersEnrolsSql();
				else
					$getUsersEnrolsSql = $this->getUsersEnrolsSql(array(), array($custom['enrol']));

				$users = $DB->get_records_sql("SELECT
														SQL_CALC_FOUND_ROWS ue.id,
														gc.grade,
														c.enablecompletion,
														cc.timecompleted as complete,
														u.id as uid,
														u.email,
														u.country,
														uid.data as state,
														u.firstname,
														u.lastname,
														ue.enrols,
														l.timespend,
														c.id as cid,
														c.fullname as course
														$sql_columns
																	FROM (".$getUsersEnrolsSql.") as ue
																		LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
																		LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
																		LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
																		LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
																		LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
																		LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
																		LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
														$where_sql $order_sql $limit_sql
						");

				$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
				return array("users" => $users,"recordsTotal"    => key($size),"recordsFiltered" => key($size));
			}
		}

		$methods = $DB->get_records_sql("SELECT
											e.id,
											e.enrol,
											COUNT(DISTINCT ue.id) as users
										FROM {enrol} e
											LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
										WHERE e.id>0 GROUP BY e.enrol");
		$students = 'ra.roleid='.str_replace(',',' OR ra.roleid=',$params->learner_roles);
		$countries = $DB->get_records_sql("SELECT
											SQL_CALC_FOUND_ROWS u.id,
											u.country,
											uid.data as state,
											COUNT(DISTINCT u.id) as users
										FROM {role_assignments} ra
											LEFT JOIN {user} u ON u.id=ra.userid
											LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
											LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ra.userid
										WHERE $students GROUP BY u.country,uid.data ");

		$users = $DB->get_records_sql("SELECT
											u.country,
											COUNT(DISTINCT u.id) as users
										FROM {role_assignments} ra
											LEFT JOIN {user} u ON u.id=ra.userid
										WHERE $students GROUP BY u.country");

		return array("methods" => $methods, "countries" => $countries, "users" => $users);
	}
	function analytic5($params)
	{
		global $USER, $CFG, $DB;


		$data = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS qa.id,
										IF((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt),'first-last',
											IF(qa.userid=min_att.userid AND qa.attempt=min_att.attempt,'first','last')
										) as `attempt_category`,

										CONCAT(10*floor(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10),
												'-',
												10*floor(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10) + 10,
												'%'
											) as `range`,
										COUNT(qa.sumgrades) as count_att

									FROM {quiz_attempts} qa

										JOIN (SELECT id,userid, MAX(attempt) as attempt
												FROM {quiz_attempts}
											 WHERE quiz=$params->custom GROUP BY userid ) as max_att

										JOIN (SELECT id,userid, MIN(attempt) as attempt
												FROM {quiz_attempts}
											 WHERE quiz=$params->custom GROUP BY userid ) as min_att ON max_att.userid=min_att.userid

										LEFT JOIN {quiz} q ON q.id=qa.quiz
									WHERE qa.userid<>2 AND qa.quiz=$params->custom AND qa.sumgrades IS NOT NULL AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))
									GROUP BY `range`,`attempt_category`");

		$overall_info = $DB->get_record_sql("SELECT
												(SELECT AVG((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100) as average
													FROM {quiz_attempts} qa
														LEFT JOIN {quiz} q ON q.id=qa.quiz
													WHERE qa.userid<>2 AND qa.quiz=$params->custom AND qa.attempt=1 AND qa.state='finished'
												) as average_first_att,
												(SELECT COUNT(qa.id)
													FROM {quiz_attempts} qa
													WHERE qa.userid<>2 AND qa.quiz=$params->custom AND qa.state='finished'
												) as count_att,
												(SELECT COUNT(qa.attempt)/COUNT(DISTINCT qa.userid)
													FROM {quiz_attempts} qa
													WHERE qa.userid<>2 AND qa.quiz=$params->custom
												) as avg_att
										");

		$question_info = $DB->get_records_sql("SELECT
													SQL_CALC_FOUND_ROWS qas.id,
													IF((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt),'first-last',
																							IF(qa.userid=min_att.userid AND qa.attempt=min_att.attempt,'first','last')
																						) as `attempt_category`,
													que.id as questionid,
													que.name,
													que.questiontext,
													AVG(((qas.fraction-qua.minfraction)/(qua.maxfraction-qua.minfraction))*100) as scale,
													COUNT(qa.id) as count_users
												FROM {quiz} q

													JOIN (SELECT id,userid, MAX(attempt) as attempt
															FROM {quiz_attempts}
															 WHERE quiz=$params->custom AND userid<>2 GROUP BY userid ) as max_att

													JOIN (SELECT id,userid, MIN(attempt) as attempt
															FROM {quiz_attempts}
														 WHERE quiz=$params->custom AND userid<>2 GROUP BY userid ) as min_att ON max_att.userid=min_att.userid

													LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))

													LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid

													LEFT JOIN (
															SELECT questionattemptid, MAX(sequencenumber) as number
															FROM {question_attempt_steps}
															GROUP BY questionattemptid
														) as last_update ON last_update.questionattemptid=qua.id

													LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.sequencenumber=last_update.number
													LEFT JOIN {question} que ON que.id=qua.questionid

												WHERE q.id=$params->custom GROUP BY `attempt_category`,que.id");

		return array("data" => $data, 'overall_info'=>$overall_info, 'question_info'=>$question_info);
	}

	function analytic6($params){
		global $DB,$CFG;

		if($CFG->version < 2014051200){
		   $table = "log";
		   $table_time = "time";
		   $table_course = "course";
		  }else{
		   $table = "logstore_standard_log";
		   $table_time = "timecreated";
		   $table_course = "courseid";
		  }

		$interactions = $DB->get_records_sql("SELECT
											SQL_CALC_FOUND_ROWS log.id,
											COUNT(log.id) AS `all`,
											SUM(IF(log.userid=$params->custom ,1,0)) as user,
											FROM_UNIXTIME(log.$table_time,'%m/%d/%Y') as `day`
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN {".$table."} log ON c.instanceid=log.$table_course AND ra.userid=log.userid
										WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND log.$table_time BETWEEN $params->timestart AND $params->timefinish GROUP BY `day`
										ORDER BY day DESC");

		$access = $DB->get_records_sql("SELECT
											SQL_CALC_FOUND_ROWS log.id,
											COUNT(log.id) AS `all`,
											SUM(IF(log.userid=$params->custom ,1,0)) as user,
											FROM_UNIXTIME(log.$table_time,'%m/%d/%Y') as `day`
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN {".$table."} log ON c.instanceid=log.$table_course AND ra.userid=log.userid
										WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND log.target='course' AND log.action='viewed' AND log.$table_time BETWEEN $params->timestart AND $params->timefinish GROUP BY `day`
										ORDER BY day DESC");

		$timespend = $DB->get_record_sql("SELECT
											SUM(t.timespend) AS `all`,
											tu.timespend AS user
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN (".$this->getCurseUserTimeSql().") t ON t.courseid=c.instanceid AND t.userid=ra.userid
											LEFT JOIN (".$this->getCurseUserTimeSql().") tu ON tu.courseid=c.instanceid AND tu.userid=$params->custom
										WHERE c.instanceid=$params->courseid AND c.contextlevel=50
									");

		$count_students = $DB->get_record_sql("SELECT
												COUNT(DISTINCT ra.userid) as students
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 ");

		$user_quiz = $DB->get_records_sql("SELECT
												SQL_CALC_FOUND_ROWS qa.id,
												COUNT(qa.id) as `all`,
												SUM(IF(qa.userid=$params->custom,1,0)) as `user`,
												FROM_UNIXTIME(qa.timefinish,'%m/%d/%Y') as `day`
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
												LEFT JOIN {quiz} q ON q.course=c.instanceid
												LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.userid=ra.userid AND qa.state='finished'
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND qa.id IS NOT NULL AND qa.timefinish BETWEEN $params->timestart AND $params->timefinish GROUP BY `day`");

		$user_assign = $DB->get_records_sql("SELECT
												SQL_CALC_FOUND_ROWS asub.id,
												COUNT(asub.id) as `all`,
												SUM(IF(asub.userid=$params->custom,1,0)) as `user`,
												FROM_UNIXTIME(asub.timemodified,'%m/%d/%Y') as `day`
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
												LEFT JOIN {assign} a ON a.course=c.instanceid
												LEFT JOIN {assign_submission} asub ON asub.assignment=a.id AND asub.userid=ra.userid AND asub.status='submitted'
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND asub.id IS NOT NULL AND asub.timemodified BETWEEN $params->timestart AND $params->timefinish GROUP BY `day`");

		$score = $DB->get_record_sql("SELECT avg.grade as avg,
												user.grade as user
										FROM (".$this->getCourseGradeSql().") as avg,
											 (".$this->getCourseUserGradeSql().") as user
										WHERE avg.courseid=$params->courseid AND user.courseid=$params->courseid AND user.userid=$params->custom
									");
		return array("interactions" => $interactions,"access" => $access,"timespend" => $timespend,"user_quiz" => $user_quiz,"user_assign" => $user_assign,'score'=>$score,'count_students'=>$count_students);
	}

	function analytic7($params){
		global $DB;

		$countries = $DB->get_records_sql("SELECT
								SQL_CALC_FOUND_ROWS u.id,
								u.country,
								uid.data as state,
								COUNT(DISTINCT u.id) as users
							FROM {context} c
								LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN( $params->learner_roles )
								LEFT JOIN {user} u ON u.id=ra.userid
								LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
								LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ra.userid
							WHERE c.contextlevel=50 AND c.instanceid IN ($params->courseid) AND u.id IS NOT NULL GROUP BY u.country,uid.data ");

		if($params->custom == 'get_countries'){
			return array("countries" => $countries);
		}

		$enroll_methods = $DB->get_records_sql("SELECT
													e.id,
													e.enrol,
													COUNT(DISTINCT ue.id) as users
												FROM {enrol} e
													LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
												WHERE e.id>0 AND e.courseid IN ($params->courseid) GROUP BY e.enrol
											");

		$complettions = $DB->get_record_sql("SELECT
													SUM(IF(gg.finalgrade>gg.rawgrademin AND cc.timecompleted IS NULL,1,0)) as not_completed,
													SUM(IF(cc.timecompleted>0,1,0)) as completed,
													SUM(IF(cc.timestarted>0 AND cc.timecompleted IS NULL AND (gg.finalgrade=gg.rawgrademin OR gg.finalgrade IS NULL),1,0)) as in_progress
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN($params->learner_roles)
												LEFT JOIN {user} u ON u.id=ra.userid
												LEFT JOIN {course_completions} cc ON cc.course=c.instanceid AND cc.userid=u.id
												LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.instanceid
												LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
											WHERE c.contextlevel=50 AND c.instanceid IN ($params->courseid) AND u.id IS NOT NULL ");

		$grade_range = $DB->get_records_sql("SELECT
													CONCAT(10*floor((((gg.finalgrade-gg.rawgrademin)/(gg.rawgrademax-gg.rawgrademin))*100)/10),
																							'-',
															10*floor((((gg.finalgrade-gg.rawgrademin)/(gg.rawgrademax-gg.rawgrademin))*100)/10) + 10,
																							'%'
																						) as `range`,
													COUNT(DISTINCT gg.userid) as users

											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN($params->learner_roles)
												LEFT JOIN {grade_items} gi ON gi.courseid=c.instanceid AND gi.itemtype='course'
												LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=ra.userid
											WHERE c.contextlevel=50 AND c.instanceid IN ($params->courseid) AND gg.rawgrademax IS NOT NULL GROUP BY `range`");

		return array("countries" => $countries, "enroll_methods" => $enroll_methods, "complettions" => $complettions, "grade_range" => $grade_range);
	}

	function analytic7table($params){
		global $USER, $CFG, $DB;

		$columns = array_merge(array("name", "u.email", "c.fullname", "u.country", "uid.data", "ue.enrols", "l.visits", "l.timespend", "gc.grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));


		$sql_filter = " AND c.id IN ($params->courseid) ";
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$where = array();
		$where_str = '';
		$custom = unserialize($params->custom);
		if(!empty($custom['country']) && $custom['country'] != 'world')
			$where[] = "u.country='".$custom['country']."'";
		if(isset($custom['state']) && !empty($custom['state']))
			$where[] = "uid.data LIKE '%(".$custom['state'].")%'";
		if(isset($custom['enrol']) && !empty($custom['enrol']))
			$where[] = "ue.enrols LIKE '%".$custom['enrol']."%'";
		if(isset($custom['grades']) && !empty($custom['grades'])){
			$grades = explode('-',$custom['grades']);
			$grades[1] = (empty($grades[1]))?110:$grades[1];
			$where[] = "gc.grade BETWEEN ".$grades[0]." AND ".($grades[1]-0.001);
		}
		if(isset($custom['user_status']) && !empty($custom['user_status'])){
			if($custom['user_status'] == 1){
				$where[] = "(gc.grade>0 AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
			}elseif($custom['user_status'] == 2){
				$where[] = "cc.timecompleted>0";
			}elseif($custom['user_status'] == 3){
				$where[] = "(cc.timestarted>0 AND (gc.grade=0 OR gc.grade IS NULL) AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
			}
		}
		if(!empty($where))
			$where_str = " AND ".implode(' AND ',$where);

		$where_sql = "WHERE u.id IS NOT NULL ".$where_str;


		$data = $DB->get_records_sql("SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			u.id as uid, u.email,
			u.country,
			uid.data as state,
			CONCAT(u.firstname, ' ', u.lastname) as name,
			ue.enrols,
			l.timespend,
			l.visits,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date
			$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
							LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
							LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
						$where_sql $sql_filter $sql_orger $sql_limit");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function analytic8($params){
		global $USER, $CFG, $DB;

		$columns = array("coursename", "cohortname", "learners_completed", "learners_not_completed", "learners_overdue", "avg_grade", "timespend");


		$sql_filter .= "";
		$sql_filter .= ($params->courseid)?" AND c.id IN ($params->courseid) ":'';
		$sql_filter .= (!empty($params->cohortid) && $params->cohortid != -1)?" AND cm.cohortid IN ($params->cohortid) ":'';
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$params->custom = ($params->custom)?$params->custom:time();

		$data = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS ue.id,
										c.id as courseid,
										c.fullname as coursename,
										cm.cohortid,
										coh.name as cohortname,
										AVG(gc.grade) as avg_grade,
										SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>0,1,0)) as learners_completed,
										SUM(IF(cr.completion IS NOT NULL AND (cc.timecompleted=0 OR cc.timecompleted IS NULL),1,0)) as learners_not_completed,
										SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>$params->custom ,1,0)) as learners_overdue,
										AVG(l.timespend) as timespend
										FROM (".$this->getUsersEnrolsSql().") as ue
											LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
											LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
											LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
											LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
											LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
											LEFT JOIN {$CFG->prefix}cohort_members cm ON cm.userid = u.id
											LEFT JOIN {$CFG->prefix}cohort coh ON coh.id=cm.cohortid
											LEFT JOIN (SELECT COUNT(id) as completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=ue.courseid
										WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY c.id,cm.cohortid $sql_orger $sql_limit
									");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");

		return array(
					"recordsTotal"    => key($size),
					"recordsFiltered" => key($size),
					"data"            => $data);
	}

	function analytic8details($params){
		global $USER, $CFG, $DB;
		$custom = json_decode($params->custom);

		if($params->cohortid == 0 && $params->courseid == 0){
			return array(
					"recordsTotal"    => 0,
					"recordsFiltered" => 0,
					"data"            => array());
		}

		if($custom->user_status == 1){
			$sql_where = " AND cc.timecompleted>0 ";
		}elseif($custom->user_status == 2){
			$sql_where = " AND (cc.timecompleted=0 OR cc.timecompleted IS NULL) ";
		}elseif($custom->user_status == 3){
			$sql_where = " AND cc.timecompleted>".$custom->duedate;
		}

		$columns = array_merge(array("coursename", "cohortname", "learnername", "u.email", "gc.grade", "l.timespend","cc.timecompleted"), $this->get_filter_columns($params));

		$sql_filter = "";
		$sql_filter .= ($params->courseid)?" AND c.id IN ($params->courseid) ":'';
		$sql_filter .= (!empty($params->cohortid) && $params->cohortid != -1 && $params->cohortid != -2)?" AND cm.cohortid IN ($params->cohortid) ":'';
		$sql_filter .= ($params->cohortid == -2)?" AND cm.cohortid IS NULL ":'';
		$sql_filter .= ($params->filter)?" AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%$params->filter%' ":'';
		$sql_orger = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_columns = $this->get_columns($params, "u.id");

		$data = $DB->get_records_sql("SELECT
										SQL_CALC_FOUND_ROWS ue.id,
										c.id as courseid,
										c.fullname as coursename,
										cm.cohortid,
										coh.name as cohortname,
										gc.grade,
										l.timespend,
										CONCAT(u.firstname, ' ', u.lastname) as learnername,
										u.email,
										cc.timecompleted
										$sql_columns
										FROM (".$this->getUsersEnrolsSql().") as ue
											LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
											LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
											LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
											LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
											LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
											LEFT JOIN {$CFG->prefix}cohort_members cm ON cm.userid = u.id
											LEFT JOIN {$CFG->prefix}cohort coh ON coh.id=cm.cohortid
											LEFT JOIN (SELECT COUNT(id) as completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=ue.courseid
										WHERE cr.completion IS NOT NULL AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_where $sql_filter $sql_orger $sql_limit
									");

		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");

		return array(
				"recordsTotal"    => key($size),
				"recordsFiltered" => key($size),
				"data"            => $data);
	}

	function get_users($params){
		global $DB;

		$data = $DB->get_records_sql("SELECT us.id, CONCAT(us.firstname,' ',us.lastname) as name
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN {user} us ON us.id=ra.userid
										WHERE us.id IS NOT NULL AND c.contextlevel=50 AND c.instanceid=$params->courseid
									");

		return array("data" => $data);
	}

	function get_grade_letters($params){
		global $DB;

		$data = $DB->get_records_sql("SELECT id,lowerboundary,letter
										FROM {grade_letters}
										WHERE contextid=1
									");

		return array("letters" => $data);
	}

	function get_questions($params)
	{
		global $USER, $CFG, $DB;

		if($CFG->version < 2012120301){
			$sql_extra = "q.questions";
		}else{
			$sql_extra = "qat.layout";
		}
		return $DB->get_records_sql("SELECT qa.id, ROUND(((qa.maxmark * qas.fraction) * q.grade / q.sumgrades),2) as grade, qa.slot, qu.id as attempt, q.name as quiz, que.name as question, que.questiontext, qas.userid, qas.state, qas.timecreated, FORMAT(((LENGTH($sql_extra) - LENGTH(REPLACE($sql_extra, ',', '')) + 1)/2), 0) as questions
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

	function get_activity($params)
	{
		global $USER, $CFG, $DB;

		$config = json_decode($params->custom);
		if($params->filter > 0 and $config->frequency){
			$params->timestart = $onlinestart = strtotime('-'.$config->frequency.' seconds');
		}else{
			$params->timestart = strtotime('-14 days');
			$onlinestart = strtotime('-10 minutes');
		}
		$params->timefinish = time() + 86400;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		$data = array();
		if($config->enrols){
			$data['enrols'] = $DB->get_records_sql("SELECT ue.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username,  ue.timecreated as timepoint, cx.id as context, c.id as cid, c.fullname as course, GROUP_CONCAT( DISTINCT e.enrol) AS enrols, GROUP_CONCAT( DISTINCT r.shortname) AS roles
					FROM {$CFG->prefix}user_enrolments ue
						LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
						LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
						LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
						LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND cx.contextlevel = 30
						LEFT JOIN {$CFG->prefix}context ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
						LEFT JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
						LEFT JOIN {$CFG->prefix}role r ON r.id = ra.roleid
							WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql GROUP BY ue.id ORDER BY ue.timecreated DESC LIMIT 10");
		}
		if($config->users){
			$data['users'] = $DB->get_records_sql("SELECT u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username,  u.timecreated as timepoint, cx.id as context, u.auth
						FROM {$CFG->prefix}user u
							LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND cx.contextlevel = 30
								WHERE u.timecreated BETWEEN $params->timestart AND $params->timefinish $sql ORDER BY u.timecreated DESC LIMIT 10");
			}
			if($config->completions){
					$data['completions'] = $DB->get_records_sql("SELECT cc.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username,  cx.id as context,  cc.timecompleted as timepoint, c.id as cid, c.fullname as course
								FROM {$CFG->prefix}course_completions cc, {$CFG->prefix}course c, {$CFG->prefix}user u
										LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND cx.contextlevel = 30
											WHERE u.id = cc.userid AND c.id = cc.course AND cc.timecompleted BETWEEN $params->timestart AND $params->timefinish $sql ORDER BY cc.timecompleted DESC LIMIT 10");
			}
			if($config->grades){
					$data['grades'] = $DB->get_records_sql("SELECT g.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username, cx.id as context, ((g.finalgrade/g.rawgrademax)*100) as grade, IFNULL(g.timemodified, g.timecreated)  as timepoint, gi.itemname, gi.itemtype,  gi.itemmodule, c.id as cid, c.fullname as course
								FROM {$CFG->prefix}grade_grades g, {$CFG->prefix}grade_items gi, {$CFG->prefix}course c, {$CFG->prefix}user u
										LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND cx.contextlevel = 30
											WHERE gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid AND g.finalgrade IS NOT NULL AND (g.timecreated BETWEEN $params->timestart AND $params->timefinish OR g.timemodified BETWEEN $params->timestart AND $params->timefinish) $sql ORDER BY g.timecreated DESC LIMIT 10");
			}
			if($config->online){
					$data['online'] = $DB->get_records_sql("SELECT u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.lastaccess as timepoint, cx.id as context
								FROM {$CFG->prefix}user u
									LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND cx.contextlevel = 30
										WHERE u.lastaccess BETWEEN $onlinestart AND $params->timefinish $sql ORDER BY u.timecreated DESC LIMIT 10");
			}

			return $data;
	}
	function get_total_info($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");
		$sql2 = $this->get_teacher_sql($params, "id", "users");
		$sql3 = $this->get_teacher_sql($params, "id", "courses");
		$sql4 = $this->get_teacher_sql($params, "course", "courses");

		return $DB->get_record_sql("SELECT
			(SELECT count(*) FROM {$CFG->prefix}user WHERE deleted = 0 AND username != 'guest' $sql2) as users,
			(SELECT count(*) FROM {$CFG->prefix}course WHERE visible = 1 and category > 0 $sql3) as courses,
			(SELECT count(*) FROM {$CFG->prefix}course_modules WHERE visible = 1  $sql4) as modules,
			(SELECT count(*) FROM {$CFG->prefix}course_categories WHERE visible = 1) as categories,
			(SELECT count(*) FROM {$CFG->prefix}user WHERE username != 'guest' and deleted = 0 AND suspended = 0 and lastaccess > 0 $sql) as learners,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE id > 0 $sql) as space,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE component='user' $sql) as userspace,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE filearea='content' $sql) as coursespace");
	}
	function get_system_users($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_record_sql("SELECT
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' $sql) as users,
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' and u.deleted = 1 $sql) as deleted,
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' and u.deleted = 0 AND u.suspended = 0 and u.lastaccess > 0 $sql) as active,
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' and (u.confirmed = 0 OR u.deleted = 1) $sql) as deactive,
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' and u.deleted = 0 and u.lastlogin > 0 $sql) as returned,
			(SELECT count(DISTINCT (u.id)) FROM {$CFG->prefix}user u, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.username != 'guest' and u.suspended = 1 $sql) as suspended,
			(SELECT count(DISTINCT (c.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}course_completions c, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and u.id = c.id $sql) as graduated,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and ee.id = e.enrolid AND e.userid=u.id $sql) as enrolled,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and ee.enrol = 'cohort' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_cohort,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and ee.enrol = 'manual' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_manual,
			(SELECT count(DISTINCT (e.userid)) FROM {$CFG->prefix}user u, {$CFG->prefix}enrol ee, {$CFG->prefix}user_enrolments e, {$CFG->prefix}role_assignments ra WHERE ra.roleid  IN ($this->learner_roles) and u.id = ra.userid and ee.enrol = 'self' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_self");
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
			(SELECT count(DISTINCT (userid)) FROM {$CFG->prefix}role_assignments WHERE roleid  IN ($this->learner_roles) $sql4) as students,
			(SELECT count(DISTINCT (userid)) FROM {$CFG->prefix}role_assignments WHERE roleid IN ($this->teacher_roles) $sql4) as tutors,
			(SELECT count(*) FROM {$CFG->prefix}course_modules_completion WHERE completionstate > 0 $sql4) as completed,
			(SELECT COUNT(*) FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' $sql4) as reviewed,
			(SELECT count(cm.id) FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE m.name = 'certificate' AND cm.module = m.id $sql3) as certificates");
	}

	function get_system_load($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");

		return $DB->get_record_sql("SELECT
			(SELECT sum(timespend) FROM {$CFG->prefix}local_intelliboard_tracking WHERE id > 0 $sql) as sitetimespend,
			(SELECT sum(timespend) FROM {$CFG->prefix}local_intelliboard_tracking WHERE courseid > 0 $sql) as coursetimespend,
            (SELECT sum(timespend) FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' $sql) as activitytimespend,
			(SELECT sum(visits) FROM {$CFG->prefix}local_intelliboard_tracking WHERE id > 0 $sql) as sitevisits,
			(SELECT sum(visits) FROM {$CFG->prefix}local_intelliboard_tracking WHERE courseid > 0 $sql) as coursevisits,
            (SELECT sum(visits) FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' $sql) as activityvisits");
	}

	function get_module_visits($params)
	{
		global $USER, $CFG, $DB;

		$sql0 = $this->get_teacher_sql($params, "userid", "users");
		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT m.id, m.name, sum(lit.visits) as visits FROM {$CFG->prefix}local_intelliboard_tracking lit, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module $sql GROUP BY m.id");
	}
	function get_useragents($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.useragent as name, count(lit.id) AS amount FROM {$CFG->prefix}local_intelliboard_tracking lit WHERE lit.userid IN (SELECT DISTINCT(userid) FROM {$CFG->prefix}role_assignments WHERE roleid  IN ($this->learner_roles)) $sql GROUP BY lit.useragent");
	}
	function get_useros($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.useros as name, count(lit.id) AS amount FROM {$CFG->prefix}local_intelliboard_tracking lit WHERE lit.userid IN (SELECT DISTINCT(userid) FROM {$CFG->prefix}role_assignments WHERE roleid  IN ($this->learner_roles)) $sql GROUP BY lit.useros");
	}
	function get_userlang($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.userlang as name, count(lit.id) AS amount FROM {$CFG->prefix}local_intelliboard_tracking lit WHERE lit.userid IN (SELECT DISTINCT(userid) FROM {$CFG->prefix}role_assignments WHERE roleid  IN ($this->learner_roles)) $sql GROUP BY lit.userlang");
	}


	//update
	function get_module_timespend($params)
	{
		global $USER, $CFG, $DB;

		$sql0 = $this->get_teacher_sql($params, "userid", "users");
		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT m.id, m.name, (sum(lit.timespend) / (SELECT sum(timespend) FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' $sql0)*100) as timeval, sum(lit.timespend) as timespend FROM {$CFG->prefix}local_intelliboard_tracking lit, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module $sql GROUP BY m.id");
	}

	function get_users_count($params)
	{
		global $USER, $CFG, $DB;


		$sql = $this->get_teacher_sql($params, "id", "users");

		return $DB->get_records_sql("SELECT auth, count(*) as users,
					(SELECT count(*) FROM {$CFG->prefix}user where username != 'guest' and deleted = 0 $sql) as amount
				FROM {$CFG->prefix}user WHERE username != 'guest' and deleted = 0 $sql GROUP BY auth");
	}



	function get_most_visited_courses($params)
	{
		 global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "l.courseid", "courses");

		return $DB->get_records_sql("SELECT c.id, c.fullname, sum(l.visits) as visits, sum(l.timespend) as timespend, gc.grade
				FROM {$CFG->prefix}local_intelliboard_tracking l
				LEFT JOIN {$CFG->prefix}course c ON c.id = l.courseid
				LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id
					WHERE c.category > 0 AND c.visible = 1 AND l.courseid > 0 $sql AND l.lastaccess BETWEEN $params->timestart AND $params->timefinish
						GROUP BY l.courseid
							ORDER BY visits DESC
								LIMIT 10");
	}
	function get_no_visited_courses($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");

		return $DB->get_records_sql("SELECT c.id, c.fullname, c.timecreated
					FROM  {$CFG->prefix}course c  WHERE c.category > 0 AND c.visible = 1 AND c.id NOT IN (SELECT courseid FROM {$CFG->prefix}local_intelliboard_tracking WHERE lastaccess BETWEEN $params->timestart AND $params->timefinish GROUP BY courseid) LIMIT 10");
	}
	function get_active_users($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_records_sql("
					SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) name,
					u.lastaccess,
					count(ue.courseid) as courses,
					avg(l.grade) as grade,
					sum(lit.visits) as visits,
					sum(lit.timespend) as timespend
				FROM
					(".$this->getUsersEnrolsSql().") as ue
					LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseUserGradeSql().") l ON l.courseid = ue.courseid AND l.userid = u.id
					LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = ue.courseid AND lit.userid = u.id
				WHERE u.deleted = 0 AND u.suspended = 0 $sql GROUP BY u.id ORDER BY lit.visits DESC LIMIT 10");
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

		return $DB->get_records_sql("SELECT c.id, c.timecreated, c.fullname, fs.coursesize
				FROM {$CFG->prefix}course c
					LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize FROM {$CFG->prefix}files f, {$CFG->prefix}context c WHERE c.id = f.contextid GROUP BY c.instanceid) fs ON fs.course = c.id
						WHERE c.category > 0 $sql LIMIT 20");

	}
	function get_active_ip_users($params, $limit = 10)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_records_sql("SELECT l.userid, l.userip, u.lastaccess as time, sum(l.visits) as visits, CONCAT( u.firstname, ' ', u.lastname ) AS name
					FROM {$CFG->prefix}local_intelliboard_tracking l,  {$CFG->prefix}user u
						WHERE u.id = l.userid AND l.lastaccess BETWEEN $params->timestart AND $params->timefinish $sql
							GROUP BY l.userid
								ORDER BY visits  DESC
									LIMIT 10");
	}

	function get_active_courses_per_day($params)
	{
		global $USER, $CFG, $DB;

		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 30){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}


		$data = $DB->get_records_sql("SELECT floor(timepoint / $ext) * $ext as timepoint, SUM(courses) as courses
				FROM {local_intelliboard_totals}
					WHERE timepoint BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timepoint / $ext) * $ext");


		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->timepoint - 86400).'.0';
			}
			$response[] = $item->timepoint.'.'.$item->courses;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->timepoint + 86400).'.0';
			}
			$k++;
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

		$data = $DB->get_records_sql("SELECT floor(lastaccess / $ext) * $ext as time, COUNT(id) as users
				FROM {$CFG->prefix}user
					WHERE id IN (SELECT DISTINCT(userid) FROM {$CFG->prefix}role_assignments WHERE roleid  IN ($this->learner_roles)) AND lastaccess BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(lastaccess / $ext) * $ext");


		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->time - 86400).'.0';
			}
			$response[] = $item->time.'.'.$item->users;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->time + 86400).'.0';
			}
			$k++;
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

		$data = $DB->get_records_sql("SELECT floor(timecreated / $ext) * $ext as time, COUNT(id) as courses
				FROM {$CFG->prefix}course
					WHERE category > 0 AND  timecreated BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timecreated / $ext) * $ext");


		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->time - 86400).'.0';
			}
			$response[] = $item->time.'.'.$item->courses;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->time + 86400).'.0';
			}
			$k++;
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

		$data = $DB->get_records_sql("SELECT floor(timecreated / $ext) * $ext as time, COUNT(id) as users
				FROM {$CFG->prefix}user
					WHERE timecreated BETWEEN $params->timestart AND $params->timefinish $sql
						GROUP BY floor(timecreated / $ext) * $ext");

		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->time - 86400).'.0';
			}
			$response[] = $item->time.'.'.$item->users;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->time + 86400).'.0';
			}
			$k++;
		}
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

		$data = $DB->get_records_sql("
			SELECT floor(timepoint / $ext) * $ext as timepoint, SUM(sessions) as users
				FROM {local_intelliboard_totals}
					WHERE timepoint BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timepoint / $ext) * $ext");
		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->timepoint - 86400).'.0';
			}
			$response[] = $item->timepoint.'.'.$item->users;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->timepoint + 86400).'.0';
			}
			$k++;
		}

		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
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
								LEFT JOIN (SELECT courseid, sum(lit.visits) as size FROM {$CFG->prefix}local_intelliboard_tracking GROUP BY courseid) l
									ON l.courseid = c.id WHERE $sql_c $sql LIMIT 0, 10");
	}
	function get_markers($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_records_sql("SELECT u.id, lit.userip, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email
				FROM {$CFG->prefix}user u, {$CFG->prefix}local_intelliboard_tracking lit
						WHERE u.id = lit.userid $sql
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

		$sql = $this->get_teacher_sql($params, "c.id", "courses");

		$sql_filter = ($params->filter) ? " AND c.fullname LIKE '%$params->filter%'" : "";
		$sql_limit = ($params->length or $params->start) ? "  LIMIT $params->start, $params->length" : "";

		return $DB->get_records_sql("SELECT
				c.id,
				c.fullname,
				ca.id as cid,
				ca.name as category
			FROM {$CFG->prefix}course c, {$CFG->prefix}course_categories ca
			WHERE c.category = ca.id $sql $sql_filter ORDER BY c.fullname $sql_limit");
	}


	function get_mudules(){
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT id, name FROM {$CFG->prefix}modules WHERE visible = 1");
	}
	function get_roles($params){
		global $USER, $CFG, $DB;

		if($params->filter){
			$sql = "'guest', 'frontpage'";
		}else{
			$sql = "'student', 'guest', 'user', 'frontpage'";
		}

		return $DB->get_records_sql("SELECT id, name, shortname
			FROM {$CFG->prefix}role
				WHERE archetype NOT IN ($sql)
					ORDER BY sortorder");
	}
	function get_tutors($params){
		global $USER, $CFG, $DB;

		$filter = ($params->filter) ? "a.roleid = $params->filter" : "a.roleid IN ($this->teacher_roles)";
		return $DB->get_records_sql("SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) as name, u.email
			FROM {$CFG->prefix}user u
				LEFT JOIN {$CFG->prefix}role_assignments a ON a.userid = u.id
				WHERE $filter AND u.deleted = 0 AND u.confirmed = 1 GROUP BY u.id");
	}


	function get_cminfo($params){
		global $USER, $CFG, $DB;

		$module = $DB->get_record_sql("SELECT cm.id, cm.instance, m.name FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE m.id = cm.module AND cm.id = ".intval($params->custom));

		return $DB->get_record($module->name, array('id'=>$module->instance));
	}



	function get_enrols($params){
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT e.id, e.enrol FROM {$CFG->prefix}enrol e GROUP BY e.enrol");
	}

	function get_teacher_sql($params, $column, $type)
	{
		$sql = '';
		if(isset($params->userid) and $params->userid){
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
								WHERE ra.roleid  IN ($this->learner_roles) AND ctx.instanceid = c.id AND c.visible=1 AND c.id IN($courses)
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
			$courses = $this->courses = $DB->get_records_sql("SELECT distinct(c.id) as id, c.fullname FROM {$CFG->prefix}course AS c, {$CFG->prefix}role_assignments AS ra
				JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
				WHERE c.visible=1 and ctx.instanceid = c.id AND ra.userid = $params->userid AND ra.roleid IN ($this->teacher_roles)");
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
	function get_learner($params){
		global $USER, $CFG, $DB;

		if($params->userid){
			$user = $DB->get_record_sql("SELECT
				u.*,
				cx.id as context,
				count(c.id) as completed,
				gc.grade,
				lit.timespend_site, lit.visits_site,
				lit2.timespend_courses, lit2.visits_courses,
				lit3.timespend_modules, lit3.visits_modules,
				(SELECT count(*) FROM {$CFG->prefix}course WHERE visible = 1 AND category > 0) as available_courses
				FROM {$CFG->prefix}user u
					LEFT JOIN {$CFG->prefix}course_completions c ON c.timecompleted > 0 AND c.userid = u.id
					LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
					LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = $params->userid) as gc ON gc.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_site, sum(visits) as visits_site FROM {$CFG->prefix}local_intelliboard_tracking WHERE userid = $params->userid) lit ON lit.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_courses, sum(visits) as visits_courses FROM {$CFG->prefix}local_intelliboard_tracking WHERE courseid > 0 AND userid = $params->userid) lit2 ON lit2.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_modules, sum(visits) as visits_modules FROM {$CFG->prefix}local_intelliboard_tracking WHERE page = 'module' AND userid = $params->userid) lit3 ON lit3.userid = u.id
				WHERE u.id = $params->userid");

			if($user->id){
				$user->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
				(SELECT
						round(avg(b.timespend_site),0) as timespend_site,
						round(avg(b.visits_site),0) as visits_site
					FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site
						FROM {$CFG->prefix}local_intelliboard_tracking
						WHERE userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN ($this->learner_roles)) and userid != $user->id
						GROUP BY userid) as b) a,
					(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
					FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g
					WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND
					g.userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN ($this->learner_roles)) and g.userid != $user->id GROUP BY g.userid) b) c");


				$user->data = $DB->get_records_sql("SELECT uif.id, uif.name, uid.data
						FROM
							{$CFG->prefix}user_info_field uif,
							{$CFG->prefix}user_info_data uid
						WHERE uif.id = uid.fieldid and uid.userid = $user->id
						ORDER BY uif.name");

				$user->grades = $DB->get_records_sql("SELECT g.id, gi.itemmodule, round(AVG( (g.finalgrade/g.rawgrademax)*100),2) AS grade
						FROM
							{$CFG->prefix}grade_items gi,
							{$CFG->prefix}grade_grades g
						WHERE  gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL and g.userid = $user->id
						GROUP BY gi.itemmodule ORDER BY g.timecreated DESC");

				$user->courses = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS ue.id,
					ue.userid,
					round(((cmc.completed/cmm.modules)*100), 0) as completion,
					c.id as cid,
					c.fullname
							FROM {$CFG->prefix}user_enrolments as ue
								LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
								LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
								LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
								LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
								LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
							WHERE ue.userid = $user->id GROUP BY e.courseid $sql_orger $sql_limit");
			}else{
				return false;
			}
		}else{
			return false;
		}
		return $user;
	}
	function get_learners($params)
	{
		global $USER, $CFG, $DB;

		$users = $DB->get_records_sql("SELECT u.*, cx.id as context, gc.average, ue.courses, c.completed, round(((c.completed/ue.courses)*100), 0) as progress
			FROM {$CFG->prefix}user u
			LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
			LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
			LEFT JOIN (".$this->getLearnerCoursesSql().") as ue ON ue.userid = u.id
			LEFT JOIN (SELECT userid, count(id) as completed FROM {$CFG->prefix}course_completions WHERE timecompleted > 0 GROUP BY userid) as c ON c.userid = u.id
			WHERE u.deleted = 0 and u.id IN ($params->filter)");

		if($params->custom == 'details'){
			foreach($users as &$user){
				$params->filter = $user->id;
				$user->visits = $this->get_learner_visits_per_day($params);
				$user->activity = $this->get_activity_learners($params);
			}
		}
		return $users;
	}
	function get_learner_courses($params){
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT c.id, c.fullname
							FROM {$CFG->prefix}user_enrolments as ue
								LEFT JOIN {$CFG->prefix}enrol as e ON e.id = ue.enrolid
								LEFT JOIN {$CFG->prefix}course as c ON c.id = e.courseid
							WHERE ue.userid = $params->userid GROUP BY e.courseid  ORDER BY c.fullname ASC");

	}
	function get_course($params)
	{
		global $USER, $CFG, $DB;

		$course = $DB->get_record_sql("SELECT c.id,
			c.fullname,
			c.timecreated,
			c.enablecompletion,
			c.format,
			c.startdate,
			ca.name as category,
			e.learners,
			cc.completed,
			gc.grade,
			gr.grades,
			cm.modules,
			s.sections,
			lit.timespend,
			lit.visits,
			lit2.timespend as timespend_modules,
			lit2.visits as visits_modules
			$sql_columns
				FROM {$CFG->prefix}course as c
					LEFT JOIN {$CFG->prefix}course_categories as ca ON ca.id = c.category
					LEFT JOIN (SELECT course, count( id ) AS modules FROM {$CFG->prefix}course_modules WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
					LEFT JOIN (SELECT gi.courseid, count(g.id) AS grades FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gr ON gr.courseid = c.id
					LEFT JOIN (SELECT course, count(*) as sections FROM {$CFG->prefix}course_sections where visible = 1 group by course) as s ON s.course = c.id
					LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id
					LEFT JOIN (".$this->getCourseLearnersSql().") e ON e.courseid = c.id
					LEFT JOIN (".$this->getCourseCompletedSql().") as cc ON cc.course = c.id
					LEFT JOIN (".$this->getCourseTimeSql().") as lit ON lit.courseid = c.id
					LEFT JOIN (".$this->getCourseTimeSql("timespend", "visits", " lit.page = 'module' AND ").") as lit2 ON lit2.courseid = c.id
						WHERE c.id IN ($params->courseid)");

		if($course->id){
			$course->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
					(SELECT
							round(avg(b.timespend_site),0) as timespend_site,
							round(avg(b.visits_site),0) as visits_site
						FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site
							FROM {$CFG->prefix}local_intelliboard_tracking
							WHERE userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN ($this->learner_roles)) and courseid != $course->id
							GROUP BY courseid) as b) a,
						(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
						FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g
						WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid != $course->id GROUP BY gi.courseid) b) c");

			$course->mods = $DB->get_records_sql("SELECT m.id, m.name, count( cm.id ) AS size FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE cm.visible = 1 and m.id = cm.module and cm.course = 2 GROUP BY cm.module");


			$course->teachers = $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as name, u.email, cx.id as context  FROM {$CFG->prefix}user AS u
								LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
								LEFT JOIN {$CFG->prefix}role_assignments AS ra ON u.id = ra.userid
								LEFT JOIN {$CFG->prefix}context AS ctx ON ra.contextid = ctx.id
								LEFT JOIN {$CFG->prefix}course AS c ON c.id = ctx.instanceid
									WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND c.id IN ($params->courseid)
										GROUP BY u.id");
		}
		return $course;
	}
	function get_activity_learners($params)
	{
		global $USER, $CFG, $DB;


		$completions = $DB->get_records_sql("SELECT cc.id, cc.timecompleted, c.id as cid, c.fullname as course
					FROM {$CFG->prefix}course_completions cc
						LEFT JOIN {$CFG->prefix}course c ON c.id = cc.course
						LEFT JOIN {$CFG->prefix}user u ON u.id = cc.userid
							WHERE cc.timecompleted BETWEEN $params->timestart AND $params->timefinish AND cc.userid IN ($params->filter) ORDER BY cc.timecompleted DESC LIMIT 10");

		$enrols = $DB->get_records_sql("SELECT ue.id, ue.timecreated, c.id as cid, c.fullname as course
					FROM {$CFG->prefix}user_enrolments ue
						LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
						LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
						LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
							WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND ue.userid IN ($params->filter) GROUP BY ue.userid, e.courseid ORDER BY ue.timecreated DESC LIMIT 10");

		$grades = $DB->get_records_sql("SELECT g.id, round(((g.finalgrade/g.rawgrademax)*100),0) AS grade, gi.courseid, gi.itemname, c.fullname as course, g.timecreated
					FROM
						{$CFG->prefix}grade_items gi,
						{$CFG->prefix}grade_grades g,
						{$CFG->prefix}course c,
						{$CFG->prefix}user u
				WHERE g.timecreated BETWEEN $params->timestart AND $params->timefinish AND g.userid IN ($params->filter) AND gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid ORDER BY g.timecreated DESC LIMIT 10");

		return array("enrols"=>$enrols, "grades"=>$grades, "completions"=>$completions);
	}

	function get_learner_visits_per_day($params)
	{
		global $USER, $CFG, $DB;


		$ext = 86400;


		$sql_filter = "";
		if($params->courseid){
			$sql_filter = "t.courseid  IN ($params->courseid) AND ";
		}
		$data = $DB->get_records_sql("SELECT floor(l.timepoint / $ext) * $ext as timepoint, sum(l.visits) as visits
				FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id AND $sql_filter t.userid IN ($params->filter) AND l.timepoint BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(l.timepoint / $ext) * $ext");


		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->timepoint - 86400).'.0';
			}
			$response[] = $item->timepoint.'.'.$item->visits;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->timepoint + 86400).'.0';
			}
			$k++;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_course_visits_per_day($params)
	{
		global $USER, $CFG, $DB;

		$sql_user = ($params->userid) ? " AND t.userid=$params->userid":"";
		$ext = 86400;

		$data = $DB->get_records_sql("SELECT floor(l.timepoint / $ext) * $ext as timepoint, SUM(l.visits) as visits
				FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id $sql_user AND t.courseid  IN ($params->courseid) AND l.timepoint BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(l.timepoint / $ext) * $ext");


		$response = array();
		$k = 1;
		foreach($data as $item){
			if(count($data) <= 1 and $k == 1){
				$response[] = ($item->timepoint - 86400).'.0';
			}
			$response[] = $item->timepoint.'.'.$item->visits;
			if(count($data) <= 1 and $k == count($data)){
				$response[] = ($item->timepoint + 86400).'.0';
			}
			$k++;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}


	function get_userinfo($params){
		global $USER, $CFG, $DB;

		return $DB->get_record_sql("SELECT u.*, cx.id as context
			FROM {$CFG->prefix}user u
				LEFT JOIN {$CFG->prefix}context cx ON cx.instanceid = u.id AND contextlevel = 30
			WHERE u.id = ".$params->filter);
	}
	function get_user_info_fields_data($params)
	{
		global $USER, $CFG, $DB;

		$sql = "";
		$sql .= ($params->filter) ? " AND fieldid IN ($params->filter)":"";
		$sql .= ($params->custom) ? " AND userid IN ($params->custom)":"";

		return $DB->get_records_sql("SELECT id, fieldid, data, count(id) as items FROM {$CFG->prefix}user_info_data  WHERE data != '' $sql GROUP BY data ORDER BY data ASC");
	}
	function get_user_info_fields($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_records_sql("SELECT uif.id, uif.name, uic.name as category FROM {$CFG->prefix}user_info_field uif, {$CFG->prefix}user_info_category uic WHERE uif.categoryid = uic.id ORDER BY uif.name");
	}
	function get_reportcard($params)
	{
		global $USER, $CFG, $DB, $SITE;

		$data = array();
		$data['stats'] = $DB->get_record_sql("SELECT
			(SELECT count(distinct e.courseid) FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) as courses,
			(SELECT count(distinct course) FROM {$CFG->prefix}course_completions WHERE timecompleted > 0 and userid = $params->userid) as completed,
			(SELECT COUNT(DISTINCT cm.id) FROM {$CFG->prefix}course_modules cm LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible = 1 AND cm.course in (SELECT distinct e.courseid FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) AND cm.visible=1 AND cm.completionexpected< ".time()." AND cm.completionexpected>0 AND (cmc.id IS NULL OR cmc.completionstate=0)) as missed,
			(SELECT COUNT(DISTINCT cm.id) FROM {$CFG->prefix}course_modules cm WHERE cm.course IN (SELECT distinct e.courseid FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) AND cm.visible=1 AND cm.completionexpected>0) as current,
			(SELECT count(id) FROM {$CFG->prefix}quiz WHERE course in (SELECT distinct e.courseid FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) and id NOT IN (SELECT quiz FROM {$CFG->prefix}quiz_grades WHERE userid = $params->userid AND grade > 0)) as quizes
		");

		$timestart = strtotime('today');
		$timefinish = $timestart + 86400;

		$data['courses'] = $DB->get_records_sql("SELECT c.id, c.fullname, a.assignments, b.missing, t.quizes, cc.timecompleted, g.grade
				FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = $params->userid
				LEFT JOIN (SELECT gi.courseid, (g.finalgrade/g.rawgrademax)*100 as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = $params->userid GROUP BY gi.courseid) g ON g.courseid = c.id
				LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as missing FROM {$CFG->prefix}course_modules cm LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible=1 AND cm.completionexpected < ".time()."  AND cm.completionexpected > 0 AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) b ON b.course = c.id
				LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as assignments FROM {$CFG->prefix}course_modules cm LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible=1 AND cm.completionexpected BETWEEN ".(time()-86400)." AND ".time()." AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) a ON a.course = c.id
				LEFT JOIN (SELECT course, count(id) as quizes FROM {$CFG->prefix}quiz WHERE id NOT IN (SELECT quiz FROM {$CFG->prefix}quiz_grades WHERE userid = $params->userid AND grade > 0) GROUP BY course) t ON t.course = c.id
				WHERE ue.status = 0 and e.status = 0 AND ue.userid = $params->userid ORDER BY c.fullname ASC");

		if(count($data['courses'])){
			require_once($CFG->libdir . "/gradelib.php");

			foreach($data['courses'] as $course){
				$context = context_course::instance($course->id,IGNORE_MISSING);

				$letters = grade_get_letters($context);
				foreach($letters as $lowerboundary=>$value){
					if($course->grade >= $lowerboundary){
						$course->grade = $value;
						break;
					}
				}
			}
		}

		return $data;
	}

	function get_dashboard_totals($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_record_sql("SELECT
			(SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND suspended = 0 and username != 'guest') as users,
			(SELECT COUNT(*) FROM {course} WHERE visible = 1 and category > 0) as courses,
			(SELECT COUNT(*) FROM {course_modules} WHERE visible = 1 ) as modules,
			(SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 ) as categories,
			(SELECT SUM(filesize) FROM {$CFG->prefix}files WHERE filesize > 0) as space");
	}
	function get_dashboard_avg($params)
	{
		global $USER, $CFG, $DB;

		return $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
						(SELECT round(avg(b.timespend_site),0) as timespend_site, round(avg(b.visits_site),0) as visits_site
							FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site
								FROM {$CFG->prefix}local_intelliboard_tracking
								WHERE userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN ($this->learner_roles)) and userid != 2 GROUP BY userid) as b) a,
						(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
							FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g
							WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN ($this->learner_roles)) and g.userid != 2 GROUP BY g.userid) b) c");
	}

	function get_dashboard_courses($params)
	{
		global $USER, $CFG, $DB;
		$sql = $this->get_teacher_sql($params, "c.id", "courses");

		return $DB->get_records_sql("SELECT c.id, c.fullname, count(*) users, cc.completed
				FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN (SELECT course, count(*) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) cc ON cc.course = e.courseid
				WHERE ue.status = 0 and e.status = 0 GROUP BY e.courseid ORDER BY cc.completed DESC LIMIT $params->start, $params->length");
	}
	function get_dashboard_countries($params)
	{
		global $USER, $CFG, $DB;
		$sql = $this->get_teacher_sql($params, "id", "users");

		return $DB->get_records_sql("SELECT country, count(*) as users FROM {user} WHERE confirmed = 1 and deleted = 0 and suspended = 0 and country != '' GROUP BY country");
	}
	function get_dashboard_enrols($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "e.courseid", "courses");

		return $DB->get_records_sql("SELECT e.id, e.enrol, count(ue.id) as enrols FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql GROUP BY e.enrol");
	}
	function get_dashboard_info($params)
	{
		global $USER, $CFG, $DB;

		if($params->custom == 'daily'){
			$timefinish = time();
			$timestart = strtotime('last week');
			$ext = 86400;
			$format = 'EEEE';
		}elseif($params->custom == 'weekly'){
			$timefinish = time();
			$timestart = strtotime('last month');
			$ext = 86400;
			$format = 'dd MMM';
		}elseif($params->custom == 'monthly'){
			$timefinish = time();
			$timestart = strtotime('-12 month');
			$ext = 604800;
			$format = 'MMMM';
		}else{
			$timefinish = strtotime('+1 year');
			$timestart = strtotime('-5 years');
			$ext = 31556926;
			$format = 'yyyy';
		}

		$data = array();
		$data[] = $format;
		$data[] = 'timepoint';

		$sql = $this->get_teacher_sql($params, "userid", "users");

		$data[] = $DB->get_records_sql("
			SELECT floor(timepoint / $ext) * $ext as timepoint, SUM(sessions) as visits
				FROM {local_intelliboard_totals}
					WHERE timepoint BETWEEN $timestart AND $timefinish $sql
						GROUP BY floor(timepoint / $ext) * $ext");

		$data[] = $DB->get_records_sql("
			SELECT floor(timecreated / $ext) * $ext as timecreated, COUNT(DISTINCT (userid)) as users
				FROM {user_enrolments}
					WHERE timecreated BETWEEN $timestart AND $timefinish $sql
						GROUP BY floor(timecreated / $ext) * $ext");

		$data[] = $DB->get_records_sql("
			SELECT floor(timecompleted / $ext) * $ext as timecreated, COUNT(DISTINCT (userid)) as users
				FROM {course_completions}
					WHERE timecompleted BETWEEN $timestart AND $timefinish $sql
						GROUP BY floor(timecompleted / $ext) * $ext");

		return $data;
	}
	function get_dashboard_stats($params)
	{
		global $USER, $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");
		$timeyesterday = strtotime('yesterday');
		$timelastweek = strtotime('last week');
		$timetoday = strtotime('today');
		$timeweek = strtotime('previous monday');
		$timefinish = time();

		$data = array();
		$data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timeyesterday AND $timetoday $sql) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timelastweek AND $timeweek $sql) as sessions_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timeyesterday AND $timetoday $sql) as enrolments_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timelastweek AND $timeweek $sql) as enrolments_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timeyesterday AND $timetoday $sql) as compl_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timelastweek AND $timeweek $sql) as compl_week");

		$data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timetoday AND $timefinish $sql) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timeweek AND $timefinish $sql) as sessions_week,
			(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN $timetoday AND $timefinish $sql) as enrolments_today,
			(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN $timeweek AND $timefinish $sql) as enrolments_week,
			(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN $timetoday AND $timefinish $sql) as compl_today,
			(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN $timeweek AND $timefinish $sql) as compl_week");
			return $data;
	}

	function set_notification_enrol($params)
	{
		set_config("enrol", $params->notification_enrol, "local_intelliboard");
		set_config("enrol_email", $params->notification_email, "local_intelliboard");
		set_config("enrol_subject", $params->notification_subject, "local_intelliboard");
		set_config("enrol_message", $params->notification_message, "local_intelliboard");
		return true;
	}
	function set_notification_auth($params)
	{
		set_config("auth", $params->notification_auth, "local_intelliboard");
		set_config("auth_email", $params->notification_email, "local_intelliboard");
		set_config("auth_subject", $params->notification_subject, "local_intelliboard");
		set_config("auth_message", $params->notification_message, "local_intelliboard");
		return true;
	}
}
