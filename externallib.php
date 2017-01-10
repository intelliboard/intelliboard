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

error_reporting(E_ALL);
ini_set('display_errors', '1');


class local_intelliboard_external extends external_api {

	var $params = array();
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
                        	'function' => new external_value(PARAM_ALPHANUMEXT, 'Main Function name', VALUE_REQUIRED),
                            'timestart' => new external_value(PARAM_INT, 'Time start param', VALUE_OPTIONAL, 0),
                            'timefinish' => new external_value(PARAM_INT, 'Time finish param', VALUE_OPTIONAL, 0),
                            'start' => new external_value(PARAM_INT, 'Pagination start', VALUE_OPTIONAL, 0),
                            'length' => new external_value(PARAM_INT, 'Pagination length', VALUE_OPTIONAL, 0),
                            'columns' => new external_value(PARAM_SEQUENCE, 'Profile columns', VALUE_OPTIONAL, 0),
                            'filter_columns' => new external_value(PARAM_SEQUENCE, 'Filter columns param', VALUE_OPTIONAL, 0),
                            'filter_profile' => new external_value(PARAM_INT, 'Filter profile column param', VALUE_OPTIONAL, 0),
                            'order_column' => new external_value(PARAM_INT, 'Order column param', VALUE_OPTIONAL, 0),
                            'order_dir' => new external_value(PARAM_ALPHA, 'Order direction param', VALUE_OPTIONAL, ''),
                            'filter' => new external_value(PARAM_RAW, 'Filter var', VALUE_OPTIONAL, ''),
                            'custom' => new external_value(PARAM_RAW, 'Custom var', VALUE_OPTIONAL, ''),
                            'custom2' => new external_value(PARAM_RAW, 'Custom2 var', VALUE_OPTIONAL, ''),
                            'custom3' => new external_value(PARAM_RAW, 'Custom3 var', VALUE_OPTIONAL, ''),
                            'notification_enrol' => new external_value(PARAM_RAW, 'Notification enrol', VALUE_OPTIONAL, ''),
                            'notification_auth' => new external_value(PARAM_RAW, 'Notification auth', VALUE_OPTIONAL, ''),
                            'notification_email' => new external_value(PARAM_RAW, 'Notification email', VALUE_OPTIONAL, ''),
                            'notification_subject' => new external_value(PARAM_RAW, 'Notification subject', VALUE_OPTIONAL, ''),
                            'notification_message' => new external_value(PARAM_RAW, 'Notification message', VALUE_OPTIONAL, ''),
                            'teacher_roles' => new external_value(PARAM_SEQUENCE, 'Teacher roles', VALUE_OPTIONAL, 0),
                            'learner_roles' => new external_value(PARAM_SEQUENCE, 'Learner roles', VALUE_OPTIONAL, 0),
                            'users' => new external_value(PARAM_SEQUENCE, 'Users SEQUENCE', VALUE_OPTIONAL, 0),
                            'userid' => new external_value(PARAM_INT, 'Instuctor ID', VALUE_OPTIONAL, 0),
                            'sizemode' => new external_value(PARAM_INT, 'Size mode', VALUE_OPTIONAL, 0),
                            'debug' => new external_value(PARAM_INT, 'Debug mode', VALUE_OPTIONAL, 0),
                            'courseid' => new external_value(PARAM_SEQUENCE, 'Course IDs SEQUENCE', VALUE_OPTIONAL, 0),
                            'cohortid' => new external_value(PARAM_SEQUENCE, 'Cohort IDs SEQUENCE', VALUE_OPTIONAL, 0),
                            'filter_user_deleted' => new external_value(PARAM_INT, 'filter_user_deleted', VALUE_OPTIONAL, 0),
                            'filter_user_suspended' => new external_value(PARAM_INT, 'filter_user_suspended', VALUE_OPTIONAL, 0),
                            'filter_user_guest' => new external_value(PARAM_INT, 'filter_user_guest', VALUE_OPTIONAL, 0),
                            'filter_course_visible' => new external_value(PARAM_INT, 'filter_course_visible', VALUE_OPTIONAL, 0),
                            'filter_enrolmethod_status' => new external_value(PARAM_INT, 'filter_enrolmethod_status', VALUE_OPTIONAL, 0),
                            'filter_enrol_status' => new external_value(PARAM_INT, 'filter_enrol_status', VALUE_OPTIONAL, 0),
                            'filter_enrolled_users' => new external_value(PARAM_INT, 'filter_enrolled_users', VALUE_OPTIONAL, 0),
                            'filter_module_visible' => new external_value(PARAM_INT, 'filter_module_visible', VALUE_OPTIONAL, 0)
                        )
                    )
				)
            )
        );
    }

    public static function database_query($params) {
        global $CFG, $DB;

        require_once($CFG->dirroot .'/local/intelliboard/locallib.php');

        $params = self::validate_parameters(self::database_query_parameters(), array('params' => $params));

        self::validate_context(context_system::instance());

		$transaction = $DB->start_delegated_transaction();
		$obj = new local_intelliboard_external();

		$params = (object)reset($params['params']);
		$params->userid = isset($params->userid) ? $params->userid : 0;
		$params->courseid = isset($params->courseid) ? $params->courseid : 0;
		$params->cohortid = isset($params->cohortid) ? $params->cohortid : 0;
		$params->users = isset($params->users) ? $params->users : 0;
		$params->start = isset($params->start) ? $params->start : 0;
		$params->length = isset($params->length) ? $params->length : 50;
		$params->filter = isset($params->filter) ? clean_raw($params->filter) : '';
		$params->custom = isset($params->custom) ? clean_raw($params->custom, false) : '';
		$params->custom2 = isset($params->custom2) ? clean_raw($params->custom2) : '';
		$params->custom3 = isset($params->custom3) ? clean_raw($params->custom3) : '';
		$params->columns = isset($params->columns) ? $params->columns : '';
		$params->filter_columns = (isset($params->filter_columns)) ? $params->filter_columns : "0,1";
		$params->filter_profile = (isset($params->filter_profile)) ? $params->filter_profile : 0;
		$params->timestart = (isset($params->timestart)) ? $params->timestart : 0;
		$params->timefinish = (isset($params->timefinish)) ? $params->timefinish : 0;
		$params->sizemode = (isset($params->sizemode)) ? $params->sizemode : 0;
		$params->debug = (isset($params->debug)) ? $params->debug : 0;
		$params->filter_user_deleted = (isset($params->filter_user_deleted)) ? $params->filter_user_deleted : 0;
		$params->filter_user_suspended = (isset($params->filter_user_suspended)) ? $params->filter_user_suspended : 0;
		$params->filter_user_guest = (isset($params->filter_user_guest)) ? $params->filter_user_guest : 0;
		$params->filter_course_visible = (isset($params->filter_course_visible)) ? $params->filter_course_visible : 0;
		$params->filter_enrolmethod_status = (isset($params->filter_enrolmethod_status)) ? $params->filter_enrolmethod_status : 0;
		$params->filter_enrol_status = (isset($params->filter_enrol_status)) ? $params->filter_enrol_status : 0;
		$params->filter_enrolled_users = (isset($params->filter_enrolled_users)) ? $params->filter_enrolled_users : 0;
		$params->filter_module_visible = (isset($params->filter_module_visible)) ? $params->filter_module_visible : 0;
		$params->teacher_roles = (isset($params->teacher_roles) and $params->teacher_roles) ? $params->teacher_roles : 3;
		$params->learner_roles = (isset($params->learner_roles) and $params->learner_roles) ? $params->learner_roles : 5;
		$params->notification_enrol = isset($params->notification_enrol) ? clean_raw($params->notification_enrol, false) : '';
		$params->notification_auth = isset($params->notification_auth) ? clean_raw($params->notification_auth, false) : '';
		$params->notification_email = isset($params->notification_email) ? clean_raw($params->notification_email, false) : '';
		$params->notification_subject = isset($params->notification_subject) ? clean_raw($params->notification_subject, false) : '';
		$params->notification_message = isset($params->notification_message) ? clean_raw($params->notification_message, false) : '';

		if($params->debug){
			$CFG->debug = (E_ALL | E_STRICT);
			$CFG->debugdisplay = true;
		}
		$obj->teacher_roles = $params->teacher_roles;
		$obj->learner_roles = $params->learner_roles;

		$function = (isset($params->function)) ? $params->function : false;
		if($function){
			$data = $obj->{$function}($params);
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

	function get_filter_sql($params, $columns)
	{
		global $DB;

		$filter = "";
		if($params->filter and !empty($columns)){
			$sql_arr = array(); $filter_columns = explode(",", $params->filter_columns);
			foreach($columns as $i => $column){
				if(in_array($i, $filter_columns)){
					$key = clean_param($column, PARAM_ALPHANUMEXT);
					$sql_arr[] = $DB->sql_like($column, ":$key", false, false);
					$this->params[$key] = "%$params->filter%";
				}
			}
			$filter .= ($sql_arr) ? implode(" OR ", $sql_arr) : "";
		}
		if($params->filter_profile){
			$params->custom3 = clean_param($params->custom3, PARAM_SEQUENCE);
			if($params->custom3 and !empty($params->columns)){
				$cols = explode(",", $params->columns);
				$fields = $DB->get_records_sql("SELECT id, fieldid, data FROM {user_info_data} WHERE id IN ($params->custom3)");
				$fields_filter = array();
				foreach($fields as $i => $field){
					if(in_array($field->fieldid, $cols)){
						$key = "field$field->fieldid";
						$unickey = "field{$field->fieldid}_{$i}";
						$fields_filter[] = $DB->sql_like($key, ":$unickey", false, false);
						$this->params[$unickey] = "%$field->data%";
					}
				}
				$filter = ($fields_filter and $filter) ? "($filter) AND " : $filter;
				$filter .= ($fields_filter) ? " (" . implode(" OR ", $fields_filter) .") " : "";
			}
		}
		return ($filter) ? " HAVING " . $filter : "";
	}

	function get_filterdate_sql($params, $column)
	{
		if($params->timestart and $params->timefinish){
			return " AND $column BETWEEN $params->timestart AND $params->timefinish ";
		}
		return "";
	}
	function get_filter_user_sql($params, $prefix)
	{
		$filter = ($params->filter_user_deleted) ? "" : " AND {$prefix}deleted = 0";
		$filter .= ($params->filter_user_suspended) ? "" : " AND {$prefix}suspended = 0";
		$filter .= ($params->filter_user_guest) ? "" : " AND {$prefix}username != 'guest'";
		return $filter;
	}
	function get_filter_course_sql($params, $prefix)
	{
		return ($params->filter_course_visible) ? "" : " AND {$prefix}visible = 1";
	}
	function get_filter_enrol_sql($params, $prefix)
	{
		return ($params->filter_enrol_status) ? "" : " AND {$prefix}status = 0";
	}

	function get_filter_enrolled_users_sql($params, $column)
	{
		return ($params->filter_enrolled_users) ? " AND {$column} IN (SELECT DISTINCT userid FROM {user_enrolments})" : "";
	}
	function get_filter_module_sql($params, $prefix)
	{
		return ($params->filter_module_visible) ? "" : " AND {$prefix}visible = 1";
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
		if($type == "grade"){
			$sql = "avg((qa.sumgrades/q.sumgrades)*100) as $type";
		}elseif($type == "duration"){
			$sql = "sum(qa.timefinish - qa.timestart) $type";
		}else{
			$sql = "count(distinct(qa.id)) $type";
		}

		return "SELECT qa.quiz, $sql
						FROM
							{quiz} q,
							{quiz_attempts} qa,
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
									{grade_items} gi,
									{grade_grades} g
								WHERE
									gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
								GROUP BY gi.iteminstance, gi.itemmodule";
	}
	function getCourseUserGradeSql($grage = 'grade', $round = 0){
		return "SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
				FROM
					{grade_items} gi,
					{grade_grades} g
				WHERE
					gi.itemtype = 'course' AND
					g.itemid = gi.id
				GROUP BY gi.courseid, g.userid";
	}
	function getCourseGradeSql($grage = 'grade', $round = 0)
	{
		return "SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
					FROM
						{grade_items} gi,
						{grade_grades} g
					WHERE
						gi.itemtype = 'course' AND
						g.itemid = gi.id AND g.finalgrade IS NOT NULL
					GROUP BY gi.courseid";
	}
	function getLearnerCoursesSql($courses  = 'courses')
	{
		return "SELECT ue.userid, COUNT(DISTINCT(ue.courseid)) AS $courses
					FROM
						(".$this->getUsersEnrolsSql().") ue
					GROUP BY ue.userid";
	}

	function getCourseLearnersSql($learners  = 'learners', $timestart = 0, $timefinish = 0)
	{
		$sql = ($timestart and $timefinish) ? "ue.timecreated BETWEEN $timestart AND $timefinish" : "1";

		return "SELECT ue.courseid, COUNT(DISTINCT(ue.userid)) AS $learners
					FROM
						(".$this->getUsersEnrolsSql().") ue
					WHERE $sql GROUP BY ue.courseid";
	}
	function getModCompletedSql($completed  = 'completed')
	{
		return "SELECT cm.id, count(DISTINCT(cmc.userid)) AS $completed
					FROM
						{course_modules} cm,
						{course_modules_completion} cmc,
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
		return "SELECT c.course, count(DISTINCT(c.userid)) AS $completed
					FROM
						{course_completions} c,
						(".$this->getUsersEnrolsSql().") ue
					WHERE
						c.timecompleted > 0 AND
						c.course = ue.courseid AND
						c.userid = ue.userid
					GROUP BY c.course";
	}
	function getCourseTimeSql($timespend  = 'timespend', $visits  = 'visits', $filter = '')
	{
		return "SELECT lit.courseid, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
			FROM
				{local_intelliboard_tracking} lit,
				(".$this->getUsersEnrolsSql().") l
			WHERE $filter
				lit.courseid = l.courseid AND
				lit.userid = l.userid
			GROUP BY lit.courseid";
	}

	function getModTimeSql($timespend  = 'timespend', $visits  = 'visits')
	{
		return "SELECT lit.param, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
			FROM
				{local_intelliboard_tracking} lit,
				(".$this->getUsersEnrolsSql().") l
			WHERE
				lit.page = 'module' AND
				lit.courseid = l.courseid AND
				lit.userid = l.userid
			GROUP BY lit.param";
	}
	function getCurseUserTimeSql($timespend  = 'timespend', $visits  = 'visits')
	{
		return "SELECT lit.userid, lit.courseid, sum(lit.timespend) as $timespend, sum(lit.visits) as $visits
					FROM
						{local_intelliboard_tracking} lit
					GROUP BY lit.courseid, lit.userid";
	}

	function getUsersEnrolsSql($roles = array(), $enrols = array())
	{
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

		return "SELECT ue.id, ra.roleid, e.courseid, ue.userid, ue.timecreated, ue.timeend, GROUP_CONCAT( DISTINCT e.enrol) AS enrols
					FROM
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
	}

	function report1($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("name", "u.email", "c.fullname", "enrols", "l.visits", "l.timespend", "grade","ue.timecreated", "ul.timeaccess", "ue.timeend", "cc.timecompleted","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_join_filter = ""; $sql_mode = 0;

		$sql_join = "";
		if(isset($params->custom) and  strrpos($params->custom, ',') !== false){
			$sql_filter .= " AND u.id IN($params->custom)";
			$sql_filter_column = "ue.timecreated";
		}elseif(isset($params->custom) and $params->custom == 2 and !$params->sizemode){
			$sql_filter_column = "l.timepoint";
			$sql_mode = 1;
		}elseif(isset($params->custom) and $params->custom == 1){
			$sql_filter_column = "cc.timecompleted";
		}else{
			$sql_filter_column = "ue.timecreated";
		}

		$sql_filter .= $this->get_filterdate_sql($params, "$sql_filter_column");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");

		if($params->cohortid){
			$sql_join .= " LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}
		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
		}elseif($sql_mode){
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join .= " LEFT JOIN (SELECT t.id,t.userid,t.courseid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id $sql_join_filter GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
		}else{
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join .= " LEFT JOIN (SELECT t.userid,t.courseid, sum(t.timespend) as timespend, sum(t.visits) as visits FROM
								{local_intelliboard_tracking} t GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
		}


		$data = $DB->get_records_sql("SELECT ue.id,
			ue.timecreated as enrolled,
			ue.timeend,
			ul.timeaccess,
			ROUND(((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			u.id as uid,
			u.email,
			u.phone1,
			u.phone2,
			u.institution,
			u.department,
			u.address,
			u.city,
			u.country,
			CONCAT(u.firstname, ' ', u.lastname) as name,
			e.enrol as enrols,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date
			$sql_columns
						FROM {user_enrolments} ue
							LEFT JOIN {enrol} e ON e.id = ue.enrolid
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {user_lastaccess} as ul ON ul.courseid = c.id AND ul.userid = u.id
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                    		LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
							$sql_join
								WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}

	function report2($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("course", "learners", "modules", "completed", "l.visits", "l.timespend", "grade", "c.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		if($params->sizemode){
			$sql_columns = ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns = ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT courseid, sum(timespend) as timespend, sum(visits) as visits FROM {local_intelliboard_tracking} GROUP BY courseid) l ON l.courseid = c.id";
		}

		$data = $DB->get_records_sql("SELECT c.id,
				c.fullname as course,
				c.timecreated as created,
				c.enablecompletion,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT cc.userid) as completed,
				COUNT(DISTINCT ue.userid) as learners,
				cm.modules
				$sql_columns
			FROM {course} as c
				LEFT JOIN {enrol} e ON e.courseid = c.id
				LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
				LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = c.id AND cc.userid = ue.userid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
		        LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
		        LEFT JOIN (SELECT course, COUNT(id) as modules FROM {course_modules} WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
		        $sql_join
			WHERE 1 $sql_filter GROUP BY c.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report3($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("activity", "m.name", "completed", "visits", "timespend", "grade", "cm.added"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND cm.course  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "cm.added");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_join = "";

		$list = clean_param($params->custom, PARAM_SEQUENCE);
		$sql_mods = ($list) ? " AND m.id IN ($list)" : "";

		$sql_filter .= $sql_mods;
		$sql_cm_end = "";
		$sql_cm_if = array();
		$modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(m.name='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		$sql_columns =  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";


		if($params->sizemode){
			$sql_columns .= ",'0' as grade, '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", round((g.finalgrade/g.rawgrademax)*100, 0) AS grade, l.timespend as timespend, l.visits as visits";
			$sql_join .= " LEFT JOIN (SELECT lit.param, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.page = 'module' GROUP BY lit.param) l ON l.param = cm.id";
			$sql_join .= " LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.iteminstance = cm.instance
						LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.finalgrade IS NOT NULL";
		}

		$data = $DB->get_records_sql("SELECT
				cm.id,
				m.name AS module,
				m.name AS moduletype,
				cm.added,
				cm.completion,
				COUNT(DISTINCT cmc.id) AS completed
				$sql_columns
					FROM {course_modules} cm
						LEFT JOIN {modules} m ON m.id = cm.module
						LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1
						$sql_join
							WHERE 1 $sql_filter GROUP BY cm.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report4($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("learner","u.email","registered","courses","cmc.completed_activities","completed_courses","lit.visits","lit.timespend","grade", "u.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} chm ON chm.userid = u.id";
			$sql_filter .= " AND chm.cohortid  IN ($params->cohortid)";
		}

		$sql_raw = true;
		$sql_join_filter = "";
		if(isset($params->custom) and $params->custom == 1){
			$sql_join_filter .= $this->get_filterdate_sql($params, "l.timepoint");
			$sql_raw = false;
		}else{
			$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		}

		if($params->sizemode and $sql_raw){
			$sql_columns .= ",'0' as grade, '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			if($sql_raw){
				$sql_columns .= ", lit.timespend, lit.visits";
				$sql_join .= " LEFT JOIN (SELECT id,userid, sum(timespend) as timespend, sum(visits) as visits FROM
							{local_intelliboard_tracking}
						WHERE courseid > 0 GROUP BY userid) as lit ON lit.userid = u.id";
			}else{
				$sql_columns .= ", lit.timespend, lit.visits";
				$sql_join .= " LEFT JOIN (SELECT t.id,t.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM
							{local_intelliboard_tracking} t,
							{local_intelliboard_logs} l
						WHERE l.trackid = t.id AND t.courseid > 0 $sql_join_filter GROUP BY t.userid) as lit ON lit.userid = u.id";
			}
		}

		$data = $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email,
				u.lastaccess,
				u.timecreated as registered,
				round(AVG((g.finalgrade/g.rawgrademax)*100), 2) as grade,
				count(DISTINCT e.courseid) as courses,
				count(DISTINCT cc.id) as completed_courses,
				cmc.completed_activities
				$sql_columns
				FROM {user} as u
					LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
					LEFT JOIN {enrol} e ON e.id = ue.enrolid
					LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.timecompleted > 0
					LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype = 'course'
					LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL
					LEFT JOIN (SELECT userid, count(id) as completed_activities FROM {course_modules_completion} WHERE completionstate = 1 GROUP BY userid) cmc ON cmc.userid = u.id
					$sql_join
				WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}


	function report5($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("teacher","courses","ff.videos","l1.urls","l0.evideos","l2.assignments","l3.quizes","l4.forums","l5.attendances"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($CFG->version < 2014051200){
			$table = "log";
			$data = $DB->get_records_sql("SELECT u.id,
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
						LEFT JOIN {user} u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {$CFG->prefix}$table l WHERE l.module = 'url' AND l.action = 'add' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {$CFG->prefix}$table l WHERE l.module = 'page' AND l.action = 'add' GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {$CFG->prefix}$table l WHERE l.module = 'assignment' AND l.action = 'add' GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {$CFG->prefix}$table l WHERE l.module = 'quiz' AND l.action = 'add' GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {$CFG->prefix}$table l WHERE l.module = 'forum' AND l.action = 'add' GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {$CFG->prefix}$table l WHERE l.module = 'attendance' AND l.action = 'add' GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE 1 $sql_filter GROUP BY ue.userid $sql_having $sql_order $sql_limit", $this->params);
		}else{
			$table = "logstore_standard_log";
					$data = $DB->get_records_sql("SELECT u.id,
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
						LEFT JOIN {user} u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) files FROM {files} f WHERE filearea = 'content' GROUP BY f.userid) as f1 ON f1.userid = u.id
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'url' AND l.action = 'created' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'page' AND l.action = 'created'GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'assignment' AND l.action = 'created'GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'quiz' AND l.action = 'created'GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'forum' AND l.action = 'created'GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {$CFG->prefix}$table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'attendance' AND l.action = 'created'GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE 1 $sql_filter GROUP BY ue.userid $sql_having $sql_order $sql_limit", $this->params);
		}
		return array("data"=> $data);
	}
	function report6($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("student", "email", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= " AND ch.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT ue.id,
			cri.gradepass,
			u.email,
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
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {course_completion_criteria} as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = c.id AND lit.userid = u.id
							LEFT JOIN (".$this->getCourseGradeSql('average').") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(DISTINCT cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 AND cm.completion = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
							$sql_join
								WHERE 1 $sql_filter GROUP BY ue.userid, ue.courseid $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report7($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("learner","email", "course", "visits", "participations", "assignments", "grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT ue.id, ue.userid,u.email,
					((cmca.cmcnuma / cma.cmnuma)*100 ) as assignments,
					((cmc.cmcnums / cmx.cmnumx)*100 ) as participations,
					((count(lit.id) / cm.cmnums)*100 ) as visits,
					cma.cmnuma as assigns,
					gc.grade,
					c.fullname as course,
					CONCAT( u.firstname, ' ', u.lastname ) AS learner
					$sql_columns
						FROM (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {user} u ON u.id = ue.userid
							LEFT JOIN {course} c ON c.id = ue.courseid
							LEFT JOIN {local_intelliboard_tracking} lit ON lit.courseid = c.id AND lit.page = 'module' AND lit.userid = u.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnums FROM {course_modules} cv WHERE cv.visible = 1 GROUP BY cv.course) as cm ON cm.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnumx FROM {course_modules} cv WHERE cv.visible = 1 and cv.completion = 1 GROUP BY cv.course) as cmx ON cmx.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnuma FROM {course_modules} cv WHERE cv.visible = 1 and cv.module = 1 GROUP BY cv.course) as cma ON cma.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
								WHERE 1 $sql_filter GROUP BY ue.userid, ue.courseid $sql_having $sql_order $sql_limit", $this->params);


		return array("data"=> $data);
	}
	function report8($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("teacher","courses","learners","activelearners","completedlearners","grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT u.id,
					CONCAT(u.firstname, ' ', u.lastname) teacher,
					COUNT(DISTINCT ctx.instanceid) as courses,
					SUM(l.learners) as learners,
					SUM(l1.activelearners) as activelearners,
					SUM(cc.completed) as completedlearners,
					AVG(g.grade) as grade
					$sql_columns
			FROM {user} as u
				LEFT JOIN {role_assignments} AS ra ON ra.userid = u.id
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
				LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as learners FROM {role_assignments} ra, {context} ctx WHERE ra.roleid IN ($this->learner_roles) AND ctx.id = ra.contextid AND ctx.contextlevel = 50 GROUP BY ctx.instanceid) AS l ON l.instanceid = ctx.instanceid
				LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as activelearners FROM {role_assignments} ra, {user} u, {context} ctx WHERE ra.roleid IN ($this->learner_roles) AND ctx.id = ra.contextid AND ctx.contextlevel = 50 AND u.id = ra.userid AND u.lastaccess BETWEEN ". strtotime('-30 days')." AND ".time()." AND u.deleted = 0 AND u.suspended = 0 GROUP BY ctx.instanceid) AS l1 ON l1.instanceid = ctx.instanceid
				LEFT JOIN (SELECT course, count(id) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) cc ON cc.course = ctx.instanceid
				LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) g ON g.courseid = ctx.instanceid
				WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter
				GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data"            => $data);
	}
	function report9($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("q.name", "c.fullname", "q.questions", "q.timeopen", "qa.attempts", "qs.duration", "qg.grade", "q.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND q.course  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		if($CFG->version < 2014051200){
			$data = $DB->get_records_sql("SELECT q.id,
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
			FROM {quiz} q
				LEFT JOIN {course} c ON c.id = q.course
				LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
			WHERE 1 $sql_filter GROUP BY q.id $sql_having $sql_order $sql_limit", $this->params);
			foreach($data as &$item){
				$item->questions = count(array_diff(explode(',', $item->questions), array(0)));
			}
		}else{
			$data = $DB->get_records_sql("SELECT q.id,
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
			FROM {quiz} q
				LEFT JOIN {course} c ON c.id = q.course
				LEFT JOIN (SELECT quizid, count(*) questions FROM {quiz_slots} GROUP BY quizid) ql ON ql.quizid = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
				LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
			WHERE 1 $sql_filter GROUP BY q.id $sql_having $sql_order $sql_limit", $this->params);
		}

		return array( "data" => $data);
	}
	function report10($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("q.name","learner", "u.email", "c.fullname", "qa.state", "qa.timestart", "qa.timefinish", "duration", "grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND q.course  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= " AND ch.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT qa.id,
				q.name, u.email,
				q.course,
				c.fullname,
				qa.timestart,
				qa.timefinish,
				qa.state,
				(qa.timefinish - qa.timestart) as duration,
				(qa.sumgrades/q.sumgrades*100) as grade,
				CONCAT(u.firstname, ' ', u.lastname) learner
				$sql_columns
				FROM {quiz_attempts} qa
					LEFT JOIN {quiz} q ON q.id = qa.quiz
					LEFT JOIN {user} u ON u.id = qa.userid
					LEFT JOIN {course} c ON c.id = q.course
					LEFT JOIN {context} ctx ON ctx.instanceid = c.id
					LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = u.id
					$sql_join
				WHERE ra.roleid IN ($this->learner_roles) $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report11($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("learner", "course", "u.email", "enrolled", "complete", "grade", "complete"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "ue.courseid", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT ue.id,
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
						FROM {user_enrolments} ue
							LEFT JOIN {enrol} e ON e.id = ue.enrolid
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = u.id
							LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype = 'course'
							LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
							$sql_join
								WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array("data"=> $data);
	}

	function report12($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "e.learners", "v.visits", "v.timespend", "gc.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT c.id,
					c.fullname,
					e.learners,
					gc.grade,
					v.visits,
					v.timespend
						FROM {course} as c
							LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id
							LEFT JOIN (".$this->getCourseLearnersSql().") e ON e.courseid = c.id
							LEFT JOIN (".$this->getCourseTimeSql().") v ON v.courseid = c.id
								WHERE c.category > 0 $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array( "data"            => $data);
	}


	function report13($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("name", "visits", "timespend", "courses", "learners"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT u.id,
					CONCAT(u.firstname, ' ', u.lastname) name,
					count(ue.courseid) as courses,
					sum(l.learners) as learners,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
					$sql_columns
				FROM
					(".$this->getUsersEnrolsSql(explode(",", $this->teacher_roles)).") as ue
					LEFT JOIN {user} as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseLearnersSql().") l ON l.courseid = ue.courseid
					LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = ue.courseid AND lit.userid = u.id
				WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}


	function report14($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("name", "u.email", "visits", "timespend", "courses", "grade", "grade", "u.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filterdate_sql($params, "u.lastaccess");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT u.id, u.lastaccess,
					CONCAT(u.firstname, ' ', u.lastname) name, u.email,
					count(ue.courseid) as courses,
					avg(l.grade) as grade,
					sum(lit.timespend) as timespend,
					sum(lit.visits) as visits
					$sql_columns
				FROM
					(".$this->getUsersEnrolsSql().") as ue
					LEFT JOIN {user} as u ON u.id = ue.userid
					LEFT JOIN (".$this->getCourseUserGradeSql().") l ON l.courseid = ue.courseid AND l.userid = u.id
					LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = ue.courseid AND lit.userid = u.id
				WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report15($params)
	{
		global $CFG, $DB;
		$columns = array_merge(array("enrol", "courses", "users"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT e.id,
						e.enrol as enrol,
						count(DISTINCT e.courseid) as courses,
						count(ue.userid) as users
							FROM {enrol} e
							LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
							WHERE 1 $sql_filter GROUP BY e.enrol $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}

	function report16($params)
	{
		global $CFG, $DB;
		$columns = array_merge(array("c.fullname", "teacher", "total", "v.visits", "v.timespend", "p.posts", "d.discussions"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT c.id,
					c.fullname,
					v.visits,
					v.timespend,
					d.discussions,
					p.posts,
					COUNT(*) AS total,
					(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					  FROM {role_assignments} AS ra
					  JOIN {user} as u ON ra.userid = u.id
					  JOIN {context} AS ctx ON ctx.id = ra.contextid
					  WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher
						FROM {course} c
							LEFT JOIN {forum} f ON f.course = c.id
							LEFT JOIN (SELECT lit.courseid, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='forum' GROUP BY lit.courseid) v ON v.courseid = c.id
							LEFT JOIN (SELECT course, count(*) discussions FROM {forum_discussions} group by course) d ON d.course = c.id
							LEFT JOIN (SELECT fd.course, count(*) posts FROM {forum_discussions} fd, {forum_posts} fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
							WHERE 1 $sql_filter GROUP BY f.course $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}
	function report17($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "f.name ", "f.type ", "Discussions", "UniqueUsersDiscussions", "Posts", "UniqueUsersPosts", "Students", "Teachers", "UserCount", "StudentDissUsage", "StudentPostUsage"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT f.id as forum, c.id, c.fullname,f.name, f.type
						,(SELECT COUNT(id) FROM {forum_discussions} AS fd WHERE f.id = fd.forum) AS Discussions
						,(SELECT COUNT(DISTINCT fd.userid) FROM {forum_discussions} AS fd WHERE fd.forum = f.id) AS UniqueUsersDiscussions
						,(SELECT COUNT(fp.id) FROM {forum_discussions} fd JOIN {forum_posts} AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS Posts
						,(SELECT COUNT(DISTINCT fp.userid) FROM {forum_discussions} fd JOIN {forum_posts} AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS UniqueUsersPosts
						,(SELECT COUNT( ra.userid ) AS Students
						FROM {role_assignments} AS ra
						JOIN {context} AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid  IN ($this->learner_roles)
						AND ctx.instanceid = c.id
						) AS StudentsCount
						,(SELECT COUNT( ra.userid ) AS Teachers
						FROM {role_assignments} AS ra
						JOIN {context} AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid IN ($this->teacher_roles)
						AND ctx.instanceid = c.id
						) AS teacherscount
						,(SELECT COUNT( ra.userid ) AS Users
						FROM {role_assignments} AS ra
						JOIN {context} AS ctx ON ra.contextid = ctx.id
						WHERE  ctx.instanceid = c.id
						) AS UserCount
						, (SELECT (UniqueUsersDiscussions / StudentsCount )) AS StudentDissUsage
						, (SELECT (UniqueUsersPosts /StudentsCount)) AS StudentPostUsage
						FROM {forum} AS f
						JOIN {course} as c ON f.course = c.id
						WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}


	function report18($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("f.name", "user","course", "discussions", "posts"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT fd.id,
					c.fullname as course,
					CONCAT(u.firstname,' ',u.lastname) as user,
					f.name,
					count(distinct fp.id) as posts,
					count(distinct fd.id) as discussions
					FROM
						{forum_discussions} fd
						LEFT JOIN {user} u ON u.id = fd.userid
						LEFT JOIN {course} c ON c.id = fd.course
						LEFT JOIN {forum} f ON f.id = fd.forum
						LEFT JOIN {forum_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
					WHERE 1 $sql_filter GROUP BY u.id, f.id  $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}

	function report19($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "teacher", "scorms"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT c.id,
			c.fullname, count(s.id) as scorms,
			(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {role_assignments} AS ra
									  JOIN {user} as u ON ra.userid = u.id
									  JOIN {context} AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{course} c
										LEFT JOIN {scorm} s ON s.course = c.id
										WHERE c.category > 0 $sql_filter GROUP BY c.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report20($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("s.name", "c.fullname", "sl.visits", "sm.duration", "s.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "s.timemodified");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT s.id,
					c.fullname,
					s.name,
					s.timemodified,
					count(sst.id) as attempts,
					sl.visits,
					sm.duration
						FROM {scorm} s
						LEFT JOIN {scorm_scoes_track} sst ON sst.scormid = s.id AND sst.element = 'x.start.time'
						LEFT JOIN {course} c ON c.id = s.course
						LEFT JOIN (SELECT cm.instance, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='scorm' GROUP BY cm.instance) sl ON sl.instance = s.id
						LEFT JOIN (SELECT scormid, SEC_TO_TIME(SUM(TIME_TO_SEC(value))) AS duration FROM {scorm_scoes_track} where element = 'cmi.core.total_time' GROUP BY scormid) AS sm ON sm.scormid =s.id
						WHERE 1 $sql_filter GROUP BY s.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report21($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "u.email", "sc.name", "c.fullname", "attempts", "sm.duration","sv.starttime","cmc.timemodified", "score"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= " AND ch.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT u.id+st.scormid+st.timemodified as id,
			CONCAT(u.firstname,' ',u.lastname) as user, u.email,
			st.userid,
			st.scormid,
			sc.name,
			c.fullname,
			count(DISTINCT(st.attempt)) as attempts,
			cmc.completionstate,
			cmc.timemodified as completiondate,
			sv.starttime,
			sm.duration,
			sm.timemodified as lastaccess,
			round(sg.score, 0) as score
			$sql_columns
					FROM {scorm_scoes_track} AS st
					LEFT JOIN {user} as u ON st.userid=u.id
					LEFT JOIN {scorm} AS sc ON sc.id=st.scormid
					LEFT JOIN {course} c ON c.id = sc.course
					LEFT JOIN {modules} m ON m.name = 'scorm'
					LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = sc.id
					LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
					LEFT JOIN (SELECT userid, timemodified, scormid, SEC_TO_TIME( SUM( TIME_TO_SEC( value ) ) ) AS duration FROM {scorm_scoes_track} where element = 'cmi.core.total_time' GROUP BY userid, scormid) AS sm ON sm.scormid =st.scormid and sm.userid=st.userid
					LEFT JOIN (SELECT userid, MIN(value) as starttime, scormid FROM {scorm_scoes_track} where element = 'x.start.time' GROUP BY userid, scormid) AS sv ON sv.scormid =st.scormid and sv.userid=st.userid
					LEFT JOIN (SELECT gi.iteminstance, (gg.finalgrade/gg.rawgrademax)*100 AS score, gg.userid FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemmodule='scorm' and gg.itemid=gi.id  GROUP BY gi.iteminstance, gg.userid) AS sg ON sg.iteminstance =st.scormid and sg.userid=st.userid
					$sql_join
					WHERE 1 $sql_filter
					GROUP BY st.userid, st.scormid $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report22($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "teacher", "quizzes", "qa.attempts", "qv.visits", "qv.timespend", "qg.grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT c.id,
				c.fullname,
				count(q.id) as quizzes,
				sum(qs.duration) as duration,
				sum(qa.attempts) as attempts,
				avg(qg.grade) as grade,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {role_assignments} AS ra
									  JOIN {user} as u ON ra.userid = u.id
									  JOIN {context} AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles)  AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
						{quiz} q
						LEFT JOIN {course} c ON c.id = q.course
						LEFT JOIN (".$this->getQuizAttemptsSql("duration").") qs ON qs.quiz = q.id
						LEFT JOIN (".$this->getQuizAttemptsSql().") qa ON qa.quiz = q.id
						LEFT JOIN (".$this->getQuizAttemptsSql("grade").") qg ON qg.quiz = q.id
						WHERE c.category > 0 $sql_filter
						GROUP BY c.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report23($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "resources", "teacher"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT c.id,
			c.fullname,
			count(r.id) as resources,
			(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {role_assignments} AS ra
									  JOIN {user} as u ON ra.userid = u.id
									  JOIN {context} AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles)  AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
										{course} c
										LEFT JOIN {resource} r ON r.course = c.id
										WHERE 1 $sql_filter GROUP BY c.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report24($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("r.name", "c.fullname", "sl.visits", "sl.timespend", "r.timemodified"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "r.timemodified");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT r.id,
				c.fullname,
				r.name,
				r.timemodified,
				sl.visits,
				sl.timespend FROM {resource} r
										LEFT JOIN {course} c ON c.id = r.course
										LEFT JOIN (SELECT cm.instance, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='resource' GROUP BY cm.instance) sl ON sl.instance = r.id
										WHERE 1 $sql_filter GROUP BY r.id  $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}
	function report25($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("component", "files", "filesize"), $this->get_filter_columns($params));
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT id,
				component,
				count(id) as files,
				sum(filesize) as filesize
				FROM {files} WHERE filesize > 0 GROUP BY component $sql_having $sql_order $sql_limit", $this->params);


		return array( "data"  => $data);
	}

	function report26($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("course", "user", "enrolled", "cc.timecompleted", "score", "completed", "l.visits", "l.timespend"), $this->get_filter_columns($params));

		$sql_filter = ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT ue.id,
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
						FROM {user_enrolments} as ue
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {enrol} as e ON e.id = ue.enrolid
                            LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid and cc.userid = ue.userid
							LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS score FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (SELECT lit.userid, lit.courseid, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) as l ON l.courseid = c.id AND l.userid = u.id
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
						WHERE ue.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." ) $sql_filter GROUP BY ue.userid, e.courseid  $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}
	function report27($params)
	{
		global $CFG, $DB;

		$sql_filter = ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);

		if($CFG->version < 2012120301){
			$columns = array_merge(array("course", "username", "email", "q.name", "qa.id", "qa.id", "qa.id", "qa.id", "grade"), $this->get_filter_columns($params));

			$data = $DB->get_records_sql("SELECT qa.id,
				qa.*,
				q.name,
				c.fullname as course,
				CONCAT(u.firstname, ' ', u.lastname) username,
				u.email,
				(qa.sumgrades/q.sumgrades*100) as grade
				$sql_columns
					FROM {quiz_attempts} qa
						LEFT JOIN {quiz} q ON q.id = qa.quiz
						LEFT JOIN {user} u ON u.id = qa.userid
						LEFT JOIN {course} as c ON c.id = q.course
					WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." ) $sql_filter GROUP BY qa.id $sql_having $sql_order $sql_limit", $this->params);
		}else{
			$columns = array_merge(array("course", "username", "email", "q.name", "qa.state", "qa.timestart", "qa.timefinish", "qa.timefinish", "grade"), $this->get_filter_columns($params));

			$data = $DB->get_records_sql("SELECT qa.id,
				q.name,
				c.fullname as course,
				qa.timestart,
				qa.timefinish,
				qa.state,
				CONCAT(u.firstname, ' ', u.lastname) username,
				u.email,
				(qa.sumgrades/q.sumgrades*100) as grade
				$sql_columns
					FROM {quiz_attempts} qa
						LEFT JOIN {quiz} q ON q.id = qa.quiz
						LEFT JOIN {user} u ON u.id = qa.userid
						LEFT JOIN {course} as c ON c.id = q.course
					WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = ".intval($params->userid).") and userid != ".intval($params->userid)." ) $sql_filter GROUP BY qa.id $sql_having $sql_order $sql_limit", $this->params);
		}

		return array( "data" => $data);
	}

	function report28($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("gi.itemname", "learner", "u.email", "graduated", "grade", "completionstate", "timespend", "visits"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND cm.course  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "gg.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$list = clean_param($params->custom, PARAM_SEQUENCE);
		if($list){
			$sql_filter .= " AND m.id IN ($list)";
		}
		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", l.timespend as timespend, l.visits as visits";
			$sql_join = " LEFT JOIN (SELECT userid, param, sum(timespend) as timespend, sum(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY userid, param) l ON l.param = cm.id AND l.userid = u.id";
		}

		$data = $DB->get_records_sql("SELECT gg.id,
					gi.itemname,
					gg.userid,
					u.email,
					CONCAT(u.firstname, ' ', u.lastname) as learner,
					gg.timemodified as graduated,
					(gg.finalgrade/gg.rawgrademax)*100 as grade,
					cm.completion,
					cmc.completionstate
					$sql_columns
						FROM {grade_grades} gg
							LEFT JOIN {grade_items} gi ON gi.id=gg.itemid
							LEFT JOIN {user} as u ON u.id = gg.userid
							LEFT JOIN {modules} m ON m.name = gi.itemmodule
							LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {course} as c ON c.id=cm.course
							LEFT JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
							$sql_join
								WHERE gi.itemtype = 'mod' $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}

	function report29($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "course", "g.grade"), $this->get_filter_columns($params));
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);
			foreach($courses as $c){
				$data = explode("_", $c);
				$data[0] = clean_param($data[0], PARAM_INT);
				$data[1] = clean_param($data[1], PARAM_INT);
				$sql_courses[] = "(e.courseid = ".$data[1]." AND g.grade < ".$data[0].")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "e.courseid > 0";
		}

		$data = $DB->get_records_sql("SELECT ue.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				c.fullname as course,
				g.grade,
				gm.graded,
				cm.modules $sql_columns
						FROM {user_enrolments} as ue
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {enrol} as e ON e.id = ue.enrolid
                            LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN (SELECT gi.courseid, gg.userid, (gg.finalgrade/gg.rawgrademax)*100 AS grade FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemtype = 'course' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as g ON g.courseid = c.id AND g.userid = u.id
							LEFT JOIN (SELECT gi.courseid, gg.userid, count(gg.id) graded FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemtype = 'mod' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as gm ON gm.courseid = c.id AND gm.userid = u.id
							LEFT JOIN (SELECT courseid, count(id) as modules FROM {grade_items} WHERE itemtype = 'mod' GROUP BY courseid) as cm ON cm.courseid = c.id
						WHERE (cc.timecompleted IS NULL OR cc.timecompleted = 0) AND gm.graded >= cm.modules AND $sql_courses $sql_filter GROUP BY ue.userid, e.courseid  $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}

	function report30($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "course", "enrolled", "cc.timecompleted"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);

			foreach($courses as $c){
				$data = explode("_", $c);
				$data[0] = clean_param($data[0], PARAM_INT);
				$data[1] = clean_param($data[1], PARAM_INT);
				$sql_courses[] = "(cc.course = ".$data[1]." AND cc.timecompleted > ".($data[0]/1000).")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "cc.course > 0";
		}

		$data = $DB->get_records_sql("SELECT cc.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, cc.timecompleted
					FROM
						{course_completions} cc,
						{course} c,
						{user} u
					WHERE u.id= cc.userid and c.id = cc.course and $sql_courses");


		return array( "data" => $data);
	}

	function report31($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "course", "lit.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->filter){
			$sql_courses = array();
			$courses = explode(",", $params->filter);
			foreach($courses as $c){
				$data = explode("_", $c);
				$data[0] = clean_param($data[0], PARAM_INT);
				$data[1] = clean_param($data[1], PARAM_INT);
				$sql_courses[] = "(lit.courseid = ".$data[1]." AND lit.lastaccess < ".(time()-($data[0]*86400)).")";
			}
			$sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
		}else{
			$sql_courses = "lit.courseid > 0";
		}

		$data = $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, lit.lastaccess
					FROM {user} u
						LEFT JOIN {local_intelliboard_tracking} lit on lit.userid = u.id AND lit.lastaccess = (
							SELECT MAX(lastaccess)
								FROM {local_intelliboard_tracking}
								WHERE userid = lit.userid and courseid = lit.courseid
							)
						LEFT JOIN {course} c ON c.id = lit.courseid
					WHERE $sql_courses GROUP BY lit.userid, lit.courseid");


		return array( "data" => $data);
	}
	function report32($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "u.email", "courses","lit1.timesite","lit2.timecourses","lit3.timeactivities","u.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join_filter = "";
		if(isset($params->custom) and $params->custom == 1){
			$sql_join_filter .= $this->get_filterdate_sql($params, "l.timepoint");
		}else{
			$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		}
		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				count(DISTINCT (ue.courseid)) as courses,
				lit1.timesite,
				lit2.timecourses,
				lit3.timeactivities
				$sql_columns
						FROM (".$this->getUsersEnrolsSql().") ue
							LEFT JOIN {user} u ON u.id = ue.userid
							LEFT JOIN (SELECT t.userid, sum(l.timespend) as timesite FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id $sql_join_filter GROUP BY t.userid) as lit1 ON lit1.userid = u.id
							LEFT JOIN (SELECT t.userid, sum(l.timespend) as timecourses FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id AND t.courseid > 0 $sql_join_filter GROUP BY t.userid) as lit2 ON lit2.userid = u.id
							LEFT JOIN (SELECT t.userid, sum(l.timespend) as timeactivities FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id AND t.page = 'module' $sql_join_filter GROUP BY t.userid) as lit3 ON lit3.userid = u.id
							$sql_join
							WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data"  => $data);
	}


	function get_scormattempts($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT sst.attempt,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'x.start.time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as starttime,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.score.raw' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as score,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.lesson_status' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as status,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as totaltime,
				(SELECT s.timemodified FROM {scorm_scoes_track} s WHERE element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as timemodified
			FROM {scorm_scoes_track} sst
			WHERE sst.userid = " . intval($params->userid) . "  and sst.scormid = " . intval($params->filter) . "
			GROUP BY sst.attempt");
	}

	function report33($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user", "course", "ue.enrols", "l.visits", "l.timespend", "gc.grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));

		$sql_join = "";

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT ue.id,
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
						FROM {groups} as gr, {groups_members} as grm, (".$this->getUsersEnrolsSql().") as ue
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = u.id
							$sql_join
								WHERE gr.courseid = ue.courseid and grm.groupid = gr.id and grm.userid = ue.userid $sql_filter GROUP BY ue.courseid, ue.userid $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}

	function report34($params)
	{
		global $CFG, $DB;

		$columns = array("c.fullname", "ue.enrols", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT ue.id,
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
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = ue.userid
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid=$params->userid GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
								WHERE ue.userid = $params->userid  $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}

	function report35($params)
	{
		global $CFG, $DB;

		$columns = array("gi.itemname", "graduated", "grade", "completionstate", "timespend", "visits");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = ($params->courseid) ? " AND gi.courseid  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT gi.id,
					gi.itemname,
					gi.courseid,
					cm.completionexpected,
					gg.userid,
					gg.timemodified as graduated,
					(gg.finalgrade/gg.rawgrademax)*100 as grade,
					cm.completion,
					cmc.completionstate,
					l.timespend,
					l.visits
						FROM {grade_items} gi
							LEFT JOIN {user} as u ON u.id = $params->userid
							LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
							LEFT JOIN {modules} m ON m.name = gi.itemmodule
							LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {course} as c ON c.id=cm.course
							LEFT JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
							LEFT JOIN (SELECT lit.userid, lit.param, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.page = 'module' GROUP BY lit.userid, lit.param) l ON l.param = cm.id AND l.userid = u.id
								WHERE gi.itemtype = 'mod' $sql_filter $sql_having $sql_order $sql_limit", $this->params);

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

		return array( "data" => $data);
	}
	function report36($params)
	{
		global $CFG, $DB;

		$columns = array("c.fullname", "l.page", "l.param", "l.visits", "l.timespend", "l.firstaccess", "l.lastaccess", "l.useragent", "l.useros", "l.userlang");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT l.id,
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
						FROM {local_intelliboard_tracking} l
						LEFT JOIN {course} as c ON c.id = l.courseid
						WHERE l.userid = $params->userid $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}

	function report37($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("learner","u.email","u.id"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_filter_user_sql($params, "u.");

		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		if($params->custom){
			$sql_filter = " AND u.id IN($params->custom)";
		}

		$data = $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email
				$sql_columns
			FROM {role_assignments} ra, {user} as u
			WHERE ra.roleid IN ($this->learner_roles) AND u.id = ra.userid $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}
	function report38($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.startdate", "ccc.timeend", "course", "learner", "u.email", "enrols", "enrolstart", "enrolend", "complete", "complete"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT ue.id,
			IF(ue.timestart = 0, ue.timecreated, ue.timecreated) as enrolstart,
			ue.timeend as enrolend,
			ccc.timeend,
			c.startdate,
			c.enablecompletion,
			cc.timecompleted as complete,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			u.email,
			ue.userid,
			e.courseid,
			GROUP_CONCAT( DISTINCT e.enrol) AS enrols,
			c.fullname as course
			$sql_columns
						FROM
							{user_enrolments} ue
							LEFT JOIN {enrol} e ON e.id = ue.enrolid
							LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
							LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN {course_completion_criteria} as ccc ON ccc.course = e.courseid AND ccc.criteriatype = 2
								WHERE ra.roleid IN ($this->learner_roles) $sql_filter GROUP BY ue.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report39($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("user","u.email","u.timecreated","u.firstaccess","u.lastaccess","lit1.timespend_site","lit2.timespend_courses","lit3.timespend_activities","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				u.timecreated,
				u.firstaccess,
				u.lastaccess,
				lit1.timespend_site,
				lit2.timespend_courses,
				lit3.timespend_activities
				$sql_columns
						FROM {user} as u
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_site FROM {local_intelliboard_tracking} GROUP BY userid) as lit1 ON lit1.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 GROUP BY userid) as lit2 ON lit2.userid = u.id
							LEFT JOIN (SELECT userid, sum(timespend) as timespend_activities FROM {local_intelliboard_tracking} WHERE page='module' GROUP BY userid) as lit3 ON lit3.userid = u.id
							$sql_join
							WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report40($params)
	{
		global $CFG, $DB;

		$columns = array("course", "learner", "email", "ue.enrols", "ue.timecreated", "la.lastaccess", "gc.grade");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= " AND ch.cohortid  IN ($params->cohortid)";
		}


		$data = $DB->get_records_sql("SELECT ue.id,
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
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN {local_intelliboard_tracking} la ON la.courseid = c.id AND la.userid = ue.userid AND la.page = 'course'
							LEFT JOIN (SELECT t.id,t.userid,t.courseid FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id AND t.page = 'course' AND
								l.timepoint BETWEEN $params->timestart AND $params->timefinish GROUP BY t.courseid, t.userid) as l ON l.courseid = ue.courseid AND l.userid = ue.userid
							$sql_join

							WHERE l.id IS NULL $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report41($params)
	{
		global $CFG, $DB;

		$columns = array("course", "learner","email", "certificate", "ci.timecreated", "ci.code");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ci.timecreated");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT ci.id,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			u.email,
			ce.name as certificate,
			ci.timecreated,
			ci.code,
			ci.userid,
			c.id as cid,
			c.fullname as course
			$sql_columns
						FROM {certificate_issues} as ci
							LEFT JOIN {certificate} as ce ON ce.id = ci.certificateid
							LEFT JOIN {user} as u ON u.id = ci.userid
							LEFT JOIN {course} as c ON c.id = ce.course
							WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}
	function report43($params)
	{
		global $CFG, $DB;

		$columns = array("user", "completed_courses", "grade", "lit.visits", "lit.timespend", "u.timecreated");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", lit.timespend, lit.visits";
			$sql_join = " LEFT JOIN (SELECT l.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) lit ON lit.userid = u.id";
		}

		$data = $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT e.courseid) as courses,
				COUNT(DISTINCT cc.course) as completed_courses
				$sql_columns
				FROM
					{user} u
						LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid
						LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
		        		LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
		        		$sql_join
					WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);
		return array("data" => $data);
	}
	function report44($params)
	{
		global $CFG, $DB;

		$columns = array("c.fullname", "users", "completed");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);


		$data = $DB->get_records_sql("SELECT c.id,
				c.fullname,
				COUNT(DISTINCT ue.userid) users,
				COUNT(DISTINCT cc.userid) as completed
						FROM {user_enrolments} ue
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.timecompleted > 0
						WHERE 1 $sql_filter GROUP BY e.courseid $sql_having $sql_order $sql_limit", $this->params);
		return array("data" => $data);
	}
	function report45($params)
	{
		global $CFG, $DB;

		$columns = array("user", "u.email", "all_att", "lit.timespend", "highest_grade", "lowest_grade", "cmc.timemodified");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		if($params->custom == 1)
			$sql_having = (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)=0':str_replace(' HAVING ',' HAVING (',$sql_having). ') AND COUNT(DISTINCT qa.id)=0';
		elseif($params->custom == 2)
			$sql_having = (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)':str_replace(' HAVING ',' HAVING (',$sql_having).') AND COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)';

 		$data = $DB->get_records_sql("SELECT u.id,
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
				WHERE q.id=$params->courseid GROUP BY u.id $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}
	function report42($params)
	{
		global $CFG, $DB;


		$columns = array_merge(array("student","u.email", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= ($params->courseid) ? " AND ue.courseid  IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$sql_grades = '';
		$grades = array();
		if(!empty($params->custom)){
			$book = explode(',',$params->custom);
			foreach($book as $item){
				$grade = explode('-',$item);
				$grade0 = isset($grade[0]) ? clean_param($grade[0], PARAM_INT) : false;
				$grade1 = isset($grade[1]) ? clean_param($grade[1], PARAM_INT) : false;

				if($grade0 !== false and $grade1 !== false ){
					$grades[] = "(g.finalgrade/g.rawgrademax)*100 BETWEEN ". $grade0 ." AND ". $grade1;
				}
			}
			if($grades){
				$sql_grades = '('.implode(' OR ',$grades).') AND ';
			}
		}


		$data = $DB->get_records_sql("SELECT ue.id,
			cri.gradepass,
			u.email,
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
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {course_completion_criteria} as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (".$this->getCurseUserTimeSql().") lit ON lit.courseid = c.id AND lit.userid = u.id
							LEFT JOIN (".$this->getCourseGradeSql('average').") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
								WHERE $sql_grades 1 $sql_filter GROUP BY ue.userid, ue.courseid $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
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

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_where = '';
		if($params->courseid){
			$sql_where = "WHERE ue.courseid IN ($params->courseid)";
		}

		$data = $DB->get_records_sql("SELECT ue.id,
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
											$sql_where $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}

	function report58($params)
	{
		global $CFG, $DB;

		$sql_limit = $this->get_limit_sql($params);
		$sql_id = (int) $params->custom;

		$data = $DB->get_records_sql("SELECT gi.id,
					gi.itemname,
					cm.id as cmid,
					cm.completionexpected,
					c.fullname,
					cm.completionexpected
						FROM {grade_items} gi
							LEFT JOIN {course} c ON c.id = gi.courseid
							LEFT JOIN {modules} m ON m.name = gi.itemmodule
							LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $sql_id
								WHERE cm.visible = 1 AND gi.itemtype = 'mod' AND cm.completionexpected > ".time()."  AND (cmc.id IS NULL OR cmc.completionstate=0) ORDER BY cm.completionexpected ASC $sql_limit");

		return array( "data"            => $data);
	}
	function report66($params)
	{
		global $CFG, $DB;

 		$columns = array("user", "u.email", "course", "assignment", "a.duedate", "s.status", "gc.grade", "cc.timecompleted", "ue.timecreated");

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN($params->courseid)" : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");

		$data = $DB->get_records_sql("SELECT @x:=@x+1 as id,
				a.name as assignment,
				a.duedate,
				c.fullname as course,
				s.status,
				s.timemodified AS submitted ,
				u.email,
				CONCAT(u.firstname, ' ', u.lastname) as user
				$sql_columns
				FROM (SELECT @x:= 0) AS x, {assign} a
					LEFT JOIN (SELECT e.courseid, ue.userid FROM {user_enrolments} ue, {enrol} e WHERE e.id=ue.enrolid GROUP BY e.courseid, ue.userid) ue
					ON ue.courseid = a.course
					LEFT JOIN {user} u ON u.id = ue.userid
					LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = u.id
					LEFT JOIN {course} c ON c.id = a.course
					LEFT JOIN {course_modules} cm ON cm.instance = a.id
				WHERE (s.timemodified > a.duedate or s.timemodified IS NULL) $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array( "data" => $data);
	}

	function report72($params)
	{
		global $CFG, $DB;

		require_once($CFG->dirroot.'/mod/scorm/locallib.php');
		require_once($CFG->dirroot.'/mod/scorm/report/reportlib.php');

		$params->custom = (int)$params->custom;

		$data = $DB->get_records_sql("SELECT t.*
				FROM {scorm_scoes_track} t, {scorm} s
				WHERE s.course = $params->courseid AND t.scormid = s.id AND t.userid = $params->userid AND t.attempt = $params->custom");

		$questioncount = get_scorm_question_count(array_values($data)[0]->scormid);
		$data = scorm_format_interactions($data);

		return array(
					"questioncount"   => $questioncount,
					"recordsTotal"    => count($data),
					"recordsFiltered" => count($data),
					"data"            => $data);
	}
	function report73($params)
	{
		global $CFG, $DB;

		$data = $DB->get_records_sql("SELECT b.id, b.name, i.timemodified, i.location, i.progress, u.firstname, u.lastname, c.fullname FROM
					{scorm_ajax_buttons} b
					LEFT JOIN {user} u ON u.id = $params->userid
					LEFT JOIN {course} c ON c.id = $params->courseid
					LEFT JOIN {scorm} s ON s.course = c.id
					LEFT JOIN {scorm_ajax} a ON a.scormid = s.id
					LEFT JOIN {scorm_ajax_info} i ON i.page = b.id AND i.userid = u.id AND i.relid = a.relid
					ORDER BY b.id");

		return array(
					"recordsTotal"    => count($data),
					"recordsFiltered" => count($data),
					"data"            => $data);
	}
	//UMKC Custom Report
	function report75($params)
	{
		global $CFG, $DB;

		$columns = array("mc_course", "mco_name", "mc_name", "mci_userid", "mci_certid", "mu_firstname", "mu_lastname", "issue_date");

		$sql_columns = $this->get_columns($params, "mu.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "mc.course", "courses");
		$sql_filter .= ($params->courseid) ? " AND mc.course IN($params->courseid)" : "";

		$data = $DB->get_records_sql("SELECT DISTINCT mci.id,
				mc.course AS mc_course,
				mco.fullname AS mco_name,
				mc.name AS mc_name,
				mci.userid AS mci_userid,
				mci.certificateid AS mci_certid,
				mu.firstname AS mu_firstname,
				mu.lastname AS mu_lastname,
				DATE_FORMAT(FROM_UNIXTIME(mci.timecreated),'%m-%d-%Y') AS issue_date
				FROM
				{certificate} as mc
					LEFT JOIN {certificate_issues} AS mci
					ON mci.certificateid = mc.id
					LEFT OUTER JOIN {user} AS mu
					ON mci.userid = mu.id
					LEFT OUTER JOIN {course} AS mco
					ON mc.course = mco.id
				WHERE mci.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array( "data"  => $data);
	}

	function report76($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"u.email",
				"feedback",
				"question",
				"answer",
				"feedback_time",
				"course_name", "cc.timecompleted", "grade"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= ($params->courseid) ? " AND mf.course IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "mfc.timemodified");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("
			SELECT
				@x:=@x+1 as id,
				u.firstname,
				u.lastname,
				u.email,
				mfc.timemodified as feedback_time,
				cc.timecompleted,
				mfi.presentation,
                mfi.typ,
				mfi.name as question,
				mfv.value as answer,
				mf.name as feedback,
				c.fullname as course_name,
				round(((g.finalgrade/g.rawgrademax)*100), 0) AS grade
				$sql_columns
			FROM (SELECT @x:= 0) AS x, {feedback} AS mf
			LEFT JOIN {feedback_item} AS mfi ON mfi.feedback = mf.id
			LEFT JOIN {feedback_value} mfv ON mfv.item = mfi.id
			LEFT JOIN {feedback_completed} as mfc ON mfc.id = mfv.completed
			LEFT JOIN {user} as u ON mfc.userid = u.id
			LEFT JOIN {course} c ON c.id = mf.course
			LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
			LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
			LEFT JOIN {course_completions} cc ON cc.userid = u.id
			WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		foreach( $data as $k=>$v ){
			$data[$k] = $this->parseFeedbackAnswer($v);
		}

		return array("data" => $data);
	}
	function report77($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"cm.idnumber",
				"l.intro",
				"cmc.timemodified"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= ($params->courseid) ? " AND l.course IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "a.timeseen");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$data = $DB->get_records_sql("SELECT
				@x:=@x+1 as id,
				l.name,
				l.intro,
				u.firstname,
				u.lastname,
				u.email,
				c.fullname,
				a.timeseen,
				cm.idnumber,
				cc.timemodified as timecompleted,
				COUNT(a.id) as attempts,
				g.grade
				$sql_columns
			FROM (SELECT @x:= 0) AS x, {lesson} l
				LEFT JOIN {modules} m ON m.name = 'lesson'
				LEFT JOIN {course_modules} cm ON cm.instance = l.id AND cm.module = m.id
				LEFT JOIN {course} c ON c.id = l.course
				LEFT JOIN {lesson_attempts} a ON a.lessonid = l.id
				LEFT JOIN {user} u ON u.id = a.userid
				LEFT JOIN {lesson_grades} g ON g.userid = u.id AND g.lessonid = l.id
				LEFT JOIN {course_completions} cc ON cc.coursemoduleid = cm.id AND cmc.userid = u.id AND cmc.completionstate = 1
			WHERE 1 $sql_filter GROUP BY l.id, u.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}

	function report79($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"c.fullname",
				"m.name",
				"activity",
				"l.visits",
				"l.timespend",
				"l.firstaccess",
				"l.lastaccess",
				"l.useragent",
				"l.useros",
				"l.userlang",
				"l.userip"),
		 	$this->get_filter_columns($params)
		 );
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= ($params->courseid) ? " AND l.courseid IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filterdate_sql($params, "l.lastaccess");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");

		$list = clean_param($params->custom, PARAM_SEQUENCE);
		$sql_mods = ($list) ? " AND m.id IN ($list)" : "";

		$sql_filter .= $sql_mods;
		$sql_cm_end = "";
		$sql_cm_if = array();
		$modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(m.name='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		$sql_columns .=  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";


		$data = $DB->get_records_sql("SELECT
			l.id,
			u.firstname,
			u.lastname,
			u.email,
			c.fullname,
			l.param,
			l.visits,
			l.timespend,
			l.firstaccess,
			l.lastaccess,
			l.useragent,
			l.useros,
			l.userlang,
			l.userip,
			m.name as module
			$sql_columns
			FROM {local_intelliboard_tracking} l
				LEFT JOIN {user} u ON u.id = l.userid
				LEFT JOIN {course} c ON c.id = l.courseid
				LEFT JOIN {course_modules} cm ON cm.id = l.param
				LEFT JOIN {modules} m ON m.id = cm.module
			WHERE l.page = 'module' $sql_filter $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report80($params)
	{
		global $CFG, $DB;

		if(!$params->custom3){
			return array("data" => null);
		}

		$columns = array_merge(array(
				"firstname",
				"lastname",
				"page",
				"fullname",
				"l.visits",
				"l.timespend",
				"firstaccess",
				"lastaccess"),
		 	$this->get_filter_columns($params)
		 );

		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$params->custom3 = (int)$params->custom3;

		$item = $DB->get_record_sql("SELECT l.id, u.firstname, u.lastname, c.fullname, u.email, l.page, l.param, l.visits, l.timespend, l.firstaccess, l.lastaccess, '' as name
			FROM {local_intelliboard_tracking} l
				LEFT JOIN {user} u ON u.id = l.userid
				LEFT JOIN {course} c ON c.id = l.courseid
			WHERE l.id = $params->custom3");


		if($item->id and $item->param){
			if($item->page == 'module'){
				$cm = $DB->get_record_sql("SELECT cm.instance, m.name FROM
					{course_modules} cm,
					{modules} m WHERE cm.id = $item->param AND m.id = cm.module");

				$instance = $DB->get_record_sql("SELECT name FROM {$CFG->prefix}{$cm->name} WHERE id = $cm->instance");
				$item->name = $instance->name;
			}
			$data = $DB->get_records_sql("SELECT l.id, l.visits, l.timespend,
				'' as firstaccess,
				l.timepoint as lastaccess,
				'' as firstname,
				'' as lastname,
				'' as email,
				'' as param,
				'' as name,
				'' as fullname
						FROM {local_intelliboard_logs} l
						WHERE l.trackid = $item->id AND l.timepoint BETWEEN $params->timestart AND $params->timefinish $sql_order $sql_limit", $this->params);
		}
		foreach($data as $d){
			$d->firstname = $item->firstname;
			$d->lastname = $item->lastname;
			$d->fullname = $item->fullname;
			$d->name = $item->name;
			break;
		}
		//array_unshift($data, $item);

		return array("data" => $data);
	}
	function report81($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"c.fullname",
				"c.shortname",
				"module",
				"activity",
				"lit.visits",
				"lit.timespend",
				"lit.firstaccess",
				"lit.lastaccess"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		if($params->users){
			$sql_filter .= " AND ra.userid IN ($params->users)";
		}

		$sql_cm_end = "";
		$sql_cm_if = array();
		$modules = $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(m.name='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		$sql_columns .=  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";

		$timefilter = " AND %a BETWEEN $params->timestart AND $params->timefinish ";
		$sql1 = ($params->timestart) ? str_replace('%a', 'lit.lastaccess', $timefilter) : ''; //XXX



		$data = $DB->get_records_sql("
			SELECT DISTINCT @x:=@x+1 as id, u.firstname,u.lastname, u.email, c.fullname, c.shortname, lit.visits, lit.timespend, lit.firstaccess,lit.lastaccess, cm.instance, m.name as module $sql_columns
			FROM (SELECT @x:= 0) AS x,{role_assignments} AS ra
				JOIN {user} as u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} as c ON c.id = ctx.instanceid
				LEFT JOIN {course_modules} cm ON cm.course = c.id
				LEFT JOIN {modules} m ON m.id = cm.module
				LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid = u.id AND lit.param = cm.id and lit.page = 'module' $sql1
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}
	function report82($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"c.fullname",
				"c.shortname",
				"forums",
				"discussions",
				"posts",
				"l.visits",
				"l.timespend"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		if($params->users){
			$sql_filter .= " AND ra.userid IN ($params->users)";
		}

		$timefilter = " AND %a BETWEEN $params->timestart AND $params->timefinish ";
		$sql1 = ($params->timestart) ? str_replace('%a', 'd.timemodified', $timefilter) : '';
		$sql2 = ($params->timestart) ? str_replace('%a', 'p.created', $timefilter) : '';
		$sql3 = ($params->timestart) ? str_replace('%a', 'l.lastaccess', $timefilter) : ''; //XXX


		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'forum' AND cm.id = l.param AND cm.module = m.id $sql3 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
		}

		$data = $DB->get_records_sql("SELECT ra.id,u.firstname,u.lastname, u.email, c.fullname, c.shortname,
			COUNT(distinct f.id) as forums,
			COUNT(distinct d.id) as discussions,
			COUNT(distinct p.id) as posts
		 	$sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} as u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} as c ON c.id = ctx.instanceid
				LEFT JOIN {forum} f ON f.course = c.id
				LEFT JOIN {modules} m ON m.name = 'forum'
				LEFT JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
				LEFT JOIN {forum_discussions} d ON d.course = c.id AND d.forum = f.id $sql1
				LEFT JOIN {forum_posts} p ON p.discussion = d.id AND p.parent > 0 $sql2
				$sql_join
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter GROUP BY ra.id $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report83($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"c.fullname",
				"c.shortname",
				"l.visits",
				"l.timespend",
				"enrolled",
				"completed"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		if($params->users){
			$sql_filter .= " AND ra.userid IN ($params->users)";
		}

		$timefilter = " AND %a BETWEEN $params->timestart AND $params->timefinish ";
		$sql1 = ($params->timestart) ? str_replace('%a', 'ue.timemodified', $timefilter) : '';
		$sql2 = ($params->timestart) ? str_replace('%a', 'cc.timecompleted', $timefilter) : '';
		$sql3 = ($params->timestart) ? str_replace('%a', 'lastaccess', $timefilter) : ''; //XXX

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT userid, courseid, sum(timespend) as timespend, sum(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' or page = 'course' $sql3 GROUP BY userid, courseid ) l ON l.userid = u.id AND l.courseid = c.id";
		}


		$data = $DB->get_records_sql("
			SELECT ra.id,u.firstname,u.lastname, u.email, c.fullname, c.shortname,
			COUNT(distinct ue.userid) as enrolled,
			COUNT(distinct cc.userid) as completed
		 	$sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} as u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} as c ON c.id = ctx.instanceid
				JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
				LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid != u.id $sql1
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted > 0 AND cc.userid != u.id $sql2
				$sql_join
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter GROUP BY ra.id $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report84($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"c.fullname",
				"c.shortname",
				"assignments",
				"completed",
				"submissions",
				"grades",
				"l.visits",
				"l.timespend"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		if($params->users){
			$sql_filter .= " AND ra.userid IN ($params->users)";
		}

		$timefilter = " AND %a BETWEEN $params->timestart AND $params->timefinish ";
		$sql1 = ($params->timestart) ? str_replace('%a', 'cmc.timemodified', $timefilter) : '';
		$sql2 = ($params->timestart) ? str_replace('%a', 's.timemodified', $timefilter) : '';
		$sql3 = ($params->timestart) ? str_replace('%a', 'g.timemodified', $timefilter) : '';
		$sql4 = ($params->timestart) ? str_replace('%a', 'l.lastaccess', $timefilter) : ''; //XXX

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'assign' AND cm.id = l.param AND cm.module = m.id $sql4 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
		}

		$data = $DB->get_records_sql("SELECT ra.id,u.firstname,u.lastname, u.email, c.fullname, c.shortname,
			COUNT(distinct a.id) as assignments,
			COUNT(distinct cmc.coursemoduleid) as completed,
			COUNT(distinct s.assignment) as submissions,
			COUNT(distinct g.assignment) as grades
		 	$sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} as u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} as c ON c.id = ctx.instanceid
				LEFT JOIN {assign} a ON a.course = c.id
				LEFT JOIN {modules} m ON m.name = 'assign'
				LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate = 1 $sql1
				LEFT JOIN {assign_submission} s ON s.status = 'submitted' AND s.assignment = a.id $sql2
				LEFT JOIN {assign_grades} g ON g.assignment = a.id $sql3
				$sql_join
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter GROUP BY ra.id $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report85($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"registered",
				"loggedin",
				"loggedout"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= $this->get_filterdate_sql($params, "l1.timecreated");
		$sql_filter .= ($params->users) ? " AND u.id IN ($params->users) " : "";
		$sql_filter .= $this->get_filter_user_sql($params, "u.");


		$data = $DB->get_records_sql("SELECT
				l1.id,
				u.firstname,
				u.lastname,
				u.timecreated AS registered,
				l1.userid,
				l1.timecreated as loggedin,
				l2.timecreated as loggedout
				$sql_columns
			FROM {logstore_standard_log} l1
			LEFT JOIN {user} u ON u.id = l1.userid
			LEFT JOIN {logstore_standard_log} l2 ON l2.id = (
			    SELECT l3.id
			    FROM {logstore_standard_log} l3
			    WHERE l3.action = 'loggedout' AND l3.id > l1.id AND l3.userid = l1.userid
			    ORDER BY l3.id ASC
			    LIMIT 1)
			WHERE l1.action = 'loggedin' $sql_filter GROUP BY l1.id $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report86($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("c.fullname", "enrolled", "completed"), $this->get_filter_columns($params));
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");

		if($params->users){
			$sql_filter .= " AND ra.userid IN ($params->users)";
		}

		$data = $DB->get_records_sql("
			SELECT c.id, c.fullname,
			COUNT(distinct ue.userid) as enrolled,
			COUNT(distinct cc.userid) as completed
			FROM {role_assignments} AS ra
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} as c ON c.id = ctx.instanceid
				JOIN {enrol} e ON e.courseid = c.id
				LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid != ra.userid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted > 0 AND cc.userid != ra.userid
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql_filter GROUP BY c.id $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report87($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array("fieldname", "users"), $this->get_filter_columns($params));
		$sql_filter = $this->get_teacher_sql($params, "userid", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		if(!$params->custom){
			return array();
		}else{
			$params->custom = (int)$params->custom;
		}

		$data = $DB->get_records_sql("SELECT id, data as fieldname, COUNT(*) as users
			FROM {user_info_data}
			WHERE data != '' AND fieldid = $params->custom $sql_filter GROUP BY data $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data, 'custom'=> $params->custom);
	}

	function report88($params)
	{
		global $CFG, $DB;

		$sql_filter2 = "";
		$sql_filter3 = "";
		$sql_select = array();
		$sql_filter = $this->get_filterdate_sql($params, "g.timecreated");
		if($params->courseid){
			$sql_filter .= " AND gi.courseid = $params->courseid";
			$sql_select[] = "(SELECT fullname FROM {course} WHERE id = $params->courseid) as course";
		}else{
			return array();
		}
		if($params->users){
			$sql_filter3 .= " AND userid IN ($params->users)";
			$sql_filter2 .= " AND cmc.userid IN ($params->users)";
			$sql_filter .= " AND g.userid IN ($params->users)";
			$sql_select[] = "(SELECT CONCAT(firstname,' ',lastname) FROM {user} WHERE id IN ($params->users)) as user";
		}
		$sql_select = ($sql_select) ? ", " . implode(",", $sql_select) : "";

		$data = $DB->get_record_sql("SELECT
			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter AND ((g.finalgrade/g.rawgrademax)*100 ) < 60) AS grade_f,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter AND ((g.finalgrade/g.rawgrademax)*100 ) > 60 and ((g.finalgrade/g.rawgrademax)*100 ) < 70) AS grade_d,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter AND ((g.finalgrade/g.rawgrademax)*100 ) > 70 and ((g.finalgrade/g.rawgrademax)*100 ) < 80) AS grade_c,


			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter AND ((g.finalgrade/g.rawgrademax)*100 ) > 80 and ((g.finalgrade/g.rawgrademax)*100 ) < 90) AS grade_b,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter AND ((g.finalgrade/g.rawgrademax)*100 ) > 90) AS grade_a,

			(SELECT COUNT(DISTINCT param) FROM {local_intelliboard_tracking} WHERE page = 'module' AND courseid = $params->courseid $sql_filter3) as  modules_visited,

			(SELECT count(id) FROM {course_modules} WHERE visible = 1 AND course = $params->courseid) as modules_all,

			(SELECT count(id) FROM {course_modules} WHERE visible = 1 and completion = 1 AND course = $params->courseid) as modules,

			(SELECT count(cmc.id) FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cm.course=$params->courseid $sql_filter2) as modules_completed

			$sql_select
		");

		return array("data" => $data, "timestart"=>$params->timestart, "timefinish"=>$params->timefinish);
	}

	function report89($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"emploee_id",
				"emploee_name",
				"manager_name",
				"tr.education",
				"job_title",
				"overal_rating",
				"overal_perfomance_rating",
				"behaviors_rating",
				"promotability",
				"mobility",
				"tr.complited_date"),
		 	$this->get_filter_columns($params)
		 );
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter .= $this->get_filter_user_sql($params, "u.");

		$data = $DB->get_records_sql("SELECT tr.id,
				tr.user_id as emploee_id,
				CONCAT(u.firstname, ' ', u.lastname) as emploee_name,
				tr.manager as manager_name,
				tr.education,
				tr.title as job_title,
				tr.overal_review_rating as overal_rating,
				tr.goals_perfomance_overal as overal_perfomance_rating,
				tr.behaviors_overal as behaviors_rating,
				tr.complited_date,
                if(promotability_hp1 = 1, 1,
                  if(promotability_hp2 = 1, 2,
                      if(promotability_trusted = 1, 3,
                        if(promotability_placement = 1, 4,
                           if(promotability_too_new = 1, 5, 0)
                        )
                      )
                  	)
                  ) as promotability, relocatability as mobility
				$sql_columns
			FROM {local_talentreview} as tr
				LEFT JOIN {user} as u ON u.id = tr.user_id
			WHERE 1 $sql_filter $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}
	public function report90($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				'outcome_shortname',
				'outcome_fullname',
				'outcome_description',
				'sci.scale',
				'activity',
				'average_grade',
				'grades',
				'course_shortname',
				'course_fullname',
				'c.category',
				'c.startdate'), $this->get_filter_columns($params));

		$params->custom2 = clean_param($params->custom2, PARAM_SEQUENCE);
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = ($params->custom2) ? " AND o.id IN ($params->custom2) " : "";
		$sql_filter .= ($params->courseid) ? " AND c.id IN ($params->courseid) " : "";

		$sql_cm_if = array();
		$sql_cm_end = "";
		$modules = $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(gi.itemmodule='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = gi.iteminstance)";
			$sql_cm_end .= ")";
		}
		$sql_columns =  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";

		$data = $DB->get_records_sql("SELECT gi.id,
			c.shortname as course_shortname,
			c.fullname as course_fullname,
			o.fullname as outcome_fullname,
			o.shortname as outcome_shortname,
			o.description as outcome_description,
			sci.scale,
			ca.name as category,
			c.startdate,
			round(AVG(gg.finalgrade),2) AS average_grade,
			COUNT(DISTINCT gg.id) AS grades
			$sql_columns
		FROM {grade_outcomes} o
		LEFT JOIN {course} c ON c.id = o.courseid
		LEFT JOIN {course_categories} ca ON ca.id = c.category
		LEFT JOIN {scale} sci ON sci.id = o.scaleid
		LEFT JOIN {grade_items} gi ON gi.outcomeid = o.id
		LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id
		WHERE gi.itemtype = 'mod' $sql_filter GROUP BY gg.itemid $sql_having $sql_order $sql_limit", $this->params);

		foreach($data as $k=>$v){
			$scale = explode(',', $v->scale);
			$percent = $v->average_grade / count($scale);
			$iter = 1 / count($scale);
			$index = round( ($percent / $iter), 0, PHP_ROUND_HALF_DOWN)-1;
			$data[$k]->scale = (isset($scale[$index]))?$scale[$index]:'';
		}

		return array(
			"data"=>$data
		);
	}


	function report91($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"c.fullname",
				"cs.section",
				"activity",
				"completed"),
		 	$this->get_filter_columns($params)
		 );
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "cmc.userid", "users");
		$sql_filter .= ($params->courseid) ? " AND c.id IN($params->courseid)" : "";
		$sql_filter .= $this->get_filterdate_sql($params, "cmc.timemodified");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");

		$sql_cm_end = "";
		$sql_cm_if = array();
		$modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(m.name='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		$sql_columns =  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";

		$data = $DB->get_records_sql("SELECT cm.id, cm.visible as module_visible, cs.section, cs.name, cs.visible, c.fullname, COUNT(DISTINCT cmc.id) as completed $sql_columns
					FROM
					{course} c,
					{course_modules} cm,
					{modules} m,
					{course_modules_completion} cmc,
					{course_sections} cs
					WHERE
						cm.course = c.id AND
						m.id = cm.module AND
                        cm.section= cs.id AND
                        cs.course = cm.course AND
					    cmc.coursemoduleid = cm.id AND
					    cmc.completionstate = 1
					    $sql_filter
					GROUP BY cm.id $sql_having $sql_order $sql_limit", $this->params);

		return array("data" => $data);
	}
	function report93($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.firstname",
				"u.lastname",
				"u.email",
				"c.fullname",
				"enrolled",
				"progress",
				"modules_completed",
				"cc.timecompleted"),
		 	$this->get_filter_columns($params)
		 );
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= ($params->courseid) ? " AND c.id IN($params->courseid)" : "";
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");

		$data = $DB->get_records_sql("SELECT ue.id,
			u.firstname,
			u.lastname,
			u.email,
			ue.timecreated as enrolled,
			e.courseid,
			ue.userid,
			c.fullname,
			m.modules,
			cc.timecompleted,
			cmc.completed as modules_completed,
			round(((cmc.completed/m.modules)*100), 0) as progress
			$sql_columns
		FROM {user_enrolments} as ue
			LEFT JOIN {user} as u ON u.id = ue.userid
			LEFT JOIN {enrol} as e ON e.id = ue.enrolid
			LEFT JOIN {course} as c ON c.id = e.courseid
			LEFT JOIN {course_completions} as cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
			LEFT JOIN (SELECT course, count(id) as modules FROM {course_modules} WHERE visible = 1 AND completion > 0 GROUP BY course) as m ON m.course = c.id
			LEFT JOIN (SELECT cm.course, x.userid, count(DISTINCT x.id) as completed FROM {course_modules} cm, {course_modules_completion} x WHERE x.coursemoduleid = cm.id AND cm.visible = 1 AND x.completionstate = 1 GROUP BY cm.course, x.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid

		WHERE 1 $sql_filter GROUP BY ue.userid, e.courseid $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function report78($params)
	{
		global $CFG, $DB;

		$columns = array_merge(array(
				"u.id",
				"u.firstname",
				"u.lastname",
				"u.middlename",
				"u.email",
				"u.idnumber",
				"u.username",
				"u.phone1",
				"u.phone2",
				"u.institution",
				"u.department",
				"u.address",
				"u.city",
				"u.country",
				"u.auth",
				"u.confirmed",
				"u.suspended",
				"u.deleted",
				"u.timecreated",
				"u.timemodified",
				"u.firstaccess",
				"u.lastaccess",
				"u.lastlogin",
				"u.currentlogin",
				"u.lastip"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$sql_join = "";
		if($params->cohortid){
			$sql_filter .= " AND cm.cohortid  IN ($params->cohortid)";
		}

		$data = $DB->get_records_sql("SELECT u.* $sql_columns
						FROM {user} as u
							WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order $sql_limit", $this->params);

		return array( "data" => $data);
	}

	function report74($params)
	{
		global $CFG, $DB;

		$date = explode("-", $params->custom);
		$year = (int) $date[2];
		$start = (int) $date[0];
		$end = (int) $date[1];

		if(!$year or !$start or !$end or !$params->custom2){
			return array();
		}
		$position = ($params->custom2)?$params->custom2:4;

		$sql_select = "";
		$sql_join = "";
		if($start < $end){
			while($start <= $end){
				$startdate = strtotime("$start/1/$year");
				$enddate = strtotime("$start/1/$year +1 month");
				$sql_select .= ", k$start.users as month_$start";
				$sql_join .= "LEFT JOIN (SELECT p.organisationid, COUNT(distinct u.id) as users FROM {user} u, {pos_assignment} p, {pos} ps WHERE ps.id = $position AND ps.visible = 1 AND p.positionid = ps.id AND p.userid = u.id AND u.timecreated BETWEEN $startdate AND $enddate GROUP BY p.organisationid) k$start ON  k$start.organisationid = o.id ";
				$start++;
			}
		}

		$data = $DB->get_records_sql("SELECT o.id,
			o.fullname as organization,
			o.typeid,
			t.fullname as type,
			s.svp,
			k0.total
			$sql_select
			FROM {org} o
			LEFT JOIN {org_type} t ON t.id = o.typeid
			LEFT JOIN (SELECT o2.organisationid, o1.typeid, GROUP_CONCAT( DISTINCT o2.data) AS svp FROM {org_type_info_field} o1, {org_type_info_data} o2 WHERE o1.id = o2.fieldid AND o1.shortname LIKE '%svp%' GROUP BY o2.organisationid, o1.typeid) s ON s.organisationid = o.id AND s.typeid = t.id

			LEFT JOIN (SELECT f.typeid, d.organisationid, d.data as total FROM {org_type_info_field} f, {org_type_info_data} d WHERE f.shortname = 'techtotal' AND d.fieldid = f.id GROUP BY f.typeid, d.organisationid) k0 ON k0.organisationid = o.id AND k0.typeid = t.id

			$sql_join
			WHERE o.visible = 1 ORDER BY o.typeid, o.fullname");

		return array(
					"recordsTotal"    => count($data),
					"recordsFiltered" => count($data),
					"data"            => $data);

	}

	function report71($params)
	{
		global $CFG, $DB;


		$columns = array_merge(array("user","ue.timecreated", "e.enrol", "e.cost", "c.fullname"), $this->get_filter_columns($params));
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= ($params->courseid) ? " AND c.id IN($params->courseid)" : "";
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");

		$data = $DB->get_records_sql("
			SELECT ue.id,
				c.fullname,
				ue.timecreated,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				e.enrol,
				e.cost,
				e.currency
				$sql_columns
			FROM
				{user_enrolments} ue,
				{enrol} e,
				{user} u,
				{course} c
			WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter $sql_having $sql_order $sql_limit", $this->params);



		$data2 = $DB->get_records_sql("SELECT floor(ue.timecreated / 86400) * 86400 as timepoint, SUM(e.cost) as amount FROM
			{user_enrolments} ue, {enrol} e,{course} c,{user} u WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter GROUP BY floor(ue.timecreated / 86400) * 86400 ORDER BY timepoint ASC");

		return array("data2" => $data2, "data" => $data);
	}
	function report70($params)
	{
		global $CFG, $DB;

 		$columns = array("c.fullname", "forum", "d.name", "posts", "fp.student_posts", "ratio", "d.timemodified", "user", "");

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");

		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		$params->custom2 = clean_param($params->custom2, PARAM_SEQUENCE);

		$sql_filter .= ($params->courseid) ? " AND c.id IN($params->courseid)" : $sql_filter;
		$sql_filter .= ($params->custom) ? " AND d.forum IN($params->custom)" : $sql_filter;
		$roles = (isset($params->custom2) and $params->custom2) ? $params->custom2 : $this->teacher_roles;

		$data2 = $DB->get_records_sql("SELECT floor(p.created / 86400) * 86400 as timepoint, count(distinct p.id) as posts FROM {role_assignments} AS ra LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid LEFT JOIN {course} c ON c.id = ctx.instanceid LEFT JOIN {forum_discussions} d ON d.course = c.id LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id WHERE ra.roleid IN ($roles) AND ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 AND d.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY floor(p.created / 86400) * 86400 ORDER BY timepoint ASC");

		$data3 = $DB->get_records_sql("SELECT floor(p.created / 86400) * 86400 as timepoint, count(distinct p.id) as student_posts FROM {role_assignments} AS ra LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid LEFT JOIN {course} c ON c.id = ctx.instanceid LEFT JOIN {forum_discussions} d ON d.course = c.id LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id WHERE ra.roleid IN ($this->learner_roles) AND ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 AND d.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY floor(p.created / 86400) * 86400 ORDER BY timepoint ASC");

		$data4 = $DB->get_record_sql("SELECT count(distinct p.id) as posts FROM {role_assignments} AS ra LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid LEFT JOIN {course} c ON c.id = ctx.instanceid LEFT JOIN {forum_discussions} d ON d.course = c.id LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id WHERE ra.roleid IN ($roles) AND ctx.contextlevel = 50 AND d.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter");

		$data5 = $DB->get_record_sql("SELECT count(distinct p.id) as posts FROM {role_assignments} AS ra LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid LEFT JOIN {course} c ON c.id = ctx.instanceid LEFT JOIN {forum_discussions} d ON d.course = c.id LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id WHERE ra.roleid IN ($this->learner_roles) AND ctx.contextlevel = 50 AND d.timemodified BETWEEN $params->timestart AND $params->timefinish $sql_filter");

		$f1 = intval($data4->posts);
		$f2 = intval($data5->posts);
		$f3 = $f1 / $f2;
		$f3 = number_format($data5->posts, $f3);

		$data6 = array($f1, $f2, $f3);


		$data = $DB->get_records_sql("
				SELECT @x:=@x+1 as id,
					c.fullname,
					d.name,
					f.name as forum,
					CONCAT(u.firstname, ' ', u.lastname) as user,
					count(distinct p.id) as posts, d.timemodified,
					fp.student_posts, round((count(distinct p.id) / fp.student_posts ), 2) as ratio
				FROM (SELECT @x:= 0) AS x, {role_assignments} AS ra
				LEFT JOIN {user} as u ON u.id = ra.userid
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
				LEFT JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {forum_discussions} d ON d.course = c.id
				LEFT JOIN {forum} f ON f.id = d.forum
				LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id
				LEFT JOIN (
				   SELECT d.id, count(distinct p.id) as student_posts FROM {role_assignments} AS ra LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid LEFT JOIN {course} c ON c.id = ctx.instanceid LEFT JOIN {forum_discussions} d ON d.course = c.id LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id WHERE ra.roleid IN ($this->learner_roles) AND ctx.contextlevel = 50 $sql_filter GROUP BY p.discussion

				   ) fp ON fp.id = d.id
				WHERE ra.roleid IN ($roles) AND d.timemodified BETWEEN $params->timestart AND $params->timefinish AND ctx.contextlevel = 50 AND p.discussion > 0 $sql_filter GROUP BY  d.id, ra.userid $sql_having $sql_order $sql_limit", $this->params);



		return array( "data"            => $data,
					"data2"            => $data2,
					"data3"            => $data3,
					"data6"            => $data6);
	}
	function report67($params)
	{
		global $CFG, $DB;

 		$columns = array_merge(array("l.timecreated", "user", "u.email", "course", "l.objecttable", "activity", "l.origin", "l.ip"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_filter = $this->get_teacher_sql($params, "l.courseid", "courses");
		$sql_filter .= ($params->courseid) ? " AND l.courseid IN($params->courseid)" : "";
		$sql_filter .= $this->get_filterdate_sql($params, "l.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= " AND ch.cohortid  IN ($params->cohortid)";
		}
		$sql_mods = "";
		$list = clean_param($params->custom, PARAM_SEQUENCE);
		if($list){
			$sql_mods = " AND id IN ($list)";
			$sql_filter .= " AND m.id IN ($list)";
		}
		$sql_cm_if = array();
		$sql_cm_end = "";
		$modules = $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1 $sql_mods");
		foreach($modules as $module){
			$sql_cm_if[] = "IF(l.objecttable='{$module->name}', (SELECT name FROM {$CFG->prefix}{$module->name} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		$sql_columns .=  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";


		$data = $DB->get_records_sql("SELECT l.id,
			l.courseid,
			l.userid,
			l.contextinstanceid as cmid,
			l.objecttable,
			l.origin,
			l.ip,
			c.fullname as course,
			u.email,
			CONCAT(u.firstname, ' ', u.lastname) as user,
			l.timecreated
			$sql_columns
				FROM
				{logstore_standard_log} l
				LEFT JOIN {course} c ON c.id = l.courseid
				LEFT JOIN {user} u ON u.id = l.userid
				LEFT JOIN {modules} m ON m.name = l.objecttable
				LEFT JOIN {course_modules} cm ON cm.id = l.contextinstanceid
				$sql_join
				WHERE l.component LIKE '%mod_%' $sql_filter $sql_having $sql_order $sql_limit", $this->params);
		return array("data" => $data);
	}
	function report68($params)
	{
		global $CFG, $DB;

		$columns = array("qz.name", "ansyes", "ansno");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "qz.course", "courses");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$sql_select = "";
		$sql_from = "";

		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		if($params->custom){
			$sql_filter .= " AND qz.id IN ($params->custom)";
		}
		if($params->courseid){
			$sql_filter .= " AND qz.course IN ($params->courseid)";
			$sql_filter .= " AND c.id = qz.course ";
			$sql_select .= ", c.fullname as course";
			$sql_from .= ", {course} c";
		}
		if($params->users){
			$sql_filter .= " AND qt.userid IN ($params->users)";
			$users = explode(",", $params->users);
			if(count($users) == 1 and !empty($users)){
				$sql_select .= ", CONCAT(u.firstname, ' ', u.lastname) as username";
				$sql_from .= ", {user} as u";
				$sql_filter .= " AND u.id = qt.userid";
			}else{
				$sql_select .= ", '' as username";
				$sql_from .= "";
			}
		}
		if($params->cohortid){
			if($params->custom2){
				$sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE (a.clusterid = $params->cohortid OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = $params->cohortid)) AND b.cuserid = a.userid)";
				$sql_group = "GROUP BY qt.quiz, qt.attempt";
				$sql_select .= ", cm.cohorts";
				$sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE id IN ($params->cohortid)) cm";
			}else{
				$sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE cohortid  IN ($params->cohortid))";
				$sql_group = "GROUP BY qt.quiz, qt.attempt";
				$sql_select .= ", cm.cohorts";
				$sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE id  IN ($params->cohortid)) cm";
			}
		}else{
			$sql_group = "GROUP BY qt.quiz, qt.attempt";
		}

		$data = $DB->get_records_sql("SELECT qas.id, qt.id as attempt,
				    qz.name,
					qt.userid,
					qt.timestart,
					qt.quiz,
					qt.attempt,
				    SUM(IF(d.value=0,1,0)) as ansyes,
				    SUM(IF(d.value=1,1,0)) as ansno,
				    SUM(IF(d.value=2,1,0)) as ansne,
				    (SELECT MAX(attempt) FROM {quiz_attempts}) as attempts $sql_select
				FROM
					{quiz} qz,
					{quiz_attempts} qt,
					{question_attempts} qa,
					{question_attempt_steps} qas,
				    {question_attempt_step_data} d $sql_from
				WHERE
					qz.id = qt.quiz AND
					qa.questionusageid = qt.uniqueid AND
					qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
				    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state != 'inprogress'  $sql_filter
				$sql_group $sql_having ORDER BY qt.attempt ASC $sql_limit", $this->params);


		return array("data" => $data);
	}


	function report69($params)
	{
		global $CFG, $DB;

		$columns = array("qz.name", "ansyes", "ansno");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "qz.course", "courses");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$sql_select = "";
		$sql_from = "";
		$sql_attempts = "";

		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		if($params->custom){
			$sql_filter .= " AND qz.id IN ($params->custom)";
			$sql_attempts = " WHERE quiz IN ($params->custom)";
		}
		if($params->courseid){
			$sql_filter .= " AND qz.course IN ($params->courseid)";
			$sql_filter .= " AND c.id = qz.course ";
			$sql_select .= ", c.fullname as course";
			$sql_from .= " {course} c,";

		}
		if($params->cohortid){
			if($params->custom2){
				$sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE (a.clusterid IN ($params->cohortid) OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent IN ($params->cohortid))) AND b.cuserid = a.userid)";
				$sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
				$sql_select .= ", cm.cohorts";
				$sql_from .= "(SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE id IN ($params->cohortid)) cm, ";
			}else{
				$sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE cohortid  IN ($params->cohortid))";
				$sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";

				$sql_select .= ", cm.cohorts";
				$sql_from .= " (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE id  IN ($params->cohortid)) cm,";
			}
		}else{
			$sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
		}
		if($params->users){
			$data = $DB->get_records_sql("SELECT qas.id, qt.id as attempt,
			    qz.name,
				qt.userid,
				COUNT(DISTINCT qt.userid) as users,
				qt.timestart,
				qt.quiz,
				qt.attempt,
			    SUM(IF(d.value=0,1,0)) as ansyes,
			    SUM(IF(d.value=1,1,0)) as ansno,
			    SUM(IF(d.value=2,1,0)) as ansne,
			    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) as attempts, t.rawname as tag, ti.tagid,
			    CONCAT(u.firstname, ' ', u.lastname) as username $sql_select
			FROM
				{quiz} qz, {user} as u, $sql_from
				{quiz_attempts} qt,
				{question_attempt_steps} qas,
			    {question_attempt_step_data} d,
			    {question_attempts} qa
			    LEFT JOIN {tag_instance} ti ON ti.itemtype ='question' AND ti.itemid = qa.questionid
			    LEFT JOIN {tag} t ON t.id = ti.tagid

			WHERE
				qz.id = qt.quiz AND
				qa.questionusageid = qt.uniqueid AND
				qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
			    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state != 'inprogress' AND u.id = qt.userid AND
			    qt.userid IN ($params->users) $sql_filter
			$sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC $sql_limit", $this->params);

			//$sql_filter .= " AND qt.userid NOT IN ($params->users)";
		}else{
			$data = false;
		}

		$data2 = $DB->get_records_sql("SELECT qas.id, qt.id as attempt,
				    qz.name,
					qt.userid,
					COUNT(DISTINCT qt.userid) as users,
					qt.timestart,
					qt.quiz,
					qt.attempt,
				    SUM(IF(d.value=0,1,0)) as ansyes,
				    SUM(IF(d.value=1,1,0)) as ansno,
				    SUM(IF(d.value=2,1,0)) as ansne,
				    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) as attempts, t.rawname as tag, ti.tagid $sql_select
				FROM
					{quiz} qz, $sql_from
					{quiz_attempts} qt,
					{question_attempt_steps} qas,
				    {question_attempt_step_data} d,
				    {question_attempts} qa
				    LEFT JOIN {tag_instance} ti ON ti.itemtype ='question' AND ti.itemid = qa.questionid
				    LEFT JOIN {tag} t ON t.id = ti.tagid

				WHERE
					qz.id = qt.quiz AND
					qa.questionusageid = qt.uniqueid AND
					qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
				    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state != 'inprogress' $sql_filter
				$sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC $sql_limit", $this->params);

		if(!$data and !$params->users){
			$data = $data2;
			$data2 = array();
		}


		return array(
					"data2"			=> 	$data2,
					"data"            => $data);
	}




	function get_max_attempts($params)
	{
		global $CFG, $DB;

		$sql = "";
		if($params->filter){
			$sql .= " AND q.course IN (".intval($params->filter).") ";
		}
		if($params->custom){
			$sql .= " AND q.id IN (".intval($params->custom).") ";
		}
		return $DB->get_record_sql("
			SELECT
			(SELECT count(distinct t.tagid) as tags FROM {quiz} q, {quiz_slots} qs, {tag_instance} t
			WHERE qs.quizid = q.id AND t.itemid = qs.questionid AND t.itemtype ='question' $sql GROUP BY q.course ORDER BY tags DESC LIMIT 1) as tags,
			(SELECT MAX(qm.attempt) FROM {quiz_attempts} qm, {quiz} q WHERE qm.quiz = q.id $sql) as attempts ");
	}


	function report56($params)
	{
		global $CFG, $DB;

 		$columns = array("username", "c.fullname", "ue.enrols", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);

		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);

  		$data = $DB->get_records_sql("SELECT ue.id,
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
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN (".$this->getCourseUserGradeSql().") as gc ON gc.courseid = c.id AND gc.userid = ue.userid
							LEFT JOIN (".$this->getCurseUserTimeSql().") l ON l.courseid = c.id AND l.userid = ue.userid
							LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid=$params->userid GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
						WHERE ue.userid IN($params->custom) $sql_having $sql_order $sql_limit", $this->params);


		return array("data" => $data);
	}

	function analytic1($params)
	{
		global $CFG, $DB;


		$where_sql = "";
		$select_sql = "";

		if($CFG->version < 2014051200){
		   $table = "log";
		   $table_time = "time";
		   $table_course = "course";
        }else{
		   $table = "logstore_standard_log";
		   $table_time = "timecreated";
		   $table_course = "courseid";



		   if(!empty($params->custom) || $params->custom === 0){
               $select_sql = "LEFT JOIN {role_assignments} ra ON log.contextid=ra.contextid and ra.userid=log.userid";
               $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
               if(in_array(0,explode(',', $params->custom))){
                   $where_sql = "AND (ra.roleid IN ($params->custom) OR ra.roleid IS NULL)";
               }else{
                   $where_sql = "AND ra.roleid IN ($params->custom)";
               }
           }
        }

        if(empty($params->courseid))
            return array("data" => array());

		$data = $DB->get_records_sql("SELECT
                                      log.id,
									  COUNT(log.id) AS count,
									   WEEKDAY(FROM_UNIXTIME(log.$table_time,'%Y-%m-%d %T')) as day,
									   IF(FROM_UNIXTIME(log.$table_time,'%H')>=6 && FROM_UNIXTIME(log.$table_time,'%H')<12,'1',
										 IF(FROM_UNIXTIME(log.$table_time,'%H')>=12 && FROM_UNIXTIME(log.$table_time,'%H')<17,'2',
										 IF(FROM_UNIXTIME(log.$table_time,'%H')>=17 && FROM_UNIXTIME(log.$table_time,'%H')<=23,'3',
										 IF(FROM_UNIXTIME(log.$table_time,'%H')>=0 && FROM_UNIXTIME(log.$table_time,'%H')<6,'4','undef')))) as time_of_day
									 FROM {$CFG->prefix}{$table} log
									  $select_sql
									 WHERE `$table_course` IN ($params->courseid) AND $table_time BETWEEN $params->timestart AND $params->timefinish $where_sql GROUP BY day,time_of_day ORDER BY time_of_day, day");

		return array("data" => $data);
	}
	function analytic2($params)
	{
		global $DB, $CFG;
		$fields = explode(',',$params->custom2);

        $field_ids = array(0);
        foreach($fields as $field){
            if(strpos($field,'=') > 0){
                list($id,$name) = explode('=',$field);
                $field_ids[] = $id;
            }
        }

        $data = $DB->get_records_sql("SELECT
									  uid.id,
									  uif.id AS fieldid,
									  uif.name,
									  COUNT(uid.userid) AS users,
									  uid.data
									 FROM {user_info_field} uif
										LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
									 WHERE uif.id IN (".implode(',',$field_ids).") GROUP BY uid.data,uif.id");

		if(isset($params->custom) && !empty($params->custom)){
			$params->custom = json_decode($params->custom);
            $params->custom->field_id = clean_param($params->custom->field_id,PARAM_INT);
            $params->custom->field_value = clean_raw($params->custom->field_value,false);


			$join_sql = $select_sql = $where_sql = '';
			$where = array();
			$coll = array("u.firstname", "u.lastname", "u.email");
            $enabled_tracking = false;
			foreach($fields as $field){
			    if($field == 'average_grade'){
                    $join_sql .= " JOIN {grade_items} gi ON gi.itemtype = 'course'
                                      LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id ";
                    $select_sql .= " round(((g.finalgrade/gi.grademax)*100), 0) AS average_grade, ";
                    $coll[] = "average_grade";
                }elseif($field == 'courses_enrolled'){
                    $join_sql .= " JOIN {user_enrolments} ue ON ue.userid=u.id
                                      LEFT JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid>1";
                    $select_sql .= " COUNT(DISTINCT e.courseid) AS courses_enrolled, ";
                    $coll[] = "courses_enrolled";
                }elseif($field == 'total_visits' || $field == 'time_spent'){
                    $join_sql .= (!$enabled_tracking)?" LEFT JOIN (SELECT lit.userid, sum(lit.timespend) as timespend, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit GROUP BY lit.userid) lit ON lit.userid = u.id ":'';
                    $select_sql .= ($field == 'total_visits')?' lit.visits as total_visits, ':' lit.timespend as time_spent, ';
                    $coll[] = $field;
                    $enabled_tracking = true;
                }else{
                    if(empty($field)) continue;
                    list($id,$name) = explode('=',$field);
                    $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid={$id} ";
                    $select_sql .= " uid{$id}.data as field_{$id}, ";
                    if($params->custom->field_id != 0){
                        $where[] = " (uid{$id}.fieldid=".$params->custom->field_id." AND uid{$id}.data='".$params->custom->field_value."') ";
                    }
                    $coll[] = "field_{$id}";
                }
			}

			if(!empty($where))
				$where_sql = 'AND ('.implode('OR',$where).')';

			$order_sql = $this->get_order_sql($params, $coll);
			$limit_sql = $this->get_limit_sql($params);

            $sql = "SELECT
										  u.id,
										  u.firstname,
										  u.lastname,
										  u.email,
										  $select_sql
										  u.id AS userid
										 FROM {user} u
											$join_sql
										 WHERE u.id>1 $where_sql GROUP BY u.id $order_sql $limit_sql";

			$users = $DB->get_records_sql($sql);
			$size = $this->count_records($sql);
			return array('users'=>$users,"recordsTotal" => $size,"recordsFiltered" => $size,'data'=>$data);
		}

		$join_sql = $select_sql = '';
		foreach($fields as $field){
            if($field == 'average_grade' || $field == 'total_visits' || $field == 'time_spent' || $field == 'courses_enrolled'){
                $select_sql .= " 0 as {$field}, ";
            }else{
                if(empty($field)) continue;
                list($id,$name) = explode('=',$field);
                $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid={$id} ";
                $select_sql .= " uid{$id}.data as field_{$id}, ";
            }

		}
		$user = $DB->get_record_sql("SELECT
									  u.id,
									  u.firstname,
									  u.lastname,
									  u.email,
									  $select_sql
									  u.id AS userid
									 FROM {user} u
										$join_sql
									 WHERE u.id>0 LIMIT 1");
		return array("data" => $data, 'user'=>$user);
	}

	function get_quizes($params)
	{
		global $DB, $CFG;

		$sql = (!empty($params->courseid)) ? ' WHERE q.course IN('.$params->courseid.')' : '';

		$data = $DB->get_records_sql("SELECT q.id, q.name, c.id as courseid, c.fullname as coursename
			FROM {quiz} q
				LEFT JOIN {course} c ON c.id=q.course $sql");

		return array('data'=>$data);
	}

	function analytic3($params)
	{
		global $CFG, $DB;
		$data = array();
		if(is_numeric($params->custom)){
			$where = '';
			if($params->custom > 0)
				$where .= ' AND q.id='.$params->custom;
			if($params->courseid > 0)
				$where .= " AND q.course=$params->courseid";

			$data = $DB->get_records_sql("SELECT qas.id,
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
										  qa.id,
										  COUNT(qa.id) AS count,
										   WEEKDAY(FROM_UNIXTIME(qa.timefinish,'%Y-%m-%d %T')) as day,
										   IF(FROM_UNIXTIME(qa.timefinish,'%H')>=6 && FROM_UNIXTIME(qa.timefinish,'%H')<12,'1',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=12 && FROM_UNIXTIME(qa.timefinish,'%H')<17,'2',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=17 && FROM_UNIXTIME(qa.timefinish,'%H')<=23,'3',
											 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=0 && FROM_UNIXTIME(qa.timefinish,'%H')<6,'4','undef')))) as time_of_day
										 FROM {quiz} q
											LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.state='finished' AND qa.sumgrades IS NOT NULL
										 WHERE q.id>0 $where GROUP BY day,time_of_day ORDER BY time_of_day, day");

			$grades = $DB->get_records_sql("SELECT gg.id,
											q.id AS quiz_id,
											q.name AS quiz_name,
											ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS gradepass,
											COUNT(DISTINCT gg.userid) AS users,
											ROUND(((gg.rawgrade - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS grade
										 FROM {quiz} q
											LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
											LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid<>2 AND gg.rawgrade IS NOT NULL
										 WHERE gg.rawgrade IS NOT NULL $where GROUP BY ROUND(((gg.rawgrade - gg.rawgrademin)/(gg.rawgrademax - gg.rawgrademin))*100,0),quiz_id");
		}


		return array("data" => $data, "time"=>$time, "grades"=>$grades);
	}
	function analytic4($params)
	{
		global $CFG, $DB;

		if(!empty($params->custom)){
			if($params->custom == 'get_countries'){
				$countries = $DB->get_records_sql("SELECT
                                                    u.id,
                                                    u.country,
                                                    uid.data as state,
                                                    COUNT(DISTINCT u.id) as users
                                                FROM {user} u
                                                    LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                                                    LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                                                WHERE u.country NOT LIKE '' GROUP BY u.country,uid.data");
				return array("countries" => $countries);
			}else{

				$columns = array_merge(array("u.firstname", "u.lastname", "u.email", "u.country", "state", "course", "ue.enrols", "grade", "l.timespend", "complete"), $this->get_filter_columns($params));

				$where = array();
				$where_str = '';
				$custom = unserialize($params->custom);
				if(!empty($custom['country'])){
				    $custom['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
                    $where[] = "u.country='" . $custom['country'] . "'";
                }
				if(isset($custom['state']) && !empty($custom['state'])){
                    $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
                    $where[] = "uid.data LIKE '%(" . $custom['state'] . ")%'";
                }
				if(isset($custom['enrol']) && !empty($custom['enrol'])){
                    $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
                    $where[] = 'e.enrol IN("' . $custom['enrol'] . '")';
                }
				if(!empty($where))
					$where_str = " AND ".implode(' AND ',$where);

				$where_sql = "WHERE u.id IS NOT NULL ".$where_str;
				$order_sql = $this->get_order_sql($params, $columns);
				$limit_sql = $this->get_limit_sql($params);
				$sql_columns = $this->get_columns($params, "u.id");
                $sql = "SELECT
							  ue.id,
                              round(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                              c.enablecompletion,
                              cc.timecompleted as complete,
                              u.id as uid,
                              u.email,
                              u.country,
                              uid.data as state,
                              u.firstname,
                              u.lastname,
                              GROUP_CONCAT(DISTINCT e.enrol) as enrols,
                              c.id as cid,
                              c.fullname as course,
                              l.timespend
							  $sql_columns
							FROM {user} u
							  LEFT JOIN {user_enrolments} ue ON u.id = ue.userid
                              LEFT JOIN {enrol} e ON e.id = ue.enrolid

                              LEFT JOIN {course} as c ON c.id = e.courseid
                              LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid
                              LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                              LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id
                              LEFT JOIN (SELECT lit.userid,
                                                lit.courseid,
                                                sum(lit.timespend) as timespend
                                              FROM
                                                {local_intelliboard_tracking} lit
                                              GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id
                              LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                              LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
							$where_sql GROUP BY u.id, c.id $order_sql $limit_sql
						";

				$users = $DB->get_records_sql($sql);
                $size = $this->count_records($sql);

				return array("users" => $users,"recordsTotal" => $size,"recordsFiltered" => $size);
			}
		}

		$methods = $DB->get_records_sql("SELECT
											e.id,
											e.enrol,
											COUNT(DISTINCT ue.id) as users
										FROM {enrol} e
											LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
										WHERE e.id>0 GROUP BY e.enrol");

		$countries = $DB->get_records_sql("SELECT
                                                u.id,
                                                u.country,
                                                uid.data as state,
                                                COUNT(DISTINCT u.id) as users
                                             FROM {user} u
                                                LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                                                LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                                             WHERE u.country NOT LIKE '' GROUP BY u.country,uid.data");

		return array("methods" => $methods, "countries" => $countries);
	}
    function analytic5($params)
    {
        global $DB;
        $params->custom = clean_param($params->custom,PARAM_INT);

        $data = $DB->get_records_sql("SELECT
										qa.id,
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

        return array("data" => $data, 'overall_info'=>$overall_info);
    }
    function analytic5table($params)
    {
        global $DB;
        $columns = array("que.id", "que.name", "que.questiontext");
        $order_sql = $this->get_order_sql($params, $columns);
        $limit_sql = $this->get_limit_sql($params);
        $params->custom = clean_param($params->custom,PARAM_INT);

        $sql = "SELECT
													qas.id,
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

													LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.sequencenumber = (SELECT MAX(sequencenumber) FROM {question_attempt_steps} WHERE questionattemptid = qua.id)
													LEFT JOIN {question} que ON que.id=qua.questionid

												WHERE q.id=$params->custom GROUP BY `attempt_category`,que.id $order_sql $limit_sql";

        $question_info = $DB->get_records_sql($sql);
        $size = $this->count_records($sql);

        return array('question_info'=>$question_info,"recordsTotal" => $size,"recordsFiltered" => $size);
    }

	function analytic6($params){
		global $DB,$CFG;
        $params->custom = clean_param($params->custom,PARAM_INT);

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
											log.id,
											COUNT(log.id) AS `all`,
											SUM(IF(log.userid=$params->custom ,1,0)) as user,
											FROM_UNIXTIME(log.$table_time,'%m/%d/%Y') as `day`
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN {".$table."} log ON c.instanceid=log.$table_course AND ra.userid=log.userid
										WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND log.$table_time BETWEEN $params->timestart AND $params->timefinish
										GROUP BY `day`
										ORDER BY day DESC");

		$access = $DB->get_records_sql("SELECT
											log.id,
											COUNT(log.id) AS `all`,
											SUM(IF(log.userid=$params->custom ,1,0)) as user,
											FROM_UNIXTIME(log.$table_time,'%m/%d/%Y') as `day`
										FROM {context} c
											LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											LEFT JOIN {".$table."} log ON c.instanceid=log.$table_course AND ra.userid=log.userid
										WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND log.target='course' AND log.action='viewed' AND log.$table_time BETWEEN $params->timestart AND $params->timefinish
										GROUP BY `day`
										ORDER BY day DESC");

		$timespend = $DB->get_record_sql("SELECT
                                              SUM(t.timespend) AS `all`,
                                              tu.timespend AS user
                                            FROM {context} c
                                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
                                            LEFT JOIN (SELECT lit.userid, sum(lit.timespend) as timespend
                                                          FROM {local_intelliboard_tracking} lit
                                                          WHERE lit.courseid=$params->courseid
                                                          GROUP BY lit.userid) t ON t.userid=ra.userid
                                            LEFT JOIN (SELECT lit.userid, sum(lit.timespend) as timespend
                                                        FROM {local_intelliboard_tracking} lit
                                                        WHERE lit.courseid=$params->courseid AND lit.userid=$params->custom) tu ON tu.userid=$params->custom
                                            WHERE c.instanceid=$params->courseid AND c.contextlevel=50
									");

		$count_students = $DB->get_record_sql("SELECT
												COUNT(DISTINCT ra.userid) as students
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 ");

		$user_quiz = $DB->get_records_sql("SELECT
												qa.id,
												COUNT(qa.id) as `all`,
												SUM(IF(qa.userid=$params->custom,1,0)) as `user`,
												FROM_UNIXTIME(qa.timefinish,'%m/%d/%Y') as `day`
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
												LEFT JOIN {quiz} q ON q.course=c.instanceid
												LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.userid=ra.userid AND qa.state='finished'
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND qa.id IS NOT NULL AND qa.timefinish BETWEEN $params->timestart AND $params->timefinish
											GROUP BY `day`");

		$user_assign = $DB->get_records_sql("SELECT
												asub.id,
												COUNT(asub.id) as `all`,
												SUM(IF(asub.userid=$params->custom,1,0)) as `user`,
												FROM_UNIXTIME(asub.timemodified,'%m/%d/%Y') as `day`
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
												LEFT JOIN {assign} a ON a.course=c.instanceid
												LEFT JOIN {assign_submission} asub ON asub.assignment=a.id AND asub.userid=ra.userid AND asub.status='submitted'
											WHERE c.instanceid=$params->courseid AND c.contextlevel=50 AND asub.id IS NOT NULL AND asub.timemodified BETWEEN $params->timestart AND $params->timefinish
											GROUP BY `day`");

		$score = $DB->get_record_sql("SELECT
                                          (SELECT round(avg((g.finalgrade/g.rawgrademax)*100), 0)
                                              FROM
                                                {grade_items} gi,
                                                {grade_grades} g
                                              WHERE
                                                gi.itemtype = 'course' AND
                                                g.itemid = gi.id AND
                                                gi.courseid = $params->courseid) as avg,
                                          (SELECT round(((g.finalgrade/g.rawgrademax)*100), 0)
                                              FROM
                                                {grade_items} gi,
                                                {grade_grades} g
                                              WHERE
                                                gi.itemtype = 'course' AND
                                                g.itemid = gi.id AND
                                                gi.courseid = $params->courseid AND
                                                g.userid = $params->custom) as user
									");
		return array("interactions" => $interactions,"access" => $access,"timespend" => $timespend,"user_quiz" => $user_quiz,"user_assign" => $user_assign,'score'=>$score,'count_students'=>$count_students);
	}

	function analytic7($params){
		global $DB;

		$countries = $DB->get_records_sql("SELECT
                                                u.id,
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
													SUM(IF(gg.finalgrade>gi.grademin AND cc.timecompleted IS NULL,1,0)) as not_completed,
													SUM(IF(cc.timecompleted>0,1,0)) as completed,
													SUM(IF(cc.timestarted>0 AND cc.timecompleted IS NULL AND (gg.finalgrade=gi.grademin OR gg.finalgrade IS NULL),1,0)) as in_progress
											FROM {context} c
												LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN($params->learner_roles)
												LEFT JOIN {user} u ON u.id=ra.userid
												LEFT JOIN {course_completions} cc ON cc.course=c.instanceid AND cc.userid=u.id
												LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.instanceid
												LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
											WHERE c.contextlevel=50 AND c.instanceid IN ($params->courseid) AND u.id IS NOT NULL ");

		$grade_range = $DB->get_records_sql("SELECT
                                                    CONCAT(10*floor((((gg.finalgrade-gi.grademin)/(gi.grademax-gi.grademin))*100)/10),
                                                             '-',
                                                             10*floor((((gg.finalgrade-gi.grademin)/(gi.grademax-gi.grademin))*100)/10) + 10,
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
		global $CFG, $DB;

		$columns = array_merge(array("name", "u.email", "c.fullname", "u.country", "uid.data", "ue.enrols", "l.visits", "l.timespend", "grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));


		$sql_filter = " AND c.id IN ($params->courseid) ";
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_limit = $this->get_limit_sql($params);

		$where = array(" ra.roleid IN($this->learner_roles) ");
		$where_str = '';
		$custom = unserialize($params->custom);
		if(!empty($custom['country']) && $custom['country'] != 'world'){
            $custom['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
            $where[] = "u.country='" . $custom['country'] . "'";
        }
		if(isset($custom['state']) && !empty($custom['state'])){
            $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
            $where[] = "uid.data LIKE '%(" . $custom['state'] . ")%'";
        }
		if(isset($custom['enrol']) && !empty($custom['enrol'])){
            $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
            $where[] = "e.enrol LIKE '%" . $custom['enrol'] . "%'";
        }
		if(isset($custom['grades']) && !empty($custom['grades'])){
            $custom['grades'] = clean_param($custom['grades'],PARAM_ALPHANUMEXT);
			$grades = explode('-',$custom['grades']);
			$grades[1] = (empty($grades[1]))?110:$grades[1];
			$where[] = "round(((g.finalgrade/gi.grademax)*100), 0) BETWEEN ".$grades[0]." AND ".($grades[1]-0.001);
		}
		if(isset($custom['user_status']) && !empty($custom['user_status'])){
            $custom['user_status'] = clean_param($custom['user_status'],PARAM_INT);
			if($custom['user_status'] == 1){
				$where[] = "(round(((g.finalgrade/gi.grademax)*100), 0)>0 AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
			}elseif($custom['user_status'] == 2){
				$where[] = "cc.timecompleted>0";
			}elseif($custom['user_status'] == 3){
				$where[] = "(cc.timestarted>0 AND (round(((g.finalgrade/gi.grademax)*100), 0)=0 OR g.finalgrade IS NULL) AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
			}
		}
		if(!empty($where))
			$where_str = " AND ".implode(' AND ',$where);

		$where_sql = "WHERE u.id IS NOT NULL ".$where_str;

        $sql = "SELECT
                      ue.id,
                      ue.timecreated as enrolled,
                      round(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                      c.enablecompletion,
                      cc.timecompleted as complete,
                      u.id as uid, u.email,
                      u.country,
                      uid.data as state,
                      CONCAT(u.firstname, ' ', u.lastname) as name,
                      GROUP_CONCAT( DISTINCT e.enrol) AS enrols,
                      l.timespend,
                      l.visits,
                      c.id as cid,
                      c.fullname as course,
                      c.timemodified as start_date
                      $sql_columns
                    FROM {user_enrolments} ue
                      LEFT JOIN {enrol} e ON e.id = ue.enrolid
                      LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                      LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                      LEFT JOIN {user} as u ON u.id = ue.userid
                      LEFT JOIN {course} as c ON c.id = e.courseid
                      LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid

                      LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                      LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                      LEFT JOIN (SELECT lit.userid,
                                   lit.courseid,
                                   sum(lit.timespend) as timespend,
                                   sum(lit.visits) as visits
                                 FROM
                                   {local_intelliboard_tracking} lit
                                 GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                      LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                      LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
                    $where_sql $sql_filter
                    GROUP BY u.id, c.id
                    $sql_order $sql_limit";

		$data = $DB->get_records_sql($sql);

		$size = $this->count_records($sql);
		return array(
					"recordsTotal"    => $size,
					"recordsFiltered" => $size,
					"data"            => $data);
	}

	function analytic8($params){
		global $CFG, $DB;

		$columns = array("coursename", "cohortname", "learners_completed", "learners_not_completed", "learners_overdue", "avg_grade", "timespend");


		$sql_filter = " AND ra.roleid IN($this->learner_roles) ";
		$sql_filter .= ($params->courseid)?" AND c.id IN ($params->courseid) ":'';
		$sql_filter .= (!empty($params->cohortid) && $params->cohortid !== 0)?" AND cm.cohortid IN ($params->cohortid) ":'';
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_limit = $this->get_limit_sql($params);
		$params->custom = clean_param($params->custom, PARAM_INT);
		$params->custom = ($params->custom)?$params->custom:time();

        $sql = "SELECT
                  ue.id,
                  c.id as courseid,
                  c.fullname as coursename,
                  cm.cohortid,
                  coh.name as cohortname,
                  round(AVG(((g.finalgrade/gi.grademax)*100)), 0) as avg_grade,
                  SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>0,1,0)) as learners_completed,
                  SUM(IF(cr.completion IS NOT NULL AND (cc.timecompleted=0 OR cc.timecompleted IS NULL),1,0)) as learners_not_completed,
                  SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>$params->custom ,1,0)) as learners_overdue,
                  AVG(l.timespend) as timespend
                FROM {user_enrolments} ue
                  LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                  LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                  LEFT JOIN {user} as u ON u.id = ue.userid
                  LEFT JOIN {course} as c ON c.id = e.courseid
                  LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                  LEFT JOIN (SELECT lit.userid, lit.courseid, sum(lit.timespend) as timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id
                  LEFT JOIN {cohort_members} cm ON cm.userid = u.id
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) as completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=e.courseid
                WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_filter GROUP BY c.id,cm.cohortid $sql_order $sql_limit";

		$data = $DB->get_records_sql($sql);
		$size = $this->count_records($sql);

		return array(
					"recordsTotal"    => $size,
					"recordsFiltered" => $size,
					"data"            => $data);
	}

	function analytic8details($params){
		global $CFG, $DB;
		$custom = json_decode($params->custom);

		if($params->cohortid === 0 && $params->courseid === 0){
			return array(
					"recordsTotal"    => 0,
					"recordsFiltered" => 0,
					"data"            => array());
		}

        $sql_where = '';
		if($custom->user_status == 1){
			$sql_where = " AND cc.timecompleted>0 ";
		}elseif($custom->user_status == 2){
			$sql_where = " AND (cc.timecompleted=0 OR cc.timecompleted IS NULL) ";
		}elseif($custom->user_status == 3){
			$sql_where = " AND cc.timecompleted>".$custom->duedate;
		}

		$columns = array_merge(array("coursename", "cohortname", "learnername", "u.email", "grade", "l.timespend","cc.timecompleted"), $this->get_filter_columns($params));

        $sql_filter = " AND ra.roleid IN($this->learner_roles) ";
        $sql_filter .= ($params->courseid)?" AND c.id IN ($params->courseid) ":'';
        $sql_filter .= (!empty($params->cohortid) && $params->cohortid !== 0)?" AND cm.cohortid IN ($params->cohortid) ":'';
        $sql_filter .= ($params->cohortid == 0 && $params->custom2 == 1)?" AND cm.cohortid IS NULL ":'';
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_limit = $this->get_limit_sql($params);
        $sql_columns = $this->get_columns($params, "u.id");

        if($params->filter){
			$sql_filter .= " AND (" . $DB->sql_like('u.firstname', ":firstname", false, false);
			$sql_filter .= " OR " . $DB->sql_like('u.lastname', ":lastname", false, false);
			$sql_filter .= ")";
			$this->params['firstname'] = "%$params->filter%";
			$this->params['lastname'] = "%$params->filter%";
		}

        $sql = "SELECT
                  ue.id,
                  c.id as courseid,
                  c.fullname as coursename,
                  cm.cohortid,
                  coh.name as cohortname,
                  round(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                  l.timespend,
                  CONCAT(u.firstname, ' ', u.lastname) as learnername,
                  u.email,
                  cc.timecompleted
                  $sql_columns
                FROM {user_enrolments} ue
                  LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                  LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                  LEFT JOIN {user} as u ON u.id = ue.userid
                  LEFT JOIN {course} as c ON c.id = e.courseid
                  LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                  LEFT JOIN (SELECT lit.userid,
                               lit.courseid,
                               sum(lit.timespend) as timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                  LEFT JOIN {cohort_members} cm ON cm.userid = u.id
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) as completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=e.courseid
                WHERE cr.completion IS NOT NULL AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql_where $sql_filter $sql_order $sql_limit";


		$data = $DB->get_records_sql($sql, $this->params);
		$size = $this->count_records($sql, 'id', $this->params);


		return array(
				"recordsTotal"    => $size,
				"recordsFiltered" => $size,
				"data"            => $data);
	}

	function get_course_instructors($params)
	{
		global $DB, $CFG;

		$sql = "";
		if($params->courseid){
			$sql .= " AND ctx.instanceid IN ($params->courseid)";
		}
		$sql .= $this->get_filter_user_sql($params, "u.");

		return $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) as name, u.email
			FROM {role_assignments} AS ra
				JOIN {user} as u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
			WHERE ra.roleid IN ($this->teacher_roles) AND ctx.contextlevel = 50 $sql");
	}
	function get_course_discussions($params)
	{
		global $DB;

		$sql = "";
		if($params->courseid){
			$sql .= " WHERE course IN ($params->courseid)";
		}
		return $DB->get_records_sql("SELECT id, name FROM {forum} $sql");
	}

	function get_cohort_users($params)
	{
		global $CFG, $DB;

		$sql = "";
		if($params->custom2){
			if($params->cohortid){
				$sql = " AND (a.clusterid = $params->cohortid or a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = $params->cohortid))";
			}
			return $DB->get_records_sql("SELECT DISTINCT b.muserid as id, CONCAT(u.firstname,' ',u.lastname) as name FROM {local_elisprogram_uset_asign} a,{local_elisprogram_usr_mdl} b, {local_elisprogram_usr} u WHERE a.userid = u.id AND b.cuserid = a.userid and b.muserid IN (SELECT distinct userid FROM {quiz_attempts} where state = 'finished') $sql");
		}else{
			if($params->cohortid){
				$sql = " AND cm.cohortid IN($params->cohortid)";
			}
			if($params->courseid){
				$sql .= " AND u.id IN(SELECT distinct ue.userid FROM {user_enrolments} ue, {enrol} e where e.courseid IN ($params->courseid) and ue.enrolid = e.id)";
			}
			return $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) as name
				FROM {user} u, {cohort_members} cm
				WHERE cm.userid = u.id AND u.deleted = 0 AND u.suspended = 0 and u.id IN (SELECT distinct userid FROM {quiz_attempts} where state = 'finished') $sql");
		}
	}
	function get_users($params){
		global $DB;

		$sql = "";
		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		if($params->custom){
			$sql = " AND us.id IN($params->custom)";
		}
		$sql .= $this->get_filter_user_sql($params, "us.");


		$data = $DB->get_records_sql("SELECT us.id, CONCAT(us.firstname,' ',us.lastname) as name
									FROM {context} c
										LEFT JOIN {role_assignments} ra ON ra.contextid=c.id AND ra.roleid IN ($params->learner_roles)
										LEFT JOIN {user} us ON us.id=ra.userid
									WHERE us.id IS NOT NULL AND c.contextlevel=50 AND c.instanceid=$params->courseid $sql
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
		global $CFG, $DB;

		if($CFG->version < 2012120301){
			$sql_extra = "q.questions";
		}else{
			$sql_extra = "qat.layout";
		}
		return $DB->get_records_sql("SELECT qa.id, ROUND(((qa.maxmark * qas.fraction) * q.grade / q.sumgrades),2) as grade, qa.slot, qu.id as attempt, q.name as quiz, que.name as question, que.questiontext, qas.userid, qas.state, qas.timecreated, FORMAT(((LENGTH($sql_extra) - LENGTH(REPLACE($sql_extra, ',', '')) + 1)/2), 0) as questions
					FROM
					{question_attempts} qa,
					{question_attempt_steps} qas,
					{question_usages} qu,
					{question} que,
					{quiz} q,
                    {quiz_attempts} qat,
					{context} cx,
					{course_modules} cm
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
		global $CFG, $DB;

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
					FROM {user_enrolments} ue
						LEFT JOIN {user} u ON u.id = ue.userid
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
						LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
						LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
						LEFT JOIN {role} r ON r.id = ra.roleid
							WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish $sql GROUP BY ue.id ORDER BY ue.timecreated DESC LIMIT 10");
		}
		if($config->users){
			$data['users'] = $DB->get_records_sql("SELECT u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username,  u.timecreated as timepoint, cx.id as context, u.auth
						FROM {user} u
							LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
								WHERE u.timecreated BETWEEN $params->timestart AND $params->timefinish $sql ORDER BY u.timecreated DESC LIMIT 10");
			}
			if($config->completions){
					$data['completions'] = $DB->get_records_sql("SELECT cc.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username,  cx.id as context,  cc.timecompleted as timepoint, c.id as cid, c.fullname as course
								FROM {course_completions} cc, {course} c, {user} u
										LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
											WHERE u.id = cc.userid AND c.id = cc.course AND cc.timecompleted BETWEEN $params->timestart AND $params->timefinish $sql ORDER BY cc.timecompleted DESC LIMIT 10");
			}
			if($config->grades){
					$data['grades'] = $DB->get_records_sql("SELECT g.id, u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.email, u.username, cx.id as context, ((g.finalgrade/g.rawgrademax)*100) as grade, IFNULL(g.timemodified, g.timecreated)  as timepoint, gi.itemname, gi.itemtype,  gi.itemmodule, c.id as cid, c.fullname as course
								FROM {grade_grades} g, {grade_items} gi, {course} c, {user} u
										LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
											WHERE gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid AND g.finalgrade IS NOT NULL AND (g.timecreated BETWEEN $params->timestart AND $params->timefinish OR g.timemodified BETWEEN $params->timestart AND $params->timefinish) $sql ORDER BY g.timecreated DESC LIMIT 10");
			}
			if($config->online){
					$data['online'] = $DB->get_records_sql("SELECT u.id as uid, CONCAT( u.firstname, ' ', u.lastname ) AS name, u.lastaccess as timepoint, cx.id as context
								FROM {user} u
									LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
										WHERE u.lastaccess BETWEEN $onlinestart AND $params->timefinish $sql ORDER BY u.timecreated DESC LIMIT 10");
			}

			return $data;
	}
	function get_total_info($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");
		$sql2 = $this->get_teacher_sql($params, "id", "users");
		$sql3 = $this->get_teacher_sql($params, "id", "courses");
		$sql4 = $this->get_teacher_sql($params, "course", "courses");
		$sql2 .= $this->get_filter_enrolled_users_sql($params, "id");
		$sql2 .= $this->get_filter_user_sql($params, "");
		$sql3 .= $this->get_filter_course_sql($params, "");
		$sql4 .= $this->get_filter_module_sql($params, "");

		if($params->sizemode){
			$sql_files = "
				'0' as space,
				'0' as userspace,
				'0' as coursespace";
		}else{
			$sql_files = "
				(SELECT SUM(filesize) FROM {files} WHERE id > 0 $sql) as space,
				(SELECT SUM(filesize) FROM {files} WHERE component='user' $sql) as userspace,
				(SELECT SUM(filesize) FROM {files} WHERE filearea='content' $sql) as coursespace";
		}

		return $DB->get_record_sql("SELECT
			(SELECT count(*) FROM {user} WHERE 1 $sql2) as users,
			(SELECT count(*) FROM {course} WHERE category > 0 $sql3) as courses,
			(SELECT count(*) FROM {course_modules} WHERE 1 $sql4) as modules,
			(SELECT count(*) FROM {course_categories} WHERE visible = 1) as categories,
			(SELECT count(*) FROM {user} WHERE lastaccess > 0 $sql2) as learners,
			$sql_files");
	}
	function get_system_users($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_record_sql("SELECT
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' $sql) as users,
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' and u.deleted = 1 $sql) as deleted,
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' and u.deleted = 0 AND u.suspended = 0 and u.lastaccess > 0 $sql) as active,
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' and (u.confirmed = 0 OR u.deleted = 1) $sql) as deactive,
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' and u.deleted = 0 and u.lastlogin > 0 $sql) as returned,
			(SELECT count(DISTINCT (u.id)) FROM {user} u WHERE u.username != 'guest' and u.suspended = 1 $sql) as suspended,
			(SELECT count(DISTINCT (c.userid)) FROM {user} u, {course_completions} c WHERE u.id = c.id $sql) as graduated,
			(SELECT count(DISTINCT (e.userid)) FROM {user} u, {enrol} ee, {user_enrolments} e WHERE ee.id = e.enrolid AND e.userid=u.id $sql) as enrolled,
			(SELECT count(DISTINCT (e.userid)) FROM {user} u, {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'cohort' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_cohort,
			(SELECT count(DISTINCT (e.userid)) FROM {user} u, {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'manual' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_manual,
			(SELECT count(DISTINCT (e.userid)) FROM {user} u, {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'self' AND e.enrolid = ee.id AND e.userid=u.id $sql) as enrol_self");
	}

	function get_system_courses($params)
	{
		global $CFG, $DB;

		$sql1 = $this->get_teacher_sql($params, "course", "courses");
		$sql2 = $this->get_teacher_sql($params, "id", "courses");
		$sql3 = $this->get_teacher_sql($params, "cm.course", "courses");
		$sql4 = $this->get_teacher_sql($params, "userid", "users");

		return $DB->get_record_sql("SELECT
			(SELECT count(*) FROM {course_completions} WHERE timecompleted > 0 $sql1) as graduates,
			(SELECT count(*) FROM {course_modules} WHERE visible = 1 $sql1) as modules,
			(SELECT count(*) FROM {course} WHERE visible = 1 AND category > 0 $sql2) as visible,
			(SELECT count(*) FROM {course} WHERE visible = 0 AND category > 0 $sql2) as hidden,
			(SELECT count(DISTINCT (userid)) FROM {user_enrolments} WHERE status = 1 $sql4) as expired,
			(SELECT count(DISTINCT (userid)) FROM {role_assignments} WHERE roleid  IN ($this->learner_roles) $sql4) as students,
			(SELECT count(DISTINCT (userid)) FROM {role_assignments} WHERE roleid IN ($this->teacher_roles) $sql4) as tutors,
			(SELECT count(*) FROM {course_modules_completion} WHERE completionstate = 1 $sql4) as completed,
			(SELECT COUNT(DISTINCT (param)) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql4) as reviewed,
			(SELECT count(cm.id) FROM {course_modules} cm, {modules} m WHERE m.name = 'certificate' AND cm.module = m.id $sql3) as certificates");
	}

	function get_system_load($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");

		return $DB->get_record_sql("SELECT
			(SELECT sum(timespend) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) as sitetimespend,
			(SELECT sum(timespend) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) as coursetimespend,
            (SELECT sum(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) as activitytimespend,
			(SELECT sum(visits) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) as sitevisits,
			(SELECT sum(visits) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) as coursevisits,
            (SELECT sum(visits) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) as activityvisits");
	}

	function get_module_visits($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");
		$sql .= $this->get_filter_module_sql($params, "cm.");

		return $DB->get_records_sql("SELECT m.id, m.name, sum(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module $sql GROUP BY m.id");
	}
	function get_useragents($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.useragent as name, count(lit.id) AS amount FROM {local_intelliboard_tracking} lit WHERE lit.useragent != '' $sql GROUP BY lit.useragent");
	}
	function get_useros($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.useros as name, count(lit.id) AS amount FROM {local_intelliboard_tracking} lit WHERE lit.useros != '' $sql GROUP BY lit.useros");
	}
	function get_userlang($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "lit.userid", "users");

		return $DB->get_records_sql("SELECT lit.id, lit.userlang as name, count(lit.id) AS amount FROM {local_intelliboard_tracking} lit WHERE lit.userlang != '' $sql GROUP BY lit.userlang");
	}


	//update
	function get_module_timespend($params)
	{
		global $CFG, $DB;

		$sql0 = $this->get_teacher_sql($params, "userid", "users");
		$sql = $this->get_teacher_sql($params, "lit.userid", "users");
		$sql .= $this->get_filter_module_sql($params, "cm.");

		return $DB->get_records_sql("SELECT m.id, m.name, (sum(lit.timespend) / (SELECT sum(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql0)*100) as timeval, sum(lit.timespend) as timespend FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module $sql GROUP BY m.id");
	}

	function get_users_count($params)
	{
		global $CFG, $DB;


		$sql = $this->get_teacher_sql($params, "id", "users");
		$sql .= $this->get_filter_user_sql($params, "");

		return $DB->get_records_sql("SELECT auth, count(*) as users FROM {user} WHERE 1 $sql GROUP BY auth");
	}



	function get_most_visited_courses($params)
	{
		 global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "l.courseid", "courses");
		$sql .= $this->get_filter_course_sql($params, "c.");

		if($params->sizemode){
			$sql_columns = ", '-' as grade";
			$sql_join = "";
			$sql_order = "";
		}else{
			$sql_columns = ", gc.grade";
			$sql_join = "LEFT JOIN (".$this->getCourseGradeSql().") as gc ON gc.courseid = c.id";
			$sql_order = " ORDER BY visits DESC ";
		}

		return $DB->get_records_sql("SELECT c.id, c.fullname, sum(l.visits) as visits, sum(l.timespend) as timespend $sql_columns
				FROM {local_intelliboard_tracking} l
				LEFT JOIN {course} c ON c.id = l.courseid
				$sql_join
					WHERE c.category > 0 AND l.courseid > 0 $sql
						GROUP BY l.courseid
							$sql_order
								LIMIT 10");
	}
	function get_no_visited_courses($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		$sql .= $this->get_filter_course_sql($params, "c.");
		$sql2 = $this->get_filterdate_sql($params, "lastaccess");

		return $DB->get_records_sql("SELECT c.id, c.fullname, c.timecreated
					FROM  {course} c WHERE c.category > 0 AND c.id NOT IN (SELECT courseid FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql2 GROUP BY courseid) $sql LIMIT 10");
	}
	function get_active_users($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");
		$sql .= $this->get_filterdate_sql($params, "u.timecreated");
		$sql .= $this->get_filter_user_sql($params, "u.");
		$sql .= $this->get_filter_course_sql($params, "c.");
		$sql .= $this->get_filter_enrol_sql($params, "ue.");
		$sql .= $this->get_filter_enrol_sql($params, "e.");

		if($params->sizemode){
			$sql_order = "";
		}else{
			$sql_order = " ORDER BY lit.visits DESC ";
		}

		return $DB->get_records_sql("SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as name,
				u.lastaccess,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT e.courseid) as courses,
				lit.timespend, lit.visits
				FROM
					{user} u
						LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
		        		LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
		        		LEFT JOIN (SELECT l.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) lit ON lit.userid = u.id
					WHERE lit.visits > 0 $sql GROUP BY u.id $sql_order LIMIT 10");
	}

	function get_enrollments_per_course($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");
		$sql .= $this->get_filterdate_sql($params, "ue.timemodified");
		$sql .= $this->get_filter_course_sql($params, "c.");
		$sql .= $this->get_filter_enrol_sql($params, "ue.");
		$sql .= $this->get_filter_enrol_sql($params, "e.");


		return $DB->get_records_sql("SELECT c.id, c.fullname, count(DISTINCT ue.userid ) AS nums
			FROM
				{course} c,
				{enrol} e,
				{user_enrolments} ue
			WHERE e.courseid = c.id AND ue.enrolid = e.id $sql GROUP BY c.id LIMIT 0, 100"); // maximum
	}
	function get_size_courses($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");

		if($params->sizemode){
			return null;
		}else{
			return $DB->get_records_sql("SELECT c.id, c.timecreated, c.fullname, fs.coursesize, fm.modulessize
					FROM {course} c
						LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize FROM {files} f, {context} c WHERE c.id = f.contextid AND c.contextlevel = 50 GROUP BY c.instanceid) fs ON fs.course = c.id
						LEFT JOIN (SELECT cm.course, sum( f.filesize ) as modulessize FROM {course_modules} cm, {files} f, {context} ctx WHERE ctx.id = f.contextid AND ctx.instanceid = cm.id AND ctx.contextlevel = 70 GROUP BY cm.course) fm ON fm.course = c.id
							WHERE c.category > 0 $sql LIMIT 20");
		}

	}
	function get_active_ip_users($params, $limit = 10)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "u.id", "users");

		return $DB->get_records_sql("SELECT l.userid, l.userip, u.lastaccess as time, sum(l.visits) as visits, CONCAT( u.firstname, ' ', u.lastname ) AS name
					FROM {local_intelliboard_tracking} l,  {user} u
						WHERE u.id = l.userid AND l.lastaccess BETWEEN $params->timestart AND $params->timefinish $sql
							GROUP BY l.userid
								ORDER BY visits  DESC
									LIMIT 10");
	}

	function get_active_courses_per_day($params)
	{
		global $CFG, $DB;

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
					WHERE floor(timepoint / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timepoint / $ext) * $ext");
		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->courses;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_unique_sessions($params)
	{
		global $CFG, $DB;

		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 1){
			$ext = 3600; //by hour
		}elseif($days <= 45){
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
					WHERE floor(timepoint / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timepoint / $ext) * $ext");
		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->users;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_new_courses_per_day($params)
	{
		global $CFG, $DB;

		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 1){
			$ext = 3600; //by hour
		}elseif($days <= 45){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}

		$data = $DB->get_records_sql("SELECT floor(timecreated / $ext) * $ext as time, COUNT(id) as courses
				FROM {course}
					WHERE category > 0 AND floor(timecreated / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timecreated / $ext) * $ext");

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
		global $CFG, $DB;

		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 1){
			$ext = 3600; //by hour
		}elseif($days <= 45){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}
		$sql = $this->get_teacher_sql($params, "id", "users");

		$data = $DB->get_records_sql("SELECT floor(timecreated / $ext) * $ext as timepoint, COUNT(id) as users
				FROM {user}
					WHERE floor(timecreated / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish $sql
						GROUP BY floor(timecreated / $ext) * $ext");

		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->users;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_active_users_per_day($params)
	{
		global $CFG, $DB;

		$datediff = $params->timefinish - $params->timestart;
		$days = floor($datediff/(60*60*24)) + 1;

		if($days <= 45){
			$ext = 86400; //by day
		}elseif($days <= 90){
			$ext = 604800; //by week
		}elseif($days <= 365){
			$ext = 2592000; //by month
		}else{
			$ext = 31556926; //by year
		}

		$data = $DB->get_records_sql("SELECT floor(timepoint / $ext) * $ext as timepoint, SUM(visits) as users
				FROM {local_intelliboard_totals}
					WHERE floor(timepoint / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(timepoint / $ext) * $ext");



		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->users;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}

	function get_countries($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "id", "users");
		$sql .= $this->get_filter_user_sql($params, "");

		return $DB->get_records_sql("SELECT country, count(*) as users
				FROM {user} u
					WHERE country != '' $sql GROUP BY country");
	}
	function get_cohorts($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT id, name FROM {cohort} ORDER BY name");
	}
	function get_elisuset($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT id, name FROM {local_elisprogram_uset} ORDER BY name");
	}
	function get_totara_pos($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT id, fullname FROM {pos} WHERE visible = 1 ORDER BY fullname");
	}
	function get_scorm_user_attempts($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT DISTINCT b.attempt FROM {scorm} a, {scorm_scoes_track} b WHERE a.course = $params->courseid AND b.scormid = a.id AND b.userid = $params->userid");
	}
	function get_course_users($params)
	{
		global $CFG, $DB;

		$sql_filter = $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");

		return $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname FROM {user_enrolments} ue, {enrol} e, {user} u WHERE e.courseid = $params->courseid AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter");
	}

	function get_info($params){
		global $CFG, $DB;

		require_once($CFG->libdir.'/adminlib.php');

		return array('version' => get_component_version('local_intelliboard'));
	}
	function get_courses($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "c.id", "courses");


		$sql_filter = $this->get_filter_course_sql($params, "c.");
		$sql_limit = ($params->length or $params->start) ? "  LIMIT $params->start, $params->length" : "";

		if($params->filter){
			$sql_filter .= " AND " . $DB->sql_like('c.fullname', ":fullname", false, false);
			$this->params['fullname'] = "%$params->filter%";
		}


		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);
		if($params->custom){
			$sql_filter .= " AND c.id IN(SELECT distinct(e.courseid) FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid AND ue.userid IN ($params->custom))";
		}

		return $DB->get_records_sql("SELECT
				c.id,
				c.fullname,
				ca.id as cid,
				ca.name as category
			FROM {course} c, {course_categories} ca
			WHERE c.category = ca.id $sql $sql_filter ORDER BY c.fullname $sql_limit", $this->params);
	}


	function get_modules($params){
		global $CFG, $DB;

		$sql = "";
		if($params->custom){
			$sql = " AND name IN (SELECT itemmodule FROM {grade_items} GROUP BY itemmodule)";
		}
		return $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1 $sql");
	}
	function get_outcomes($params){
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT id, shortname, fullname FROM {grade_outcomes} WHERE courseid > 0");
	}
	function get_roles($params){
		global $CFG, $DB;

		if($params->filter){
			$sql = "'guest', 'frontpage'";
		}else{
			$sql = "'student', 'guest', 'user', 'frontpage'";
		}

		return $DB->get_records_sql("SELECT id, name, shortname
			FROM {role}
				WHERE archetype NOT IN ($sql)
					ORDER BY sortorder");
	}
	function get_roles_fix_name($params){
        $roles = role_fix_names(get_all_roles());
        return $roles;
	}
	function get_tutors($params){
		global $CFG, $DB;

		$params->filter = clean_param($params->filter, PARAM_INT);

		$filter = ($params->filter) ? "a.roleid = $params->filter" : "a.roleid IN ($this->teacher_roles)";
		return $DB->get_records_sql("SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) as name, u.email
			FROM {user} u
				LEFT JOIN {role_assignments} a ON a.userid = u.id
				WHERE $filter AND u.deleted = 0 AND u.confirmed = 1 GROUP BY u.id");
	}


	function get_cminfo($params){
		global $CFG, $DB;

		$module = $DB->get_record_sql("SELECT cm.id, cm.instance, m.name FROM {course_modules} cm, {modules} m WHERE m.id = cm.module AND cm.id = ".intval($params->custom));

		return $DB->get_record($module->name, array('id'=>$module->instance));
	}



	function get_enrols($params){
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT e.id, e.enrol FROM {enrol} e GROUP BY e.enrol");
	}

	function get_teacher_sql($params, $column, $type)
	{
		global $CFG, $DB;

		$sql = '';
		if(isset($params->userid) and $params->userid){
			if($type == "users"){
				$sql = " AND (
				$column IN(SELECT distinct(ra2.userid) as id FROM {role_assignments} AS ra
					JOIN {context} AS ctx ON ra.contextid = ctx.id
	                JOIN {role_assignments} AS ra2 ON ra2.contextid = ctx.id AND ra2.roleid in ($this->learner_roles)
					WHERE ra.userid = $params->userid AND ctx.contextlevel = 50 AND ra.roleid IN ($this->teacher_roles))
				OR
				$column IN (SELECT distinct(ra2.userid) as id FROM {role_assignments} AS ra
					JOIN {context} AS ctx ON ra.contextid = ctx.id
	    			JOIN {course} c ON c.category = ctx.instanceid
	    			JOIN {context} AS ctx2 ON  ctx2.instanceid = c.id AND ctx2.contextlevel = 50
	    			JOIN {role_assignments} AS ra2 ON ra2.contextid = ctx2.id AND ra2.roleid in ($this->learner_roles)
					WHERE ra.userid = $params->userid AND ctx.contextlevel = 40 AND ra.roleid IN ($this->teacher_roles))
				)";
			}elseif($type == "courses"){
				$sql = "AND (
				$column IN(SELECT distinct(ctx.instanceid) as id FROM {role_assignments} AS ra
					JOIN {context} AS ctx ON ra.contextid = ctx.id
					WHERE ra.userid = $params->userid AND ctx.contextlevel = 50 AND ra.roleid IN ($this->teacher_roles))
				OR
				$column IN(SELECT distinct(c.id) as id FROM {role_assignments} AS ra
					JOIN {context} AS ctx ON ra.contextid = ctx.id
					JOIN {course} c ON c.category = ctx.instanceid
					WHERE ra.userid = $params->userid AND ctx.contextlevel = 40 AND ra.roleid IN ($this->teacher_roles))
				)";
			}
		}
		return $sql;
	}


	function get_learner($params){
		global $CFG, $DB;

		if($params->userid){
			$user = $DB->get_record_sql("SELECT
				u.*,
				cx.id as context,
				count(c.id) as completed,
				gc.grade,
				lit.timespend_site, lit.visits_site,
				lit2.timespend_courses, lit2.visits_courses,
				lit3.timespend_modules, lit3.visits_modules,
				(SELECT count(*) FROM {course} WHERE visible = 1 AND category > 0) as available_courses
				FROM {user} u
					LEFT JOIN {course_completions} c ON c.timecompleted > 0 AND c.userid = u.id
					LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
					LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = $params->userid) as gc ON gc.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_site, sum(visits) as visits_site FROM {local_intelliboard_tracking} WHERE userid = $params->userid) lit ON lit.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_courses, sum(visits) as visits_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 AND userid = $params->userid) lit2 ON lit2.userid = u.id
					LEFT JOIN (SELECT userid, sum(timespend) as timespend_modules, sum(visits) as visits_modules FROM {local_intelliboard_tracking} WHERE page = 'module' AND userid = $params->userid) lit3 ON lit3.userid = u.id
				WHERE u.id = $params->userid");

			if($user->id){
				$user->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
				(SELECT
						round(avg(b.timespend_site),0) as timespend_site,
						round(avg(b.visits_site),0) as visits_site
					FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site
						FROM {local_intelliboard_tracking}
						WHERE userid NOT IN (SELECT distinct userid FROM {role_assignments} WHERE roleid NOT  IN ($this->learner_roles)) and userid != $user->id
						GROUP BY userid) as b) a,
					(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
					FROM {grade_items} gi, {grade_grades} g
					WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND
					g.userid NOT IN (SELECT distinct userid FROM {role_assignments} WHERE roleid NOT  IN ($this->learner_roles)) and g.userid != $user->id GROUP BY g.userid) b) c");


				$user->data = $DB->get_records_sql("SELECT uif.id, uif.name, uid.data
						FROM
							{user_info_field} uif,
							{user_info_data} uid
						WHERE uif.id = uid.fieldid and uid.userid = $user->id
						ORDER BY uif.name");

				$user->grades = $DB->get_records_sql("SELECT g.id, gi.itemmodule, round(AVG( (g.finalgrade/g.rawgrademax)*100),2) AS grade
						FROM
							{grade_items} gi,
							{grade_grades} g
						WHERE  gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL and g.userid = $user->id
						GROUP BY gi.itemmodule ORDER BY g.timecreated DESC");

				$user->courses = $DB->get_records_sql("SELECT
					ue.id,
					ue.userid,
					round(((cmc.completed/cmm.modules)*100), 0) as completion,
					c.id as cid,
					c.fullname
							FROM {user_enrolments} as ue
								LEFT JOIN {enrol} as e ON e.id = ue.enrolid
								LEFT JOIN {course} as c ON c.id = e.courseid
								LEFT JOIN {course_completions} as cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
								LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
								LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
							WHERE ue.userid = $user->id GROUP BY e.courseid ORDER BY c.fullname LIMIT 0, 100");
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
		global $CFG, $DB;

		$params->filter = clean_param($params->filter, PARAM_SEQUENCE);

		$users = $DB->get_records_sql("SELECT u.id,u.firstname,u.lastname,u.email,u.firstaccess,u.lastaccess,cx.id as context, gc.average, ue.courses, c.completed, round(((c.completed/ue.courses)*100), 0) as progress
			FROM {user} u
			LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
			LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
			LEFT JOIN (".$this->getLearnerCoursesSql().") as ue ON ue.userid = u.id
			LEFT JOIN (SELECT userid, count(id) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY userid) as c ON c.userid = u.id
			WHERE u.deleted = 0 and u.id IN ($params->filter)");
		return $users;
	}
	function get_learner_courses($params){
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT c.id, c.fullname
							FROM {user_enrolments} as ue
								LEFT JOIN {enrol} as e ON e.id = ue.enrolid
								LEFT JOIN {course} as c ON c.id = e.courseid
							WHERE ue.userid = $params->userid GROUP BY e.courseid  ORDER BY c.fullname ASC");

	}
	function get_course($params)
	{
		global $CFG, $DB;

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
				FROM {course} as c
					LEFT JOIN {course_categories} as ca ON ca.id = c.category
					LEFT JOIN (SELECT course, count( id ) AS modules FROM {course_modules} WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
					LEFT JOIN (SELECT gi.courseid, count(g.id) AS grades FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gr ON gr.courseid = c.id
					LEFT JOIN (SELECT course, count(*) as sections FROM {course_sections} where visible = 1 group by course) as s ON s.course = c.id
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
							FROM {local_intelliboard_tracking}
							WHERE userid NOT IN (SELECT distinct userid FROM {role_assignments} WHERE roleid NOT  IN ($this->learner_roles)) and courseid != $course->id
							GROUP BY courseid) as b) a,
						(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
						FROM {grade_items} gi, {grade_grades} g
						WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid != $course->id GROUP BY gi.courseid) b) c");

			$course->mods = $DB->get_records_sql("SELECT m.id, m.name, count( cm.id ) AS size FROM {course_modules} cm, {modules} m WHERE cm.visible = 1 and m.id = cm.module and cm.course = 2 GROUP BY cm.module");


			$course->teachers = $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as name, u.email, cx.id as context  FROM {user} as u
								LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
								LEFT JOIN {role_assignments} AS ra ON u.id = ra.userid
								LEFT JOIN {context} AS ctx ON ra.contextid = ctx.id
								LEFT JOIN {course} as c ON c.id = ctx.instanceid
									WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND c.id IN ($params->courseid)
										GROUP BY u.id");
		}
		return $course;
	}
	function get_activity_learners($params)
	{
		global $CFG, $DB;

		$params->filter = clean_param($params->filter, PARAM_SEQUENCE);

		$completions = $DB->get_records_sql("SELECT cc.id, cc.timecompleted, c.id as cid, c.fullname as course
					FROM {course_completions} cc
						LEFT JOIN {course} c ON c.id = cc.course
						LEFT JOIN {user} u ON u.id = cc.userid
							WHERE cc.timecompleted BETWEEN $params->timestart AND $params->timefinish AND cc.userid IN ($params->filter) ORDER BY cc.timecompleted DESC LIMIT 10");

		$enrols = $DB->get_records_sql("SELECT ue.id, ue.timecreated, c.id as cid, c.fullname as course
					FROM {user_enrolments} ue
						LEFT JOIN {enrol} e ON e.id = ue.enrolid
						LEFT JOIN {course} c ON c.id = e.courseid
						LEFT JOIN {user} u ON u.id = ue.userid
							WHERE ue.timecreated BETWEEN $params->timestart AND $params->timefinish AND ue.userid IN ($params->filter) GROUP BY ue.userid, e.courseid ORDER BY ue.timecreated DESC LIMIT 10");

		$grades = $DB->get_records_sql("SELECT g.id, round(((g.finalgrade/g.rawgrademax)*100),0) AS grade, gi.courseid, gi.itemname, c.fullname as course, g.timecreated
					FROM
						{grade_items} gi,
						{grade_grades} g,
						{course} c,
						{user} u
				WHERE g.timecreated BETWEEN $params->timestart AND $params->timefinish AND g.userid IN ($params->filter) AND gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid ORDER BY g.timecreated DESC LIMIT 10");

		return array("enrols"=>$enrols, "grades"=>$grades, "completions"=>$completions);
	}

	function get_learner_visits_per_day($params)
	{
		global $CFG, $DB;


		$ext = 86400;

		$params->filter = clean_param($params->filter, PARAM_SEQUENCE);

		$sql_filter = "";
		if($params->courseid){
			$sql_filter = "t.courseid  IN ($params->courseid) AND ";
		}
		$data = $DB->get_records_sql("SELECT floor(l.timepoint / $ext) * $ext as timepoint, sum(l.visits) as visits
				FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id AND $sql_filter t.userid IN ($params->filter) AND floor(l.timepoint / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(l.timepoint / $ext) * $ext");


		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->visits;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}
	function get_course_visits_per_day($params)
	{
		global $CFG, $DB;

		$sql_user = ($params->userid) ? " AND t.userid=$params->userid":"";
		$ext = 86400;

		$data = $DB->get_records_sql("SELECT floor(l.timepoint / $ext) * $ext as timepoint, SUM(l.visits) as visits
				FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id $sql_user AND t.courseid  IN ($params->courseid) AND floor(l.timepoint / $ext) * $ext BETWEEN $params->timestart AND $params->timefinish
						GROUP BY floor(l.timepoint / $ext) * $ext");

		$response = array();
		foreach($data as $item){
			$response[] = $item->timepoint.'.'.$item->visits;
		}
		$obj = new stdClass();
		$obj->id = 0;
		$obj->data = implode(',', $response);
		return $obj;
	}


	function get_userinfo($params){
		global $CFG, $DB;

		$params->filter = clean_param($params->filter, PARAM_INT);

		return $DB->get_record_sql("SELECT u.*, cx.id as context
			FROM {user} u
				LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
			WHERE u.id = ".$params->filter);
	}
	function get_user_info_fields_data($params)
	{
		global $CFG, $DB;

		$params->filter = clean_param($params->filter, PARAM_SEQUENCE);
		$params->custom = clean_param($params->custom, PARAM_SEQUENCE);

		$sql = "";
		$sql .= ($params->filter) ? " AND fieldid IN ($params->filter)":"";
		$sql .= ($params->custom) ? " AND userid IN ($params->custom)":"";

		return $DB->get_records_sql("SELECT id, fieldid, data, count(id) as items FROM {user_info_data}  WHERE data != '' $sql GROUP BY data ORDER BY data ASC");
	}
	function get_user_info_fields($params)
	{
		global $CFG, $DB;

		return $DB->get_records_sql("SELECT uif.id, uif.name, uic.name as category FROM {user_info_field} uif, {user_info_category} uic WHERE uif.categoryid = uic.id ORDER BY uif.name");
	}
	function get_reportcard($params)
	{
		global $CFG, $DB, $SITE;

		$data = array();
		$data['stats'] = $DB->get_record_sql("SELECT
			(SELECT count(distinct e.courseid) FROM {user_enrolments} ue, {enrol} e WHERE e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) as courses,
			(SELECT count(distinct course) FROM {course_completions} WHERE timecompleted > 0 and userid = $params->userid) as completed,
			(SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible = 1 AND cm.course in (SELECT distinct e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) AND cm.visible=1 AND cm.completionexpected< ".time()." AND cm.completionexpected>0 AND (cmc.id IS NULL OR cmc.completionstate=0)) as missed,
			(SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm WHERE cm.course IN (SELECT distinct e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) AND cm.visible=1 AND cm.completionexpected>0) as current,
			(SELECT count(id) FROM {quiz} WHERE course in (SELECT distinct e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = $params->userid) and id NOT IN (SELECT quiz FROM {quiz_grades} WHERE userid = $params->userid AND grade > 0)) as quizes
		");

		$timestart = strtotime('today');
		$timefinish = $timestart + 86400;

		$data['courses'] = $DB->get_records_sql("SELECT c.id, c.fullname, a.assignments, b.missing, t.quizes, cc.timecompleted, g.grade
				FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = $params->userid
				LEFT JOIN (SELECT gi.courseid, (g.finalgrade/g.rawgrademax)*100 as grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = $params->userid GROUP BY gi.courseid) g ON g.courseid = c.id
				LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as missing FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible=1 AND cm.completionexpected < ".time()."  AND cm.completionexpected > 0 AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) b ON b.course = c.id
				LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as assignments FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=$params->userid WHERE cm.visible=1 AND cm.completionexpected BETWEEN ".(time()-86400)." AND ".time()." AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) a ON a.course = c.id
				LEFT JOIN (SELECT course, count(id) as quizes FROM {quiz} WHERE id NOT IN (SELECT quiz FROM {quiz_grades} WHERE userid = $params->userid AND grade > 0) GROUP BY course) t ON t.course = c.id
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
	function get_dashboard_avg($params)
	{
		global $CFG, $DB;

		return $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
						(SELECT round(avg(b.timespend_site),0) as timespend_site, round(avg(b.visits_site),0) as visits_site
							FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site
								FROM {local_intelliboard_tracking}
								WHERE userid NOT IN (SELECT distinct userid FROM {role_assignments} WHERE roleid NOT  IN ($this->learner_roles)) and userid != 2 GROUP BY userid) as b) a,
						(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
							FROM {grade_items} gi, {grade_grades} g
							WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) b) c");
	}
	function get_dashboard_countries($params)
	{
		global $CFG, $DB;
		$sql = $this->get_teacher_sql($params, "id", "users");
		$sql .= $this->get_filter_user_sql($params, "");

		return $DB->get_records_sql("SELECT country, count(*) as users FROM {user} WHERE country != '' $sql GROUP BY country");
	}
	function get_dashboard_enrols($params)
	{
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql .= $this->get_filter_enrol_sql($params, "ue.");
		$sql .= $this->get_filter_enrol_sql($params, "e.");

		return $DB->get_records_sql("SELECT e.id, e.enrol, count(ue.id) as enrols FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql GROUP BY e.enrol");
	}
	function get_dashboard_info($params)
	{
		global $CFG, $DB;

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
					WHERE timepoint BETWEEN $timestart AND $timefinish
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
		global $CFG, $DB;

		$sql = $this->get_teacher_sql($params, "userid", "users");
		$timeyesterday = strtotime('yesterday');
		$timelastweek = strtotime('last week');
		$timetoday = strtotime('today');
		$timeweek = strtotime('previous monday');
		$timefinish = time();

		$data = array();
		if($params->sizemode){
			$data[] = array();
		}else{
			$data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timeyesterday AND $timetoday) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timelastweek AND $timeweek) as sessions_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timeyesterday AND $timetoday $sql) as enrolments_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timelastweek AND $timeweek $sql) as enrolments_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timeyesterday AND $timetoday $sql) as compl_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timelastweek AND $timeweek $sql) as compl_week");
		}
		$data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timetoday AND $timefinish) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN $timeweek AND $timefinish) as sessions_week,
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
	function count_records($sql,$unique_id = 'id',$params=array())
	{
	    global $DB;
        if(strpos($sql,"LIMIT") !== false)
            $sql = strstr($sql,"LIMIT",true);

        $sql = "SELECT COUNT(cou.$unique_id) FROM (".$sql.") cou";
        return $DB->count_records_sql($sql,$params);
	}
	/**
	* parse feedback to needed view
	* @param object $v - row from feedback table
	*/
	public function parseFeedbackAnswer($v){
		if($v->typ == 'multichoice'){
			$a = explode('|',$v->presentation);
			$a[0] = trim( explode('>>>>>',$a[0])[1] );
		}
		if($v->typ == 'singleselect'){
			$a = explode('|',$v->presentation);
			$a[0] = trim( explode('>>>>>',$a[0])[1] );
		}
		if($v->typ == 'multichoicerated'){
			$a = explode('####',$v->presentation);
			array_shift($a);
			foreach($a as $k=>$item){
				$a[$k] = explode('|',$item)[0];
			}
		}
		if(in_array($v->typ, ["multichoice", "multichoicerated", "singleselect"]) === true){
			$ans = explode('|', $v->answer);
			$v->answer = [];
			foreach ($ans as $key => $value){
				if(isset($v->answer) and isset($a[$value-1]))
					array_push($v->answer, $a[$value-1]);
			}
			$v->answer = implode("\n", $v->answer);
		}
		return $v;
	}
}
