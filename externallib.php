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

require_once($CFG->libdir . "/externallib.php");

class local_intelliboard_external extends external_api {

	public $params = array();
	public $prfx = 0;

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

		//Available functions
		$functions = array('report1','report2','report3','report4','report5','report6','report7','report8','report9','report10','report11','report12','report13','report14','report15','report16','report17','report18','report19','report20','report21','report22','report23','report24','report25','report26','report27','report28','report29','report30','report31','report32','get_scormattempts','report86','get_competency','report33','report34','report35','report36','report37','report38','report39','report40','report41','report43','report44','report45','report42','report46','report47','report58','report66','report72','report73','report75','report76','report77','report79','report80','report81','report82','report83','report84','report85','report86','report87','report88','report89','report90','report91','report92','report93','report94','report95','report96','report97','report98','report99','report99_graph','report78','report74','report71','report70','report67','report68','report69','get_max_attempts','report56','analytic1','analytic2','get_quizes','analytic3','analytic4','analytic5','analytic5table','analytic6','analytic7','analytic7table','analytic8','analytic8details','get_course_instructors','get_course_discussions','get_cohort_users','get_users','get_grade_letters','get_questions','get_activity','get_total_info','get_system_users','get_system_courses','get_system_load','get_module_visits','get_useragents','get_useros','get_userlang','get_module_timespend','get_users_count','get_most_visited_courses','get_no_visited_courses','get_active_users','get_enrollments_per_course','get_size_courses','get_active_ip_users','get_active_courses_per_day','get_unique_sessions','get_new_courses_per_day','get_users_per_day','get_active_users_per_day','get_countries','get_cohorts','get_elisuset','get_totara_pos','get_scorm_user_attempts','get_course_users','get_info','get_courses','get_modules','get_outcomes','get_roles','get_roles_fix_name','get_tutors','get_cminfo','get_enrols','get_teacher_sql','get_learner','get_learners','get_learner_courses','get_course','get_activity_learners','get_learner_visits_per_day','get_course_visits_per_day','get_userinfo','get_user_info_fields_data','get_user_info_fields','get_reportcard','get_dashboard_avg','get_dashboard_countries','get_dashboard_enrols','get_dashboard_info','get_dashboard_stats','set_notification_enrol','set_notification_auth','count_records','parseFeedbackAnswer','analytic9','get_course_sections','get_course_user_groups','get_all_system_info');

		$function = (isset($params->function)) ? $params->function : '';
		if(in_array($function, $functions)){
			$data = $obj->{$function}($params);
		}else{
			$data = null;
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
	private function get_limit_sql($params)
	{
		return (isset($params->start) and $params->length != 0 and $params->length != -1) ? "LIMIT $params->start, $params->length" : "";
	}
	private function get_order_sql($params, $columns)
	{
		return (isset($params->order_column) and isset($columns[$params->order_column]) and $params->order_dir) ? "ORDER BY ".$columns[$params->order_column]." $params->order_dir" : "";
	}
	private function get_filter_course_sql($params, $prefix)
	{
		return ($params->filter_course_visible) ? "" : " AND {$prefix}visible = 1";
	}
	private function get_filter_enrol_sql($params, $prefix)
	{
		return ($params->filter_enrol_status) ? "" : " AND {$prefix}status = 0";
	}
	private function get_filter_enrolled_users_sql($params, $column)
	{
		return ($params->filter_enrolled_users) ? " AND {$column} IN (SELECT DISTINCT userid FROM {user_enrolments})" : "";
	}
	private function get_filter_module_sql($params, $prefix)
	{
		return ($params->filter_module_visible) ? "" : " AND {$prefix}visible = 1";
	}
	private function get_filter_user_sql($params, $prefix)
	{
		$filter = ($params->filter_user_deleted) ? "" : " AND {$prefix}deleted = 0";
		$filter .= ($params->filter_user_suspended) ? "" : " AND {$prefix}suspended = 0";
		$filter .= ($params->filter_user_guest) ? "" : " AND {$prefix}username <> 'guest'";
		return $filter;
	}
	private function get_filter_columns($params)
	{
		if(!empty($params->columns)){
			$data = array();
			$columns = explode(",", $params->columns);
			foreach($columns as $column){
				$data[] = "field$column"; // {$column} defined in each report
			}
			return $data;
		}else{
			return array();
		}
	}
	private function get_columns($params, $field = "u.id")
	{
		if(!empty($params->columns)){
			$data = "";
			$columns = explode(",", $params->columns);
			foreach($columns as $column){
				$key = "column$column";
				$this->params[$key] = $column;
				$data .= ", (SELECT d.data FROM {user_info_data} d, {user_info_field} f WHERE f.id = :$key AND d.fieldid = f.id AND d.userid = $field) AS field$column";
			}
			return $data;
		}else{
			return "";
		}
	}
	private function get_filter_sql($params, $columns)
	{
		global $DB;

		$filter = "";
		//Filter by report columns
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
		//Filter by User profile fields
		if($params->filter_profile){
			$params->custom3 = clean_param($params->custom3, PARAM_SEQUENCE);
			if($params->custom3 and !empty($params->columns)){
				$cols = explode(",", $params->columns);
				$fields = $DB->get_records_sql("SELECT id, fieldid, data FROM {user_info_data} WHERE id IN ($params->custom3)");
				$fields_filter = array();
				foreach($fields as $i => $field){
					if(in_array($field->fieldid, $cols)){
						$field->fieldid = (int)$field->fieldid; //fieldid -> int
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

	private function get_filterdate_sql($params, $column)
    {
        if($params->timestart and $params->timefinish){
        	$this->prfx = $this->prfx + 1;
        	$timestart = 'tmstart'.$this->prfx;
        	$timefinish = 'tmfinish'.$this->prfx;
            $this->params[$timestart] = $params->timestart;
            $this->params[$timefinish] = $params->timefinish;

            return " AND $column BETWEEN :$timestart AND :$timefinish ";
        }
        return "";
    }
	private function get_filter_in_sql($sequence, $column, $sep = true, $equal = true)
	{
		global $DB;

		if($sequence){
			$items = explode(",", clean_param($sequence, PARAM_SEQUENCE));
			if(!empty($items)){
				$this->prfx = $this->prfx + 1;
				$key = clean_param($column.$this->prfx, PARAM_ALPHANUM);
				list($sql, $params) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED, $key, $equal);
				$this->params = array_merge($this->params, $params);
				return ($sep) ? " AND $column $sql ": " $column $sql ";
			}
		}
		return '';
	}
	private function get_report_data($query, $params, $wrap = true)
	{
		global $DB;

		if(isset($params->start) and $params->length != 0 and $params->length != -1){
			$data = $DB->get_records_sql($query, $this->params, $params->start, $params->length);
		}else{
			$data = $DB->get_records_sql($query, $this->params);
		}
		return ($wrap) ? array("data" => $data) : $data;
	}
	private function get_modules_sql($filter)
	{
		global $DB;

		$list = clean_param($filter, PARAM_SEQUENCE);
		$sql_mods = $this->get_filter_in_sql($list, "m.id");
		$sql_cm_end = ""; $sql_cm_if = array();
		$modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods", $this->params);
		foreach($modules as $module){
			$sql_cm_if[] = "IF(m.name='{$module->name}', (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
			$sql_cm_end .= ")";
		}
		return ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";
	}

	public function report1($params)
	{
		$columns = array_merge(array("u.firstname","u.lastname", "u.email", "c.fullname", "enrols", "l.visits", "l.timespend", "grade", "cc.timecompleted","ue.timecreated", "ul.timeaccess", "ue.timeend", "cc.timecompleted","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
		$sql_join_filter = ""; $sql_mode = 0;

		$sql_join = "";
		if(isset($params->custom) and  strrpos($params->custom, ',') !== false){
			$sql_filter .= $this->get_filter_in_sql($params->custom, "u.id");
			$sql_filter_column = "ue.timecreated";
		}elseif(isset($params->custom) and $params->custom == 2 and !$params->sizemode){
			$sql_filter_column = "l.timepoint";
			$sql_mode = 1;
		}elseif(isset($params->custom) and $params->custom == 1){
			$sql_filter_column = "cc.timecompleted";
		}else{
			$sql_filter_column = "ue.timecreated";
		}
		if($sql_mode){
			$sql_join_filter .= $this->get_filterdate_sql($params, "$sql_filter_column");
		}else{
			$sql_filter .= $this->get_filterdate_sql($params, "$sql_filter_column");
		}
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		if($params->cohortid){
			$sql_join .= " LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");
		}
		if($params->sizemode){
			$sql_columns .= ", '0' AS timespend, '0' AS visits";
		}elseif($sql_mode){
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join .= " LEFT JOIN (SELECT t.id,t.userid,t.courseid, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits FROM
								{local_intelliboard_tracking} t,
								{local_intelliboard_logs} l
							WHERE l.trackid = t.id $sql_join_filter GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
		}else{
			$sql_columns .= ", l.timespend, l.visits";
			$sql_join .= " LEFT JOIN (SELECT t.userid,t.courseid, SUM(t.timespend) AS timespend, SUM(t.visits) AS visits FROM
								{local_intelliboard_tracking} t GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
		}

		return $this->get_report_data("
			SELECT ue.id,
				ue.timecreated AS enrolled,
				ue.timeend,
				ul.timeaccess,
				ROUND(((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				c.enablecompletion,
				cc.timecompleted AS complete,
				u.id AS uid,
				u.email,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				u.firstname,
				u.lastname,
				e.enrol AS enrols,
				c.id AS cid,
				c.fullname AS course,
				c.timemodified AS start_date
				$sql_columns
			FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
        		LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
				$sql_join
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
	}

	public function report2($params)
	{
		$columns = array_merge(array("course", "learners", "modules", "completed", "l.visits", "l.timespend", "grade", "c.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);

		if($params->sizemode){
			$sql_columns = ", '0' AS timespend, '0' AS visits";
			$sql_join = "";
		}else{
			$sql_columns = ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY courseid) l ON l.courseid = c.id";
		}

		return $this->get_report_data("
			SELECT c.id,
				c.fullname AS course,
				c.timecreated AS created,
				c.enablecompletion,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT cc.userid) AS completed,
				COUNT(DISTINCT ue.userid) AS learners,
				cm.modules
				$sql_columns
			FROM {course} c
				LEFT JOIN {enrol} e ON e.courseid = c.id
				LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
				LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = c.id AND cc.userid = ue.userid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
		        LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
		        LEFT JOIN (SELECT course, COUNT(id) AS modules FROM {course_modules} WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
		        $sql_join
			WHERE 1 $sql_filter GROUP BY c.id $sql_having $sql_order", $params);
	}
	public function report3($params)
	{
		$columns = array_merge(array("activity", "m.name", "completed", "visits", "timespend", "grade", "cm.added"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filterdate_sql($params, "cm.added");
		$sql_filter .= $this->get_filter_module_sql($params, "cm.");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.course");
		$sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
		$sql_columns = $this->get_modules_sql($params->custom);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_join = "";

		if($params->sizemode){
			$sql_columns .= ",'0' as grade, '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", round((g.finalgrade/g.rawgrademax)*100, 0) AS grade, l.timespend as timespend, l.visits as visits";
			$sql_join .= " LEFT JOIN (SELECT lit.param, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.page = 'module' GROUP BY lit.param) l ON l.param = cm.id";
			$sql_join .= " LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.iteminstance = cm.instance
						LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.finalgrade IS NOT NULL";
		}

		return $this->get_report_data("
			SELECT
				cm.id,
				m.name AS module,
				m.name AS moduletype,
				cm.added,
				cm.completion,
				c.fullname,
				COUNT(DISTINCT cmc.id) AS completed
				$sql_columns
			FROM {course_modules} cm
				LEFT JOIN {modules} m ON m.id = cm.module
				LEFT JOIN {course} c ON c.id = cm.course
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 $sql_join
			WHERE 1 $sql_filter GROUP BY cm.id $sql_having $sql_order", $params);
	}
	public function report4($params)
	{
		$columns = array_merge(array("u.firstname","u.lastname","u.email","registered","courses","cmc.completed_activities","completed_courses","lit.visits","lit.timespend","grade", "u.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} chm ON chm.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "chm.cohortid");
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
				$sql_join .= " LEFT JOIN (SELECT id,userid, SUM(timespend) as timespend, SUM(visits) as visits FROM
							{local_intelliboard_tracking}
						WHERE courseid > 0 GROUP BY userid) as lit ON lit.userid = u.id";
			}else{
				$sql_columns .= ", lit.timespend, lit.visits";
				$sql_join .= " LEFT JOIN (SELECT t.id,t.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM
							{local_intelliboard_tracking} t,
							{local_intelliboard_logs} l
						WHERE l.trackid = t.id AND t.courseid > 0 $sql_join_filter GROUP BY t.userid) as lit ON lit.userid = u.id";
			}
		}
		return $this->get_report_data("
			SELECT u.id,
				u.firstname,
				u.lastname,
				u.email,
				u.lastaccess,
				u.timecreated as registered,
				round(AVG((g.finalgrade/g.rawgrademax)*100), 2) as grade,
				COUNT(DISTINCT e.courseid) as courses,
				COUNT(DISTINCT cc.id) as completed_courses,
				cmc.completed_activities
				$sql_columns
			FROM {user} u
				LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = u.id AND cc.timecompleted > 0
				LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL
				LEFT JOIN (SELECT userid, count(id) as completed_activities FROM {course_modules_completion} WHERE completionstate = 1 GROUP BY userid) cmc ON cmc.userid = u.id $sql_join
			WHERE 1 $sql_filter GROUP BY u.id $sql_having $sql_order", $params);
	}


	public function report5($params)
	{
		$columns = array_merge(array("teacher","courses","ff.videos","l1.urls","l0.evideos","l2.assignments","l3.quizes","l4.forums","l5.attendances"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");


		return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as teacher,
				COUNT(DISTINCT ctx.instanceid) as courses,
				f1.files,
				ff.videos,
				l1.urls,
				l0.evideos,
				l2.assignments,
				l3.quizes,
				l4.forums,
				l5.attendances
				$sql_columns
			FROM {role_assignments}  ra
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				LEFT JOIN {user} u ON u.id = ra.userid
				LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) files FROM {files} f WHERE filearea = 'content' GROUP BY f.userid) as f1 ON f1.userid = u.id
				LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) urls FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'url' AND l.action = 'created' GROUP BY l.userid) as l1 ON l1.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'page' AND l.action = 'created'GROUP BY l.userid) as l0 ON l0.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'assignment' AND l.action = 'created'GROUP BY l.userid) as l2 ON l2.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'quiz' AND l.action = 'created'GROUP BY l.userid) as l3 ON l3.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) forums FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'forum' AND l.action = 'created'GROUP BY l.userid) as l4 ON l4.userid = u.id
				LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM {logstore_standard_log} l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'attendance' AND l.action = 'created'GROUP BY l.userid) as l5 ON l5.userid = u.id
			WHERE 1 $sql_filter GROUP BY ra.userid $sql_having $sql_order", $params);
	}
	public function report6($params)
	{
		$columns = array_merge(array("u.firstname", "u.lastname", "email", "c.fullname", "started", "grade", "grade", "completed", "grade", "complete", "visits", "timespend"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "e.courseid");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");


		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
		}

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits, '0' as average, '0' as completed";
		}else{
			$sql_columns .= ", cmc.completed AS completed, git.average AS average, lit.timespend AS timespend, lit.visits AS visits";
			$sql_join .= "
					LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY courseid, userid) lit ON lit.courseid = c.id AND lit.userid = u.id
					LEFT JOIN (SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), 0) AS average
						FROM {grade_items} gi, {grade_grades} g
						WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
						GROUP BY gi.courseid) git ON git.courseid=c.id
					LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(DISTINCT cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 AND cm.completion > 0 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id";
		}

		return $this->get_report_data("
			SELECT ue.id,
				cri.gradepass,
				u.email,
				ue.userid,
				ue.timecreated as started,
				c.id as cid,
				c.fullname,
				AVG((g.finalgrade/g.rawgrademax)*100) AS grade,
				u.firstname,
				u.lastname,
				c.enablecompletion,
				cc.timecompleted as complete
				$sql_columns
			FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {course_completion_criteria} cri ON cri.course = e.courseid AND cri.criteriatype = 6
				LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
				$sql_join
				WHERE 1 $sql_filter GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
	}
	public function report7($params)
	{
		$columns = array_merge(array("u.firstname","u.lastname","email", "course", "visits", "participations", "assignments", "grade"), $this->get_filter_columns($params));

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "e.courseid");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");

		if($params->sizemode){
			$sql_columns .= ", '0' as grade";
			$sql_join = "";
		}else{
			$sql_columns .= ", gc.grade AS grade";
			$sql_join = "
					LEFT JOIN (SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), 0) AS grade
					FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
					GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id";
		}

		return $this->get_report_data("
			SELECT ue.id, ue.userid,u.email,
				((cmca.cmcnuma / cma.cmnuma)*100 ) as assignments,
				((cmc.cmcnums / cmx.cmnumx)*100 ) as participations,
				((COUNT(DISTINCT lit.id) / cm.cmnums)*100 ) as visits,
				cma.cmnuma as assigns,
				c.fullname as course,
				u.firstname,
				u.lastname
				$sql_columns
			FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {local_intelliboard_tracking} lit ON lit.courseid = c.id AND lit.page = 'module' AND lit.userid = u.id
				LEFT JOIN (SELECT course, count(id) as cmnums FROM {course_modules} WHERE visible = 1 GROUP BY course) as cm ON cm.course = c.id
				LEFT JOIN (SELECT course, count(id) as cmnumx FROM {course_modules} WHERE visible = 1 and completion > 0 GROUP BY course) cmx ON cmx.course = c.id
				LEFT JOIN (SELECT course, count(id) as cmnuma FROM {course_modules} WHERE visible = 1 and module = 1 GROUP BY course) cma ON cma.course = c.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
				$sql_join
			WHERE 1 $sql_filter GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
	}
	public function report8($params)
	{
		$columns = array_merge(array("teacher","courses","learners","activelearners","completedlearners","grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");

		$sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
		$sql2 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
		$sql3 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

		$this->params['tx1'] = strtotime('-30 days');
		$this->params['tx2'] = time();

		return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) teacher,
				COUNT(DISTINCT ctx.instanceid) as courses,
				SUM(l.learners) as learners,
				SUM(l1.activelearners) as activelearners,
				SUM(cc.completed) as completedlearners,
				AVG(g.grade) as grade
				$sql_columns
			FROM {user} u
				LEFT JOIN {role_assignments} AS ra ON ra.userid = u.id
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
				LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as learners FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ctx.instanceid) AS l ON l.instanceid = ctx.instanceid
				LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as activelearners FROM {role_assignments} ra, {user} u, {context} ctx
					WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND u.id = ra.userid AND u.lastaccess BETWEEN :tx1 AND :tx2 AND u.deleted = 0 AND u.suspended = 0 $sql2 GROUP BY ctx.instanceid) AS l1 ON l1.instanceid = ctx.instanceid
				LEFT JOIN (SELECT course, count(id) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) cc ON cc.course = ctx.instanceid
				LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) g ON g.courseid = ctx.instanceid
			WHERE ctx.contextlevel = 50 $sql3 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
	}
	public function report9($params)
	{
		$columns = array_merge(
			array(
				"q.name",
				"c.fullname",
				"ql.questions",
				"attempts",
				"q.timeopen",
				"duration",
				"grade",
				"q.timemodified"),
			$this->get_filter_columns($params)
		);

		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "q.course");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");

		return $this->get_report_data("
			SELECT q.id,
				q.name,
				q.course,
				c.fullname,
				ql.questions,
				q.timemodified,
				q.timeopen,
				q.timeclose,
				avg((qa.sumgrades/q.sumgrades)*100) AS grade,
				count(distinct(qa.id)) AS attempts,
				sum(qa.timefinish - qa.timestart) AS duration
			FROM {quiz} q
				LEFT JOIN {course} c ON c.id = q.course
				LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id
				LEFT JOIN (SELECT quizid, count(*) questions FROM {quiz_slots} GROUP BY quizid) ql ON ql.quizid = q.id
			WHERE 1 $sql_filter
			GROUP BY q.id $sql_having $sql_order", $params);
	}
	public function report10($params)
	{
		$columns = array_merge(array("q.name","u.firstname","u.lastname", "u.email", "c.fullname", "qa.state", "qa.timestart", "qa.timefinish", "duration", "grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "q.course", "courses");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "q.course");
		$sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
		$sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
		}

		return $this->get_report_data("
			SELECT qa.id,
				q.name, u.email,
				q.course,
				c.fullname,
				qa.timestart,
				qa.timefinish,
				qa.state,
				(qa.timefinish - qa.timestart) as duration,
				(qa.sumgrades/q.sumgrades*100) as grade,
				u.firstname,
				u.lastname
				$sql_columns
			FROM {quiz_attempts} qa
				LEFT JOIN {quiz} q ON q.id = qa.quiz
				LEFT JOIN {user} u ON u.id = qa.userid
				LEFT JOIN {course} c ON c.id = q.course
				LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
				LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = u.id
				$sql_join
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
	}
	public function report11($params)
	{
		$columns = array_merge(array("u.firstname", "u.lastname", "course", "u.email", "enrolled", "complete", "grade", "complete"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "ue.courseid", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "ue.courseid");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.cohortid");
		}

		return $this->get_report_data("
			SELECT ue.id,
				ue.timecreated as enrolled,
				cc.timecompleted as complete,
				(g.finalgrade/g.rawgrademax)*100 AS grade,
				u.id as uid,
				u.email,
				u.firstname,
				u.lastname,
				c.id as cid,
				c.enablecompletion,
				c.fullname as course
				$sql_columns
			FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = u.id
				LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
				$sql_join
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
	}

	public function report12($params)
	{
		$columns = array_merge(
			array(
				"c.fullname",
				"learners",
				"completed",
				"visits",
				"timespend",
				"grade"),
			$this->get_filter_columns($params)
		);
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");


		if($params->sizemode){
			$sql_columns = ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns = ", lit.timespend AS timespend, lit.visits AS visits";
			$sql_join = "
					LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY courseid, userid) lit ON lit.courseid = c.id AND lit.userid = ra.userid";
		}

		return $this->get_report_data("
			SELECT c.id,
				c.fullname,
				COUNT(DISTINCT ra.userid) as learners,
				COUNT(DISTINCT cc.userid) as completed,
				AVG((g.finalgrade/g.rawgrademax)*100) as  grade
				$sql_columns
			FROM {role_assignments} ra
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				LEFT JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ra.userid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ra.userid
				$sql_join
			WHERE 1 $sql_filter
			GROUP BY c.id $sql_having $sql_order", $params);
	}


	public function report13($params)
	{
		$columns = array_merge(
			array(
				"name",
				"visits",
				"timespend",
				"courses",
				"learners"),
			$this->get_filter_columns($params)
		);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		$sql1 = $this->get_filter_in_sql($params->learner_roles, "r.roleid");

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", lit.timespend AS timespend, lit.visits AS visits";
			$sql_join = "
					LEFT JOIN (SELECT userid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY userid) lit ON lit.userid = ra.userid";
		}

		return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) name,
				COUNT(DISTINCT ctx.instanceid) as courses,
				SUM(v.learners) as learners
				$sql_columns
			FROM {role_assignments} ra
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				LEFT JOIN {user} u ON u.id = ra.userid
				LEFT JOIN (SELECT c.instanceid, COUNT(DISTINCT r.userid) as learners
						FROM {role_assignments} r, {context} AS c WHERE c.id = r.contextid $sql1 GROUP BY c.instanceid) v ON v.instanceid = ctx.instanceid
				$sql_join
			WHERE 1 $sql_filter
			GROUP BY ra.userid $sql_having $sql_order", $params);
	}


	public function report14($params)
	{
		$columns = array_merge(
			array(
				"name",
				"u.email",
				"u.lastaccess",
				"visits",
				"timespend",
				"courses",
				"grade"),
			$this->get_filter_columns($params)
		);
		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", lit.timespend AS timespend, lit.visits AS visits";
			$sql_join = "
					LEFT JOIN (SELECT userid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY userid) lit ON lit.userid = ra.userid";
		}

		return $this->get_report_data("
			SELECT u.id, u.lastaccess,
				u.firstname,
				u.lastname,
				u.email,
				COUNT(DISTINCT ctx.instanceid) as courses,
				AVG( (g.finalgrade/g.rawgrademax)*100 ) AS grade
				$sql_columns
			FROM {role_assignments} ra
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				LEFT JOIN {user} u ON u.id = ra.userid
				LEFT JOIN {grade_items} gi ON gi.courseid = ctx.instanceid AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ra.userid AND g.finalgrade IS NOT NULL
				$sql_join
			WHERE 1 $sql_filter
			GROUP BY ra.userid $sql_having $sql_order", $params);
	}
	public function report15($params)
    {
        $columns = array_merge(array("enrol", "courses", "users"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "e.courseid", "courses");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");

        return $this->get_report_data("
			SELECT e.id,
				e.enrol as enrol,
				COUNT(DISTINCT e.courseid) as courses,
				count(ue.userid) as users
			FROM {enrol} e
				LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
			WHERE 1 $sql_filter GROUP BY e.enrol $sql_having $sql_order", $params);
    }

    public function report16($params)
    {
        $columns = array_merge(array("c.fullname", "teacher", "total", "v.visits", "v.timespend", "p.posts", "d.discussions"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT c.id,
				c.fullname,
				v.visits,
				v.timespend,
				d.discussions,
				p.posts,
				COUNT(*) AS total,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                ) AS teacher
				FROM {course} c
					LEFT JOIN {forum} f ON f.course = c.id
					LEFT JOIN (SELECT lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='forum' GROUP BY lit.courseid) v ON v.courseid = c.id
					LEFT JOIN (SELECT course, count(*) discussions FROM {forum_discussions} group by course) d ON d.course = c.id
					LEFT JOIN (SELECT fd.course, count(*) posts FROM {forum_discussions} fd, {forum_posts} fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
			WHERE 1 $sql_filter GROUP BY f.course $sql_having $sql_order", $params);
    }
    public function report17($params)
    {
        $columns = array_merge(array("c.fullname", "f.name ", "f.type ", "Discussions", "UniqueUsersDiscussions", "Posts", "UniqueUsersPosts", "Students", "Teachers", "UserCount", "StudentDissUsage", "StudentPostUsage"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_learner_roles = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT f.id as forum,
			       c.id,
			       c.fullname,
			       f.name,
			       f.type,
			       (SELECT COUNT(id) FROM {forum_discussions} AS fd WHERE f.id = fd.forum) AS Discussions,
			       (SELECT COUNT(DISTINCT fd.userid) FROM {forum_discussions} AS fd WHERE fd.forum = f.id) AS UniqueUsersDiscussions,
			       (SELECT COUNT(fp.id) FROM {forum_discussions} fd JOIN {forum_posts} AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS Posts,
			       (SELECT COUNT(DISTINCT fp.userid) FROM {forum_discussions} fd JOIN {forum_posts} AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS UniqueUsersPosts,
			       (SELECT COUNT( ra.userid ) AS Students
                        FROM {role_assignments} AS ra
                          JOIN {context} AS ctx ON ra.contextid = ctx.id
                        WHERE ctx.instanceid = c.id $sql_learner_roles
				    ) AS StudentsCount,
				    (SELECT COUNT( ra.userid ) AS Teachers
                        FROM {role_assignments} AS ra
                          JOIN {context} AS ctx ON ra.contextid = ctx.id
                        WHERE ctx.instanceid = c.id $sql_teacher_roles
                    ) AS teacherscount,
                    (SELECT COUNT( ra.userid ) AS Users
                        FROM {role_assignments} AS ra
                          JOIN {context} AS ctx ON ra.contextid = ctx.id
                        WHERE  ctx.instanceid = c.id
                    ) AS UserCount,
                    (SELECT (UniqueUsersDiscussions / StudentsCount )) AS StudentDissUsage,
                    (SELECT (UniqueUsersPosts /StudentsCount)) AS StudentPostUsage
			FROM {forum} AS f
				JOIN {course} c ON f.course = c.id
		    WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }


    public function report18($params)
    {
        $columns = array_merge(array("f.name", "u.firstname","u.lastname","course", "discussions", "posts"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT fd.id,
				c.fullname as course,
				u.firstname,
				u.lastname,
				f.name,
				COUNT(DISTINCT fp.id) AS posts,
				COUNT(DISTINCT fd.id) AS discussions
			FROM
				{forum_discussions} fd
				LEFT JOIN {user} u ON u.id = fd.userid
				LEFT JOIN {course} c ON c.id = fd.course
				LEFT JOIN {forum} f ON f.id = fd.forum
				LEFT JOIN {forum_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
			WHERE 1 $sql_filter GROUP BY u.id, f.id $sql_having $sql_order", $params);
    }

    public function report19($params)
    {
        $columns = array_merge(array("c.fullname", "teacher", "scorms"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_teacher_role = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT c.id,
				c.fullname, count(s.id) as scorms,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					FROM {role_assignments} AS ra
					JOIN {user} u ON ra.userid = u.id
					JOIN {context} AS ctx ON ctx.id = ra.contextid
					WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_role LIMIT 1) AS teacher
			FROM {course} c
				LEFT JOIN {scorm} s ON s.course = c.id
			WHERE c.category > 0 $sql_filter
			GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report20($params)
    {
        $columns = array_merge(array("s.name", "c.fullname", "sl.visits", "sm.duration", "s.timemodified"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "s.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT s.id,
					c.fullname,
					s.name,
					s.timemodified,
					count(sst.id) as attempts,
					sl.visits,
					sm.duration
			FROM {scorm} s
				LEFT JOIN {scorm_scoes_track} sst ON sst.scormid = s.id AND sst.element = 'x.start.time'
				LEFT JOIN {course} c ON c.id = s.course
				LEFT JOIN (SELECT cm.instance, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='scorm' GROUP BY cm.instance) sl ON sl.instance = s.id
				LEFT JOIN (SELECT scormid, SEC_TO_TIME(SUM(TIME_TO_SEC(value))) AS duration FROM {scorm_scoes_track} where element = 'cmi.core.total_time' GROUP BY scormid) AS sm ON sm.scormid =s.id
			WHERE 1 $sql_filter GROUP BY s.id $sql_having $sql_order", $params);
    }
    public function report21($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "sc.name", "c.fullname", "attempts", "sm.duration","sv.starttime","cmc.timemodified", "score"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
        }

        return $this->get_report_data("
			SELECT u.id+st.scormid+st.timemodified as id,
				u.firstname,
				u.lastname,
				u.email,
				st.userid,
				st.scormid,
				sc.name,
				c.fullname,
				COUNT(DISTINCT(st.attempt)) as attempts,
				cmc.completionstate,
				cmc.timemodified as completiondate,
				sv.starttime,
				sm.duration,
				sm.timemodified as lastaccess,
				round(sg.score, 0) as score
				$sql_columns
			FROM {scorm_scoes_track} AS st
				LEFT JOIN {user} u ON st.userid=u.id
				LEFT JOIN {scorm} AS sc ON sc.id=st.scormid
				LEFT JOIN {course} c ON c.id = sc.course
				LEFT JOIN {modules} m ON m.name = 'scorm'
				LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = sc.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
				LEFT JOIN (SELECT userid, timemodified, scormid, SEC_TO_TIME( SUM( TIME_TO_SEC( value ) ) ) AS duration FROM {scorm_scoes_track} where element = 'cmi.core.total_time' GROUP BY userid, scormid) AS sm ON sm.scormid =st.scormid and sm.userid=st.userid
				LEFT JOIN (SELECT userid, MIN(value) as starttime, scormid FROM {scorm_scoes_track} where element = 'x.start.time' GROUP BY userid, scormid) AS sv ON sv.scormid =st.scormid and sv.userid=st.userid
				LEFT JOIN (SELECT gi.iteminstance, (gg.finalgrade/gg.rawgrademax)*100 AS score, gg.userid FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemmodule='scorm' and gg.itemid=gi.id  GROUP BY gi.iteminstance, gg.userid) AS sg ON sg.iteminstance =st.scormid and sg.userid=st.userid $sql_join
			WHERE 1 $sql_filter
			GROUP BY st.userid, st.scormid $sql_having $sql_order", $params);
    }
    public function report22($params)
    {
        $columns = array_merge(
            array(
                "c.fullname",
                "teacher",
                "quizzes",
                "attempts",
                "duration",
                "grade"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");


        $sql1 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT qa.id,
				c.fullname,
				COUNT(DISTINCT q.id) AS quizzes,
				SUM(qa.timefinish - qa.timestart) AS duration,
				COUNT(DISTINCT qa.id) AS attempts,
				AVG((qa.sumgrades/q.sumgrades)*100) AS grade,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					FROM {role_assignments} AS ra
					JOIN {user} u ON ra.userid = u.id
					JOIN {context} AS ctx ON ctx.id = ra.contextid
					WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql1 LIMIT 1) AS teacher
			FROM {quiz_attempts} qa
				LEFT JOIN {quiz} q ON q.id = qa.quiz
				LEFT JOIN {course} c ON c.id = q.course
			WHERE 1 $sql_filter
			GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report23($params)
    {
        $columns = array_merge(array("c.fullname", "resources", "teacher"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT c.id,
				c.fullname,
				count(r.id) as resources,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					FROM {role_assignments} AS ra
					JOIN {user} u ON ra.userid = u.id
					JOIN {context} AS ctx ON ctx.id = ra.contextid
					WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql LIMIT 1) AS teacher
			FROM {course} c
				LEFT JOIN {resource} r ON r.course = c.id
			WHERE 1 $sql_filter
			GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report24($params)
    {
        $columns = array_merge(array("r.name", "c.fullname", "sl.visits", "sl.timespend", "r.timemodified"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "r.timemodified");

        return $this->get_report_data("
			SELECT r.id,
				c.fullname,
				r.name,
				r.timemodified,
				sl.visits,
				sl.timespend
				FROM {resource} r
				LEFT JOIN {course} c ON c.id = r.course
				LEFT JOIN (SELECT cm.instance, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='resource' GROUP BY cm.instance) sl ON sl.instance = r.id
			WHERE 1 $sql_filter
			GROUP BY r.id $sql_having $sql_order", $params);
    }
    public function report25($params)
    {
        $columns = array_merge(array("component", "files", "filesize"), $this->get_filter_columns($params));
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        return $this->get_report_data("
			SELECT id,
				component,
				count(id) as files,
				sum(filesize) as filesize
			FROM {files}
			WHERE filesize > 0
			GROUP BY component $sql_having $sql_order", $params);
    }

    public function report26($params)
    {
        $columns = array_merge(array("course", "user", "enrolled", "cc.timecompleted", "score", "completed", "l.visits", "l.timespend"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);

        return $this->get_report_data("
			SELECT ue.id,
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
			FROM {user_enrolments} ue
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid and cc.userid = ue.userid
				LEFT JOIN (SELECT gi.courseid, g.userid, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS score FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
				LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) as l ON l.courseid = c.id AND l.userid = u.id
				LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
			WHERE ue.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = :userid1) and userid <> :userid2 ) $sql_filter
			GROUP BY ue.userid, e.courseid  $sql_having $sql_order", $params);
    }
    public function report27($params)
    {
        $columns = array_merge(array("course", "username", "email", "q.name", "qa.state", "qa.timestart", "qa.timefinish", "qa.timefinish", "grade"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);

        return $this->get_report_data("
			SELECT qa.id,
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
				LEFT JOIN {course} c ON c.id = q.course
			WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = :userid1) and userid <> :userid2) $sql_filter
			GROUP BY qa.id $sql_having $sql_order", $params);
    }

    public function report28($params)
    {
        $columns = array_merge(array("gi.itemname", "u.firstname", "u.lastname", "u.email", "graduated", "grade", "completionstate", "timespend", "visits","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.course");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "gg.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend as timespend, l.visits as visits";
            $sql_join = " LEFT JOIN (SELECT userid, param, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY userid, param) l ON l.param = cm.id AND l.userid = u.id";
        }

        return $this->get_report_data("
			SELECT gg.id,
				gi.itemname,
				gg.userid,
				u.email,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				u.firstname,
				u.lastname,
				gg.timemodified as graduated,
				(gg.finalgrade/gg.rawgrademax)*100 as grade,
				cm.completion,
				cmc.completionstate
				$sql_columns
			FROM {grade_grades} gg
				LEFT JOIN {grade_items} gi ON gi.id=gg.itemid
				LEFT JOIN {user} u ON u.id = gg.userid
				LEFT JOIN {modules} m ON m.name = gi.itemmodule
				LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
				LEFT JOIN {course} c ON c.id=cm.course
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
				$sql_join
			WHERE gi.itemtype = 'mod' $sql_filter $sql_having $sql_order", $params);
    }

    public function report29($params)
    {
        $columns = array_merge(array("user", "course", "g.grade"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = clean_param($data[0], PARAM_INT);
                $sql_courses[] = "(e.courseid = :$course AND g.grade < :$grade)";
            }
            $sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_courses = "e.courseid > 0";
        }

        return $this->get_report_data("
			SELECT ue.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				c.fullname as course,
				g.grade,
				gm.graded,
				cm.modules $sql_columns
			FROM {user_enrolments} ue
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN (SELECT gi.courseid, gg.userid, (gg.finalgrade/gg.rawgrademax)*100 AS grade FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemtype = 'course' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as g ON g.courseid = c.id AND g.userid = u.id
				LEFT JOIN (SELECT gi.courseid, gg.userid, count(gg.id) graded FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemtype = 'mod' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as gm ON gm.courseid = c.id AND gm.userid = u.id
				LEFT JOIN (SELECT courseid, count(id) as modules FROM {grade_items} WHERE itemtype = 'mod' GROUP BY courseid) as cm ON cm.courseid = c.id
			WHERE (cc.timecompleted IS NULL OR cc.timecompleted = 0) AND gm.graded >= cm.modules AND $sql_courses $sql_filter
			GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
    }

    public function report30($params)
    {
        $columns = array_merge(array("user", "course", "enrolled", "cc.timecompleted"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = clean_param($data[0], PARAM_INT) / 1000;
                $sql_courses[] = "(cc.course = :$course AND cc.timecompleted > :$grade)";
            }
            $sql_filter .= " AND (" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_filter .= " AND cc.course > 0";
        }

        return $this->get_report_data("
			SELECT cc.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, cc.timecompleted
			FROM
				{course_completions} cc,
				{course} c,
				{user} u
			WHERE u.id= cc.userid AND c.id = cc.course $sql_filter $sql_having $sql_order", $params);
    }

    public function report31($params)
    {
        $columns = array_merge(array("user", "course", "lit.lastaccess"), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_order = $this->get_order_sql($params, $columns);

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = (time()-clean_param($data[0], PARAM_INT)*86400);
                $sql_courses[] = "(lit.courseid = :$course AND lit.lastaccess < :$grade)";
            }
            $sql_filter = " AND (" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_filter = " AND lit.courseid > 0";
        }

        return $this->get_report_data("
			SELECT lit.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, lit.lastaccess
			FROM {user} u
				LEFT JOIN {local_intelliboard_tracking} lit on lit.userid = u.id AND lit.lastaccess = (
					SELECT MAX(lastaccess)
						FROM {local_intelliboard_tracking}
						WHERE userid = lit.userid and courseid = lit.courseid
					)
				LEFT JOIN {course} c ON c.id = lit.courseid
			WHERE 1 $sql_filter GROUP BY lit.userid, lit.courseid $sql_order", $params);
    }
    public function report32($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "courses","lit1.timesite","lit2.timecourses","lit3.timeactivities","u.timecreated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $sql_join_filter = "";
        if(isset($params->custom) and $params->custom == 1){
            $sql_join_filter .= $this->get_filterdate_sql($params, "l.timepoint");
        }else{
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        }
        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");
        }

        return $this->get_report_data("
			SELECT u.id,
				u.firstname,
				u.lastname,
				u.email,
				u.timecreated,
				COUNT(DISTINCT ctx.instanceid) as courses,
				lit1.timesite,
				lit2.timecourses,
				lit3.timeactivities
				$sql_columns
			FROM {role_assignments} ra
                LEFT JOIN {user} u ON u.id = ra.userid
                LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				LEFT JOIN (SELECT t.userid, SUM(l.timespend) as timesite FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id $sql_join_filter GROUP BY t.userid) as lit1 ON lit1.userid = u.id
				LEFT JOIN (SELECT t.userid, SUM(l.timespend) as timecourses FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id AND t.courseid > 0 $sql_join_filter GROUP BY t.userid) as lit2 ON lit2.userid = u.id
				LEFT JOIN (SELECT t.userid, SUM(l.timespend) as timeactivities FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
					WHERE l.trackid = t.id AND t.page = 'module' $sql_join_filter GROUP BY t.userid) as lit3 ON lit3.userid = u.id
				$sql_join
			WHERE 1 $sql_filter
			GROUP BY ra.userid $sql_having $sql_order", $params);
    }


    public function get_scormattempts($params)
    {
        global $DB;

        $this->params['userid'] = intval($params->userid);
        $this->params['scormid'] = intval($params->filter);

        return $DB->get_records_sql("
            SELECT sst.attempt,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'x.start.time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as starttime,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.score.raw' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as score,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.lesson_status' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as status,
				(SELECT s.value FROM {scorm_scoes_track} s WHERE element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as totaltime,
				(SELECT s.timemodified FROM {scorm_scoes_track} s WHERE element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as timemodified
			FROM {scorm_scoes_track} sst
			WHERE sst.userid = :userid  and sst.scormid = :scormid
			GROUP BY sst.attempt", $this->params);
    }

    public function report34($params)
    {
        $columns = array("c.fullname", "e.enrol", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);

        return $this->get_report_data("
			SELECT ue.id,
				ue.timecreated as enrolled,
				gc.grade,
				c.enablecompletion,
				cc.timecompleted as complete,
				e.enrol,
				l.timespend,
				l.visits,
				c.id as cid,
				ue.userid,
				c.fullname as course,
				c.timemodified as start_date,
				round(((cmc.completed/cmm.modules)*100), 0) as progress
			FROM {user_enrolments} ue
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
				LEFT JOIN (SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), 0) AS grade
					FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id
				GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = ue.userid
				LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} GROUP BY courseid, userid) l ON l.courseid = c.id AND l.userid = ue.userid
				LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid = :userid1 GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
			WHERE ue.userid = :userid2 $sql_having $sql_order", $params);
    }

    public function report35($params)
    {
    	global $CFG;

        $columns = array("gi.itemname", "graduated", "grade", "completionstate", "timespend", "visits");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "gi.courseid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_order = $this->get_order_sql($params, $columns);

        $this->params['userid'] = intval($params->userid);

        $data = $this->get_report_data("
			SELECT gi.id,
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
				LEFT JOIN {user} u ON u.id = :userid
				LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
				LEFT JOIN {modules} m ON m.name = gi.itemmodule
				LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
				LEFT JOIN {course} c ON c.id=cm.course
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
				LEFT JOIN (SELECT lit.userid, lit.param, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.page = 'module' GROUP BY lit.userid, lit.param) l ON l.param = cm.id AND l.userid = u.id
			WHERE gi.itemtype = 'mod' $sql_filter $sql_having $sql_order", $params, false);

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
        return array("data" => $data);
    }
    public function report36($params)
    {
        $columns = array("c.fullname", "l.page", "l.param", "l.visits", "l.timespend", "l.firstaccess", "l.lastaccess", "l.useragent", "l.useros", "l.userlang");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        $this->params['userid'] = intval($params->userid);

        return $this->get_report_data("
			SELECT l.id,
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
				LEFT JOIN {course} c ON c.id = l.courseid
			WHERE l.userid = :userid $sql_having $sql_order", $params);
    }

    public function report37($params)
    {
        $columns = array_merge(array("learner","u.email","u.id"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "u.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email
				$sql_columns
			FROM {role_assignments} ra, {user} u
			WHERE u.id = ra.userid $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report38($params)
    {
        $columns = array_merge(array("c.startdate", "ccc.timeend", "course", "u.firstname","u.lastname", "u.email", "enrols", "enrolstart", "enrolend", "complete", "complete"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        return $this->get_report_data("
			SELECT ue.id,
				IF(ue.timestart = 0, ue.timecreated, ue.timecreated) as enrolstart,
				ue.timeend as enrolend,
				ccc.timeend,
				c.startdate,
				c.enablecompletion,
				cc.timecompleted as complete,
				u.firstname,
				u.lastname,
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
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {course_completion_criteria} ccc ON ccc.course = e.courseid AND ccc.criteriatype = 2
			WHERE 1 $sql_filter
			GROUP BY ue.id $sql_having $sql_order", $params);


    }
    public function report39($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","u.email","u.timecreated","u.firstaccess","u.lastaccess","lit1.timespend_site","lit2.timespend_courses","lit3.timespend_activities","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");
        }

        return $this->get_report_data("
			SELECT u.id,
				u.firstname,
				u.lastname,
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
			FROM {user} u
				LEFT JOIN (SELECT userid, SUM(timespend) as timespend_site FROM {local_intelliboard_tracking} GROUP BY userid) as lit1 ON lit1.userid = u.id
				LEFT JOIN (SELECT userid, SUM(timespend) as timespend_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 GROUP BY userid) as lit2 ON lit2.userid = u.id
				LEFT JOIN (SELECT userid, SUM(timespend) as timespend_activities FROM {local_intelliboard_tracking} WHERE page='module' GROUP BY userid) as lit3 ON lit3.userid = u.id
				$sql_join
			WHERE 1 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report40($params)
    {
        $columns = array("course", "u.firstname", "u.lastname", "email", "e.enrol", "ue.timecreated", "la.lastaccess", "gc.grade");

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql1 = $this->get_filterdate_sql($params, "l.timepoint");

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
        }


        return $this->get_report_data("
			SELECT ue.id,
				u.firstname,
				u.lastname,
				u.email,
				ue.timecreated as enrolled,
				ue.userid,
				e.enrol AS enrols,
				ul.timeaccess AS lastaccess,
				ROUND(((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				c.id as cid,
				c.fullname as course
				$sql_columns
			FROM {user_enrolments} ue
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
        		LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
				LEFT JOIN (SELECT t.id,t.userid,t.courseid FROM
					{local_intelliboard_tracking} t,
					{local_intelliboard_logs} l
				WHERE l.trackid = t.id AND t.page = 'course' $sql1 GROUP BY t.courseid, t.userid) as l ON l.courseid = e.courseid AND l.userid = ue.userid $sql_join
			WHERE l.id IS NULL $sql_filter $sql_having $sql_order", $params);
    }
    public function report41($params)
    {
        $columns = array("course", "u.firstname","u.lastname","email", "certificate", "ci.timecreated", "ci.code","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country","");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ci.timecreated");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");

        return $this->get_report_data("
			SELECT ci.id,
				u.firstname,
				u.lastname,
				u.email,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				ce.name as certificate,
				ci.timecreated,
				ci.code,
				ci.userid,
				c.id as cid,
				c.fullname as course
				$sql_columns
			FROM {certificate_issues} ci
				LEFT JOIN {certificate} ce ON ce.id = ci.certificateid
				LEFT JOIN {user} u ON u.id = ci.userid
				LEFT JOIN {course} c ON c.id = ce.course
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }
    public function report43($params)
    {
        $columns = array("user", "completed_courses", "grade", "lit.visits", "lit.timespend", "u.timecreated");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");

        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", lit.timespend, lit.visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) lit ON lit.userid = u.id";
        }

        return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT e.courseid) as courses,
				COUNT(DISTINCT cc.course) as completed_courses
				$sql_columns
			FROM {user} u
				LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
        		LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
        		$sql_join
			WHERE 1 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report44($params)
    {
        $columns = array("c.fullname", "users", "completed");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");

        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_order = $this->get_order_sql($params, $columns);

        return $this->get_report_data("
			SELECT c.id,
				c.fullname,
				COUNT(DISTINCT ue.userid) users,
				COUNT(DISTINCT cc.userid) as completed
			FROM {user_enrolments} ue
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid AND cc.timecompleted > 0
			WHERE 1 $sql_filter
			GROUP BY e.courseid $sql_having $sql_order", $params);
    }
    public function report45($params)
    {
        $columns = array("u.firstname", "u.lastname", "u.email", "all_att", "timespend", "highest_grade", "lowest_grade", "cmc.timemodified");

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        if($params->custom == 1){
            $sql_having .= (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)=0':str_replace(' HAVING ',' HAVING (',$sql_having). ') AND COUNT(DISTINCT qa.id)=0';
        }elseif($params->custom == 2){
            $sql_having .= (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)':str_replace(' HAVING ',' HAVING (',$sql_having).') AND COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)';
        }
        $this->params['courseid'] = intval($params->courseid);

        return $this->get_report_data("
 			SELECT u.id,
				u.firstname,
				u.lastname,
				u.email,
				COUNT(DISTINCT qa.id) as all_att,
				(MAX(qa.sumgrades)/q.sumgrades)*100 as highest_grade,
				(MIN(qa.sumgrades)/q.sumgrades)*100 as lowest_grade,
				SUM(qa.timefinish - qa.timestart) AS timespend,
				cmc.timemodified
				$sql_columns
			FROM {quiz_attempts} qa
				LEFT JOIN {quiz} q ON q.id = qa.quiz
				LEFT JOIN {user} u ON u.id = qa.userid
				LEFT JOIN {modules} m ON m.name='quiz'
				LEFT JOIN {course_modules} cm ON cm.course = q.course AND cm.module=m.id AND cm.instance=q.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate = 1 AND cmc.userid=qa.userid
			WHERE q.id= :courseid $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report42($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","u.email", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "e.courseid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");

        $sql_grades = '';
        $grades = array();
        if(!empty($params->custom)){
            $book = explode(',',$params->custom);
            foreach($book as $i=>$item){
                $grade = explode('-',$item);
                $grade0 = "grade0$i"; $grade1 = "grade1$i";
                $this->params[$grade0] = isset($grade[0]) ? clean_param($grade[0], PARAM_INT) : false;
                $this->params[$grade1] = isset($grade[1]) ? clean_param($grade[1], PARAM_INT) : false;
                if($grade0 !== false and $grade1 !== false ){
                    $grades[] = "(g.finalgrade/g.rawgrademax)*100 BETWEEN :$grade0 AND :$grade1";
                }
            }
            if($grades){
                $sql_grades = '('.implode(' OR ',$grades).') AND ';
            }
        }

        return $this->get_report_data("
			SELECT ue.id,
				cri.gradepass,
				u.email,
				ue.userid,
				ue.timecreated as started,
				c.id as cid,
				c.fullname,
				git.average,
				AVG((g.finalgrade/g.rawgrademax)*100) AS `grade`,
				cmc.completed,
				u.firstname,
				u.lastname,
				lit.timespend,
				lit.visits,
				c.enablecompletion,
				cc.timecompleted as complete
				$sql_columns
			FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {course_completion_criteria} cri ON cri.course = e.courseid AND cri.criteriatype = 6
				LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
				LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) lit ON lit.courseid = c.id AND lit.userid = u.id
				LEFT JOIN (SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), 0) AS average
						FROM {grade_items} gi, {grade_grades} g
						WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
						GROUP BY gi.courseid) git ON git.courseid=c.id
				LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
			WHERE $sql_grades 1 $sql_filter
			GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
    }
    public function report46($params)
    {
        $this->params['userid'] = intval($params->userid);

        return $this->get_report_data("
			SELECT gi.id,
				IF(gi.itemname <> '', gi.itemname,
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
			FROM {grade_items} gi
				LEFT JOIN {grade_categories} gc ON IF(gi.itemtype='category' OR gi.itemtype='course' ,gc.id=gi.iteminstance,gc.id=gi.categoryid)
				LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id
				LEFT JOIN {course} c ON c.id=gi.courseid
				LEFT JOIN {quiz_attempts} qa ON qa.quiz=gi.iteminstance AND qa.userid=gg.userid AND qa.state='finished'
				LEFT JOIN {assign_submission} ass ON ass.assignment=gi.iteminstance AND ass.userid=gg.userid AND ass.status='submitted'
				LEFT JOIN {course_completions} cc ON cc.course=gi.courseid AND cc.userid=gg.userid
			WHERE gg.userid = :userid AND gi.id IS NOT NULL
			GROUP BY gi.id
			ORDER BY gc.depth desc, gc.id ASC", $params);
    }
    public function report47($params)
    {
        $columns = array("u_related.firstname","u_related.lastname", "u_related.email", "course_name", "role", "lsl.all_count", "user_action", "action_role", "action", "timecreated");

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");

        return $this->get_report_data("
			SELECT ra.id,
				c.id AS courseid,
				c.fullname as course_name,
				GROUP_CONCAT( DISTINCT r0.shortname) AS role,
				lsl.all_count,
				IF(lsl.all_count>1,r.shortname,'-') as action_role,
				IF(lsl.all_count>1,log.action,'-') as action,
				IF(lsl.all_count>1,log.timecreated,'-') as timecreated,
				IF(lsl.all_count>1,CONCAT(u_action.firstname, ' ', u_action.lastname),'-') AS user_action,
				u_action.id as user_action_id,
				u_related.firstname,
				u_related.lastname,
				u_related.email,
				u_related.id as user_related_id
				$sql_columns
			FROM {role_assignments} ra
				LEFT JOIN {role} r0 ON r0.id = ra.roleid
				LEFT JOIN {context} ctx ON ctx.id = ra.contextid
				LEFT JOIN {course} c ON c.id=ctx.instanceid
				LEFT JOIN (SELECT lsl.courseid, lsl.relateduserid, MAX(lsl.id) as last_change, COUNT(lsl.id) as all_count
						   FROM {logstore_standard_log} lsl
						   WHERE (lsl.action='assigned' OR lsl.action='unassigned') AND lsl.target='role' AND lsl.contextlevel=50
						   GROUP BY lsl.courseid,lsl.relateduserid
						  ) as lsl ON lsl.courseid=c.id AND lsl.relateduserid=ra.userid
				LEFT JOIN {logstore_standard_log} log ON log.id=lsl.last_change
				LEFT JOIN {role} r ON r.id=log.objectid
				LEFT JOIN {user} u_action ON u_action.id=log.userid
				LEFT JOIN {user} u_related ON u_related.id=log.relateduserid
			WHERE 1 $sql_filter
			GROUP BY ra.userid $sql_having $sql_order", $params);
    }

    public function report58($params)
    {
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $this->params['userid'] = intval($params->custom);
        $this->params['time'] = time();

        return $this->get_report_data("
			SELECT gi.id,
				gi.itemname,
				cm.id as cmid,
				cm.completionexpected,
				c.fullname,
				cm.completionexpected
			FROM {grade_items} gi
				LEFT JOIN {course} c ON c.id = gi.courseid
				LEFT JOIN {modules} m ON m.name = gi.itemmodule
				LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = :userid
			WHERE cm.visible = 1 AND gi.itemtype = 'mod' AND cm.completionexpected > :time  AND (cmc.id IS NULL OR cmc.completionstate=0) $sql_filter
			ORDER BY cm.completionexpected ASC", $params);
    }
    public function report66($params)
    {
        $columns = array("u.firstname", "u.lastname", "u.email", "course", "assignment", "a.duedate", "s.status", "gc.grade", "cc.timecompleted", "ue.timecreated","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country","");

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filterdate_sql($params, "a.duedate");

        return $this->get_report_data("
			SELECT @x:=@x+1 as id,
				a.name as assignment,
				a.duedate,
				c.fullname as course,
				s.status,
				s.timemodified AS submitted ,
				u.email,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				u.firstname,
				u.lastname
				$sql_columns
			FROM (SELECT @x:= 0) AS x, {assign} a
				LEFT JOIN (SELECT e.courseid, ue.userid FROM {user_enrolments} ue, {enrol} e WHERE e.id=ue.enrolid GROUP BY e.courseid, ue.userid) ue
				ON ue.courseid = a.course
				LEFT JOIN {user} u ON u.id = ue.userid
				LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = u.id
				LEFT JOIN {course} c ON c.id = a.course
				LEFT JOIN {course_modules} cm ON cm.instance = a.id
			WHERE (s.timemodified > a.duedate or s.timemodified IS NULL) $sql_filter $sql_having $sql_order", $params);
    }

    public function report72($params)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/scorm/locallib.php');
        require_once($CFG->dirroot.'/mod/scorm/report/reportlib.php');

        $this->params['attempt'] = (int)$params->custom;
        $this->params['courseid'] = (int)$params->courseid;

        if($params->userid){
        	$this->params['userid'] = (int)$params->userid;
        	$sql = " AND t.userid = :userid";
        }else{
        	$sql = "";
        }


        $trackdata = $DB->get_records_sql("
			SELECT t.*, u.firstname, u.lastname
			FROM {scorm_scoes_track} t, {user} u, {scorm} s
			WHERE u.id = t.userid AND s.course = :courseid AND t.scormid = s.id AND t.attempt = :attempt $sql", $this->params);

        $questioncount = array_values($trackdata)[0]->scormid;

		// Defined in order to unify scorm1.2 and scorm2004. - $data = scorm_format_interactions($data);
        $data = array();
	    foreach ($trackdata as $track) {
	    	if(isset($data[$track->userid])){
	    		$usertrack = $data[$track->userid];
	    	}else{
	    		$usertrack = new stdClass();
	    		$usertrack->score_raw = '';
			    $usertrack->status = '';
			    $usertrack->total_time = '00:00:00';
			    $usertrack->session_time = '00:00:00';
			    $usertrack->timemodified = 0;
			    $usertrack->firstname = isset($track->firstname)?$track->firstname:'';
	        	$usertrack->lastname = isset($track->lastname)?$track->lastname:'';
	    	}
	        $element = $track->element;
	        $usertrack->{$element} = $track->value;
	        switch ($element) {
	            case 'cmi.core.lesson_status':
	            case 'cmi.completion_status':
	                if ($track->value == 'not attempted') {
	                    $track->value = 'notattempted';
	                }
	                $usertrack->status = $track->value;
	                break;
	            case 'cmi.core.score.raw':
	            case 'cmi.score.raw':
	                $usertrack->score_raw = (float) sprintf('%2.2f', $track->value);
	                break;
	            case 'cmi.core.session_time':
	            case 'cmi.session_time':
	                $usertrack->session_time = $track->value;
	                break;
	            case 'cmi.core.total_time':
	            case 'cmi.total_time':
	                $usertrack->total_time = $track->value;
	                break;
	        }
	        if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified)) {
	            $usertrack->timemodified = $track->timemodified;
	        }
	        $data[$track->userid] = $usertrack;
	    }


        return array(
            "questioncount"   => $questioncount,
            "recordsTotal"    => count($trackdata),
            "recordsFiltered" => count($trackdata),
            "data"            => $data);
    }
    public function report73($params)
    {
        global $DB;

        $this->params['courseid'] = (int)$params->courseid;

        $sql = "";
        if($params->userid){
	        $this->params['userid'] = (int)$params->userid;
	        $data = $DB->get_records_sql("
				SELECT b.id, b.name, i.timemodified, i.location, i.progress, u.firstname, u.lastname, c.fullname
				FROM {scorm_ajax_buttons} b
					LEFT JOIN {user} u ON u.id = :userid
					LEFT JOIN {course} c ON c.id = :courseid
					LEFT JOIN {scorm} s ON s.course = c.id
					LEFT JOIN {scorm_ajax} a ON a.scormid = s.id
					LEFT JOIN {scorm_ajax_info} i ON i.page = b.id AND i.userid = u.id AND i.relid = a.relid
				ORDER BY b.id", $this->params);
        }else{
	        $data = $DB->get_records_sql("
				SELECT @x:=@x+1 as id, b.name, i.timemodified, i.location, i.progress, u.firstname, u.lastname, c.fullname
				FROM (SELECT @x:= 0) AS x, {scorm_ajax_buttons} b
					LEFT JOIN {course} c ON c.id = :courseid
					LEFT JOIN {scorm} s ON s.course = c.id
					LEFT JOIN {scorm_ajax} a ON a.scormid = s.id
					LEFT JOIN {scorm_ajax_info} i ON i.page = b.id AND i.relid = a.relid
					LEFT JOIN {user} u ON u.id = i.userid
				WHERE u.id > 0
				GROUP BY b.id, u.id
				ORDER BY b.id", $this->params);
        }


        return array(
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data);
    }
    //Custom Report
    public function report75($params)
    {
        $columns = array("mc_course", "mco_name", "mc_name", "mci_userid", "mci_certid", "mu_firstname", "mu_lastname", "issue_date");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "mc.course", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "mc.course");
        $sql_filter .= $this->get_filterdate_sql($params, "mci.timecreated");

        return $this->get_report_data("
			SELECT DISTINCT mci.id,
				mc.course AS mc_course,
				mco.fullname AS mco_name,
				mc.name AS mc_name,
				mci.userid AS mci_userid,
				mci.certificateid AS mci_certid,
				mu.firstname AS mu_firstname,
				mu.lastname AS mu_lastname,
				DATE_FORMAT(FROM_UNIXTIME(mci.timecreated),'%m-%d-%Y') AS issue_date
			FROM {certificate} mc
				LEFT JOIN {certificate_issues} AS mci ON mci.certificateid = mc.id
				LEFT OUTER JOIN {user} AS mu ON mci.userid = mu.id
				LEFT OUTER JOIN {course} AS mco ON mc.course = mco.id
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }

    public function report76($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "feedback",
            "question_number",
            "question",
            "answer",
            "feedback_time",
            "course_name", "cc.timecompleted", "grade"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "mf.course");
        $sql_filter .= $this->get_filterdate_sql($params, "mfc.timemodified");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $data = $this->get_report_data("
			SELECT
				@x:=@x+1 as id,
				u.firstname,
				u.lastname,
				u.email,
				mfc.timemodified as feedback_time,
				cc.timecompleted,
				mfi.presentation,
                mfi.typ,
				mfi.id AS q_id,
				mfi.label AS question_number,
				mfi.name as question,
				mfv.value as answer,
				mf.name as feedback,
				c.fullname as course_name,
				round(((g.finalgrade/g.rawgrademax)*100), 0) AS grade
				$sql_columns
			FROM (SELECT @x:= 0) AS x, {feedback} AS mf
			LEFT JOIN {feedback_item} AS mfi ON mfi.feedback = mf.id
			LEFT JOIN {feedback_value} mfv ON mfv.item = mfi.id
			LEFT JOIN {feedback_completed} mfc ON mfc.id = mfv.completed
			LEFT JOIN {user} u ON mfc.userid = u.id
			LEFT JOIN {course} c ON c.id = mf.course
			LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
			LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
			LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
			WHERE 1 $sql_filter $sql_having $sql_order", $params, false);

        foreach( $data as $k=>$v ){
            $data[$k] = $this->parseFeedbackAnswer($v);
        }

        return array("data" => $data);
    }
    public function report77($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "cm.idnumber",
            "l.intro",
            "cmc.timemodified"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "l.course");
        $sql_filter .= $this->get_filterdate_sql($params, "a.timeseen");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        return $this->get_report_data("
			SELECT
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
				LEFT JOIN {course_modules_completion} cc ON cc.coursemoduleid = cm.id AND cc.userid = u.id AND cc.completionstate = 1
			WHERE 1 $sql_filter GROUP BY l.id, u.id $sql_having $sql_order", $params);
    }

    public function report79($params)
    {
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
        $sql_columns .= $this->get_modules_sql($params->custom);

        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "l.courseid");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "l.lastaccess");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        return $this->get_report_data("
			SELECT
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
			WHERE l.page = 'module' $sql_filter $sql_having $sql_order", $params);
    }
    public function report80($params)
    {
        global $DB;

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
        $sql_filter = $this->get_filterdate_sql($params, "l.timepoint");

        $this->params['id'] = (int)$params->custom3;

        $item = $DB->get_record_sql("
			SELECT l.id, u.firstname, u.lastname, c.fullname, u.email, l.page, l.param, l.visits, l.timespend, l.firstaccess, l.lastaccess, 'none' as name
			FROM {local_intelliboard_tracking} l
				LEFT JOIN {user} u ON u.id = l.userid
				LEFT JOIN {course} c ON c.id = l.courseid
			WHERE l.id = :id", $this->params);

        if($item->id and $item->param){
            if($item->page == 'module'){
                $this->params['id'] = (int)$item->param;
                $cm = $DB->get_record_sql("SELECT cm.instance, m.name FROM {course_modules} cm, {modules} m WHERE cm.id = :id AND m.id = cm.module", $this->params);

                $this->params['id'] = (int)$cm->instance;
                $instance = $DB->get_record_sql("SELECT name FROM {".$cm->name."} WHERE id = :id", $this->params);
                $item->name = $instance->name;
            }
            $this->params['trackid'] = (int)$item->id;

            $data = $this->get_report_data("
				SELECT l.id, l.visits, l.timespend,
					'' as firstaccess,
					l.timepoint as lastaccess,
					'' as firstname,
					'' as lastname,
					'' as email,
					'' as param,
					'' as name,
					'' as fullname
				FROM {local_intelliboard_logs} l
				WHERE l.trackid = :trackid $sql_filter $sql_order", $params, false);
            foreach($data as $d){
                $d->firstname = $item->firstname;
                $d->lastname = $item->lastname;
                $d->fullname = $item->fullname;
                $d->name = $item->name;
                break;
            }
            return array("data" => $data);
        }
        return null;
    }
    public function report81($params)
    {
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
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_columns .= $this->get_modules_sql('');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->users, "ra.userid");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql1 = $this->get_filterdate_sql($params, "lit.lastaccess");

        return $this->get_report_data("
			SELECT DISTINCT @x:=@x+1 as id, u.firstname,u.lastname, u.email, c.fullname, c.shortname, lit.visits, lit.timespend, lit.firstaccess,lit.lastaccess, cm.instance, m.name as module $sql_columns
			FROM (SELECT @x:= 0) AS x,{role_assignments} AS ra
				JOIN {user} u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {course_modules} cm ON cm.course = c.id
				LEFT JOIN {modules} m ON m.id = cm.module
				LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid = u.id AND lit.param = cm.id and lit.page = 'module' $sql1
			WHERE ctx.contextlevel = 50 $sql_filter $sql_having $sql_order", $params);
    }
    public function report82($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","c.fullname","c.shortname","forums","discussions","posts","l.visits","l.timespend"),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->users, "ra.userid");

        $sql1 = ($params->timestart) ? $this->get_filterdate_sql($params, 'd.timemodified') : '';
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 'p.created') : '';
        $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'l.lastaccess') : ''; //XXX


        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend, l.visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'forum' AND cm.id = l.param AND cm.module = m.id $sql3 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        return $this->get_report_data("
            SELECT ra.id,
                   u.firstname,
                   u.lastname,
                   u.email,
                   c.fullname,
                   c.shortname,
                   COUNT(distinct f.id) as forums,
                   COUNT(distinct d.id) as discussions,
                   COUNT(distinct p.id) as posts
                   $sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {forum} f ON f.course = c.id
				LEFT JOIN {modules} m ON m.name = 'forum'
				LEFT JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
				LEFT JOIN {forum_discussions} d ON d.course = c.id AND d.forum = f.id $sql1
				LEFT JOIN {forum_posts} p ON p.discussion = d.id AND p.parent > 0 $sql2
				$sql_join
			WHERE ctx.contextlevel = 50 $sql_filter
			GROUP BY ra.id $sql_having $sql_order", $params);
    }

    public function report83($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","c.fullname","c.shortname","l.visits","l.timespend","enrolled","completed"),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->users, "ra.userid");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');

        $sql1 = $this->get_filter_in_sql($params->learner_roles, 'ra.roleid');
        $sql1 .= ($params->timestart) ? $this->get_filterdate_sql($params, 'ra.timemodified') : '';
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 'cc.timecompleted') : '';
        $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'lastaccess') : ''; //XXX

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend, l.visits";
            $sql_join = " LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' or page = 'course' $sql3 GROUP BY userid, courseid ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        return $this->get_report_data("
			SELECT ra.id,
				u.firstname,
				u.lastname,
				u.email,
				c.fullname,
				c.shortname,
				e.enrolled,
				cc.completed
				$sql_columns
			FROM {role_assignments} ra
				JOIN {user} u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN (SELECT ctx.instanceid, COUNT(DISTINCT ra.userid) as enrolled FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ctx.instanceid) AS e ON e.instanceid = ctx.instanceid
				LEFT JOIN (SELECT course, COUNT(id) as completed FROM {course_completions} WHERE timecompleted > 0 $sql2 GROUP BY course) cc ON cc.course = ctx.instanceid
				$sql_join
			WHERE ctx.contextlevel = 50 $sql_filter
			GROUP BY ra.id $sql_having $sql_order", $params);
    }

    public function report84($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","c.fullname","c.shortname","assignments","completed","submissions","grades","l.visits","l.timespend"),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->users,'ra.userid');

        $sql1 = ($params->timestart) ? $this->get_filterdate_sql($params, 'cmc.timemodified') : '';
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 's.timemodified') : '';
        $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'g.timemodified') : '';
        $sql4 = ($params->timestart) ? $this->get_filterdate_sql($params, 'l.lastaccess') : ''; //XXX

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend, l.visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'assign' AND cm.id = l.param AND cm.module = m.id $sql4 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        return $this->get_report_data("
            SELECT ra.id,
                   u.firstname,
                   u.lastname,
                   u.email,
                   c.fullname,
                   c.shortname,
                   COUNT(distinct a.id) as assignments,
                   COUNT(distinct cmc.coursemoduleid) as completed,
                   COUNT(distinct s.assignment) as submissions,
                   COUNT(distinct g.assignment) as grades
                   $sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {assign} a ON a.course = c.id
				LEFT JOIN {modules} m ON m.name = 'assign'
				LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate = 1 $sql1
				LEFT JOIN {assign_submission} s ON s.status = 'submitted' AND s.assignment = a.id $sql2
				LEFT JOIN {assign_grades} g ON g.assignment = a.id $sql3
				$sql_join
			WHERE ctx.contextlevel = 50 $sql_filter
			GROUP BY ra.id $sql_having $sql_order", $params);
    }

    public function report85($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "registered",
            "loggedin",
            "loggedout"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filterdate_sql($params, "l1.timecreated");
        $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_join = "";
        if($params->custom2){
	        $sql = $this->get_filter_in_sql($params->custom2, 'roleid');
	        $sql_filter .= " AND l1.userid IN (SELECT DISTINCT userid FROM {role_assignments} WHERE 1 $sql)";
	    }
        return $this->get_report_data("
            SELECT l1.id,
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
			WHERE l1.action = 'loggedin' $sql_filter
			GROUP BY l1.id $sql_having $sql_order", $params);
    }

    public function report87($params)
    {
        $columns = array_merge(array("fieldname", "users"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "userid", "users");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "fieldid");

        if(!$params->custom){
            return array();
        }

        $data = $this->get_report_data("
        	SELECT id, data as fieldname, COUNT(*) as users
			FROM {user_info_data}
			WHERE data <> '' $sql_filter
			GROUP BY data $sql_having $sql_order", $params, false);

        return array("data" => $data, 'custom'=> $params->custom);
    }

    public function report88($params)
    {
    	global $DB;

        $sql_select = array();
        $sql_filter1 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter2 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter3 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter4 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter5 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter6 = "";
        $sql_filter7 = "";
        if($params->courseid){
            $sql_filter1 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $this->params['csid'] = intval($params->courseid);
            $sql_select[] = "(SELECT fullname FROM {course} WHERE id = :csid) as course";
        }else{
            return array();
        }
        if($params->users){
        	$sql_filter1 .= $this->get_filter_in_sql($params->users, "g.userid");
        	$sql_filter2 .= $this->get_filter_in_sql($params->users, "g.userid");
        	$sql_filter3 .= $this->get_filter_in_sql($params->users, "g.userid");
        	$sql_filter4 .= $this->get_filter_in_sql($params->users, "g.userid");
        	$sql_filter5 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter6 .= $this->get_filter_in_sql($params->users, "userid");
            $sql_filter7 .= $this->get_filter_in_sql($params->users, "cmc.userid");
            $sql_filter8 = $this->get_filter_in_sql($params->users, "id");
            $sql_select[] = "(SELECT CONCAT(firstname,' ',lastname) FROM {user} WHERE 1 $sql_filter8) as user";
        }
        $sql_select = ($sql_select) ? ", " . implode(",", $sql_select) : "";

        $this->params['cx1'] = intval($params->courseid);
        $this->params['cx2'] = intval($params->courseid);
        $this->params['cx3'] = intval($params->courseid);
        $this->params['cx4'] = intval($params->courseid);

        $data = $DB->get_record_sql("SELECT
			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter1 AND ((g.finalgrade/g.rawgrademax)*100 ) < 60) AS grade_f,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter2 AND ((g.finalgrade/g.rawgrademax)*100 ) > 60 and ((g.finalgrade/g.rawgrademax)*100 ) < 70) AS grade_d,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter3 AND ((g.finalgrade/g.rawgrademax)*100 ) > 70 and ((g.finalgrade/g.rawgrademax)*100 ) < 80) AS grade_c,


			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter4 AND ((g.finalgrade/g.rawgrademax)*100 ) > 80 and ((g.finalgrade/g.rawgrademax)*100 ) < 90) AS grade_b,

			(SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
			g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
			$sql_filter5 AND ((g.finalgrade/g.rawgrademax)*100 ) > 90) AS grade_a,

			(SELECT COUNT(DISTINCT param) FROM {local_intelliboard_tracking} WHERE page = 'module' AND courseid = :cx1 $sql_filter6) as  modules_visited,

			(SELECT count(id) FROM {course_modules} WHERE visible = 1 AND course = :cx2) as modules_all,

			(SELECT count(id) FROM {course_modules} WHERE visible = 1 and completion > 0 AND course = :cx3) as modules,

			(SELECT count(cmc.id) FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cm.course = :cx4 $sql_filter7) as modules_completed
			$sql_select
		", $this->params);

        return array("data" => $data, "timestart"=>$params->timestart, "timefinish"=>$params->timefinish);
    }

    public function report89($params)
    {
        $columns = array_merge(array(
            "emploee_id",
            "emploee_name",
            "manager_name",
            "tr.form_origin",
            "tr.complited_date",
            "tr.education",
            "position",
            "job_title",
            "overal_rating",
            "overal_perfomance_rating",
            "behaviors_rating",
            "promotability",
            "mobility",
            "tr.behaviors_growth",
            "tr.behaviors_accountability",
            "tr.behaviors_champions",
            "tr.behaviors_self_aware",
            "tr.behaviors_initiative",
            "tr.behaviors_judgment",
            "tr.behaviors_makes_people",
            "tr.behaviors_leadership",
            "tr.behaviors_effective_com",
            "tr.behaviors_gets_result",
            "tr.behaviors_integrative",
            "tr.behaviors_intelligent"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        return $this->get_report_data("SELECT tr.id,
				tr.user_id as emploee_id,
				CONCAT(u.firstname, ' ', u.lastname) as emploee_name,
				tr.manager as manager_name,
				tr.form_origin,
				tr.education,
				ps.fullname AS position,
				tr.title as job_title,
				tr.overal_review_rating as overal_rating,
				tr.goals_perfomance_overal as overal_perfomance_rating,
				tr.behaviors_overal as behaviors_rating,
				tr.behaviors_growth,
				tr.behaviors_accountability,
				tr.behaviors_champions,
				tr.behaviors_self_aware,
				tr.behaviors_initiative,
				tr.behaviors_judgment,
				tr.behaviors_makes_people,
				tr.behaviors_leadership,
				tr.behaviors_effective_com,
				tr.behaviors_gets_result,
				tr.behaviors_integrative,
				tr.behaviors_intelligent,
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
			FROM {local_talentreview} tr
				LEFT JOIN {user} u ON u.id = tr.user_id
				LEFT JOIN {pos_assignment} pa ON pa.userid = u.id
				LEFT JOIN {pos} ps ON ps.id = pa.positionid
			WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }
    public function report90($params)
    {
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

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom2,'o.id');
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        $data = $this->get_report_data("
            SELECT gi.id,
				gi.itemname as activity,
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
            FROM {grade_outcomes} o
                LEFT JOIN {course} c ON c.id = o.courseid
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN {scale} sci ON sci.id = o.scaleid
                LEFT JOIN {grade_items} gi ON gi.outcomeid = o.id
                LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id
            WHERE gi.itemtype = 'mod' $sql_filter
            GROUP BY gg.itemid $sql_having $sql_order", $params, false);

        foreach($data as $k=>$v){
            $scale = explode(',', $v->scale);
            $percent = $v->average_grade / count($scale);
            $iter = 1 / count($scale);
            $index = round( ($percent / $iter), 0, PHP_ROUND_HALF_DOWN)-1;
            $data[$k]->scale = (isset($scale[$index]))?$scale[$index]:'';
        }

        return array("data"=>$data);
    }


    public function report91($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "cs.section",
            "activity",
            "completed"),
            $this->get_filter_columns($params)
        );
        $sql_columns =  $this->get_modules_sql('');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "cmc.userid", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql1 = $this->get_filterdate_sql($params, "cmc.timemodified");

        $data = $this->get_report_data("
            SELECT cm.id,
				cm.visible as module_visible,
				cs.section,
				cs.name,
				cs.visible,
				c.fullname,
				COUNT(DISTINCT cmc.id) as completed
				$sql_columns
			FROM {course} c
				LEFT JOIN {course_modules} cm ON cm.course = c.id
				LEFT JOIN {modules} m ON m.id = cm.module
				LEFT JOIN {course_sections} cs ON cs.id = cm.section AND cs.course = cm.course
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate = 1 $sql1
			WHERE 1 $sql_filter
			GROUP BY cm.id $sql_having $sql_order", $params, false);

        return array("data" => $data, "timestart"=>$params->timestart, "timefinish"=>$params->timefinish);
    }
    public function report92($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "u.timecreated",
            "u.firstaccess",
            "u.lastaccess",
            "u.lastlogin"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        if($params->custom2 == 6){
        	$this->params['lsta'] = strtotime("-90 days");
        }elseif($params->custom2 == 5){
        	$this->params['lsta'] = strtotime("-30 days");
        }elseif($params->custom2 == 4){
        	$this->params['lsta'] = strtotime("-17 days");
        }elseif($params->custom2 == 3){
        	$this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 2){
        	$this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 1){
        	$this->params['lsta'] = strtotime("-3 days");
        }else{
        	$this->params['lsta'] = strtotime("-1 days");
        }
        $sql_filter .= " AND u.lastaccess < :lsta";
        $sql_join = "";

        if($params->custom){
	       	$sql_filter .= $this->get_filter_in_sql($params->custom, 'ra.roleid');
	        $sql_join = "LEFT JOIN {role_assignments} ra ON ra.userid = u.id ";
	    }

        return $this->get_report_data("
            SELECT DISTINCT u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.timecreated,
                u.firstaccess,
                u.lastaccess,
                u.lastlogin
                $sql_columns
            FROM {user} u
            	$sql_join
            WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }

    public function report93($params)
    {
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
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        return $this->get_report_data("
            SELECT ue.id,
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
            FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
                LEFT JOIN (SELECT course, count(id) as modules FROM {course_modules} WHERE visible = 1 AND completion > 0 GROUP BY course) as m ON m.course = c.id
                LEFT JOIN (SELECT cm.course, x.userid, COUNT(DISTINCT x.id) as completed FROM {course_modules} cm, {course_modules_completion} x WHERE x.coursemoduleid = cm.id AND cm.visible = 1 AND x.completionstate = 1 GROUP BY cm.course, x.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid

            WHERE 1 $sql_filter
            GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
    }
    public function report94($params)
    {
        $columns = array_merge(array("u.id", "u.firstname", "u.lastname", "u.email", "submitted", "attempted","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT u.id,
               u.firstname,
               u.lastname,
               u.email,
               u.phone2,
               u.institution,
               u.department,
               u.address,
               u.city,
               u.country,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
               $sql_columns
            FROM {quiz_attempts} qa, {user} u, {quiz} q, {course} c
            WHERE qa.quiz = q.id AND c.id = q.course AND qa.userid = u.id $sql_filter
            GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report95($params)
    {
        $columns = array_merge(array( "c.fullname", "submitted", "attempted"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT c.id,
               c.fullname,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
            FROM {quiz_attempts} qa, {quiz} q, {course} c
            WHERE qa.quiz = q.id AND c.id = q.course $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report96($params)
    {
        $columns = array_merge(array( "co.name", "submitted", "attempted"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'co.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT co.id,
               co.name,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
            FROM {quiz_attempts} qa, {quiz} q, {course} c, {cohort} co, {cohort_members} cm
            WHERE qa.quiz = q.id AND c.id = q.course AND cm.userid = qa.userid AND co.id = cm.cohortid $sql_filter
            GROUP BY co.id $sql_having $sql_order", $params);
    }
    public function report97($params)
    {
        $columns = array_merge(array("c.id", "c.fullname", "c.startdate", "ue.enrolled", "x.users", "timespend", "visits"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_enrolfilter = $this->get_filter_enrol_sql($params, "ue.");
        $sql_enrolfilter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_timefilter = $this->get_filterdate_sql($params, "l.timepoint");

        return $this->get_report_data("
			SELECT c.id,
			       c.fullname,
			       c.startdate,
			       x.users,
			       ue.enrolled,
			       x.timespend,
			       x.visits
			FROM {course} c
			LEFT JOIN (SELECT e.courseid, COUNT(DISTINCT ue.userid) AS enrolled
				FROM {user_enrolments} ue, {enrol} e
				WHERE ue.enrolid = e.id $sql_enrolfilter
				GROUP BY e.courseid) ue ON ue.courseid = c.id
			LEFT JOIN (SELECT t.courseid, COUNT(DISTINCT t.userid) as users, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits
				FROM  {local_intelliboard_tracking} t, {local_intelliboard_logs} l
				WHERE l.trackid = t.id $sql_timefilter
				GROUP BY t.courseid) x ON x.courseid = c.id
			WHERE 1 $sql_filter $sql_having $sql_order ", $params);
    }

    public function report98($params)
    {
        $columns = array_merge(array("u.id", "u.firstname", "u.lastname", "u.email", "c.fullname", "timespend", "visits"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "l.timepoint");

        return $this->get_report_data("
		    SELECT t.id as tid, u.id,
	           u.firstname,
	           u.lastname,
	           u.email,
	           c.fullname,
	           SUM(l.timespend) AS timespend,
	           SUM(l.visits) AS visits
			FROM  {user} u, {course} c, {local_intelliboard_tracking} t, {local_intelliboard_logs} l
			WHERE l.trackid = t.id AND c.id = t.courseid AND u.id = t.userid $sql_filter
			GROUP BY t.userid, t.courseid $sql_having $sql_order", $params);
    }

    public function report78($params)
    {
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

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        return $this->get_report_data("
            SELECT u.id,
				u.firstname,
				u.lastname,
				u.middlename,
				u.email,
				u.idnumber,
				u.username,
				u.phone1,
				u.phone2,
				u.institution,
				u.department,
				u.address,
				u.city,
				u.country,
				u.auth,
				u.confirmed,
				u.suspended,
				u.deleted,
				u.timecreated,
				u.timemodified,
				u.firstaccess,
				u.lastaccess,
				u.lastlogin,
				u.currentlogin,
				u.lastip $sql_columns
			FROM {user} u
			WHERE 1 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }

    public function report74($params){

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
                $this->params['startdate'.$start] = strtotime("$start/1/$year");
                $this->params['enddate'.$start] = strtotime("$start/1/$year +1 month");
                $this->params['position'.$start] = $position;
                $sql_select .= ", k$start.users as month_$start";
                $sql_join .= "LEFT JOIN (SELECT p.organisationid, COUNT(distinct u.id) as users FROM {user} u, {pos_assignment} p, {pos} ps WHERE ps.id = :position$start AND ps.visible = 1 AND p.positionid = ps.id AND p.userid = u.id AND u.timecreated BETWEEN :startdate$start AND :enddate$start GROUP BY p.organisationid) k$start ON  k$start.organisationid = o.id ";
                $start++;
            }
        }

        $data = $this->get_report_data("
				SELECT 	o.id,
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
				WHERE o.visible = 1 ORDER BY o.typeid, o.fullname",$params,false);

        return array(
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data);

    }

    public function report71($params){
        $columns = array_merge(array("user","ue.timecreated", "e.enrol", "e.cost", "c.fullname"), $this->get_filter_columns($params));
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $data = $this->get_report_data("
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
			WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter $sql_having $sql_order", $params, false);


        $data2 = $this->get_report_data("
            SELECT floor(ue.timecreated / 86400) * 86400 as timepoint,
                   SUM(e.cost) as amount
            FROM {user_enrolments} ue, {enrol} e,{course} c,{user} u
            WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter
            GROUP BY floor(ue.timecreated / 86400) * 86400
            ORDER BY timepoint ASC", $params, false);

        return array("data2" => $data2, "data" => $data);
    }
    public function report70($params){
        global $DB;

        $columns = array("c.fullname", "forum", "d.name", "posts", "fp.student_posts", "ratio", "d.timemodified", "user", "");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filterdate_sql($params,'d.timemodified');
        $sql1 = $this->get_filterdate_sql($params,'d.timemodified');

        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        $params->custom2 = clean_param($params->custom2, PARAM_SEQUENCE);

        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->custom,'d.forum');

    	if(isset($params->custom2) and $params->custom2){
        	$roles = $this->get_filter_in_sql($params->custom2,'ra.roleid');
    	}else{
	        $roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
	    }


        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        $data2 = $DB->get_records_sql("
                  SELECT  floor(p.created / 86400) * 86400 as timepoint,
                          count(distinct p.id) as posts
                  FROM {role_assignments} ra
                    LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {forum_discussions} d ON d.course = c.id
                    LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id
                  WHERE ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 $sql_filter $roles
                  GROUP BY floor(p.created / 86400) * 86400
                  ORDER BY timepoint ASC", $this->params);

        $data3 = $DB->get_records_sql("
                  SELECT  floor(p.created / 86400) * 86400 as timepoint,
                          count(distinct p.id) as student_posts
                  FROM {role_assignments} ra
                    LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {forum_discussions} d ON d.course = c.id
                    LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 $sql_filter $learner_roles
                  GROUP BY floor(p.created / 86400) * 86400
                  ORDER BY timepoint ASC", $this->params);

        $data4 = $DB->get_record_sql("
                  SELECT  count(distinct p.id) as posts
                  FROM {role_assignments} AS ra
                    LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {forum_discussions} d ON d.course = c.id
                    LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50 $sql_filter $roles", $this->params);

        $data5 = $DB->get_record_sql("
                  SELECT count(distinct p.id) as posts
                  FROM {role_assignments} AS ra
                    LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {forum_discussions} d ON d.course = c.id
                    LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50 $sql_filter $learner_roles", $this->params);

        $f1 = intval($data4->posts);
        $f2 = intval($data5->posts);
        $f3 = $f1 / $f2;
        $f3 = number_format($data5->posts, $f3);

        $data6 = array($f1, $f2, $f3);


        $data = $this->get_report_data("
				SELECT @x:=@x+1 as id,
					c.fullname,
					d.name,
					f.name as forum,
					CONCAT(u.firstname, ' ', u.lastname) as user,
					count(distinct p.id) as posts, d.timemodified,
					fp.student_posts, round((count(distinct p.id) / fp.student_posts ), 2) as ratio
				FROM (SELECT @x:= 0) AS x, {role_assignments} AS ra
				LEFT JOIN {user} u ON u.id = ra.userid
				LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
				LEFT JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {forum_discussions} d ON d.course = c.id
				LEFT JOIN {forum} f ON f.id = d.forum
				LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion =d.id
				LEFT JOIN (
                       SELECT d.id, count(distinct p.id) as student_posts FROM {role_assignments} AS ra
                          LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                          LEFT JOIN {course} c ON c.id = ctx.instanceid
                          LEFT JOIN {forum_discussions} d ON d.course = c.id
                          LEFT JOIN {forum_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                       WHERE ctx.contextlevel = 50 $sql1 $learner_roles
                       GROUP BY p.discussion
				   ) fp ON fp.id = d.id
				WHERE ctx.contextlevel = 50 AND p.discussion > 0 $sql_filter $roles
				GROUP BY  d.id, ra.userid $sql_having $sql_order", $params, false);



        return array( "data"            => $data,
            "data2"            => $data2,
            "data3"            => $data3,
            "data6"            => $data6);
    }
    public function report67($params){
        $columns = array_merge(array("l.timecreated", "l.userid", "user", "u.email", "course", "l.objecttable", "activity", "l.origin", "l.ip"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "l.courseid", "courses");
        $sql_filter .= ($params->courseid) ? $this->get_filter_in_sql($params->courseid,'l.courseid') : "";
        $sql_filter .= $this->get_filterdate_sql($params, "l.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        $list = clean_param($params->custom, PARAM_SEQUENCE);
        if($list){
            $sql_columns .=  $this->get_modules_sql($list);
            $sql_filter .= $this->get_filter_in_sql($list,'m.id');
        }else{
            $sql_columns .=  $this->get_modules_sql('');
        }

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid,'ch.cohortid');

        }

        return $this->get_report_data("
                SELECT l.id,
                    l.courseid,
                    l.userid,
                    l.contextinstanceid AS cmid,
                    l.objecttable,
                    l.origin,
                    l.ip,
                    c.fullname AS course,
                    u.email,
                    CONCAT(u.firstname, ' ', u.lastname) AS user,
                    l.timecreated
                    $sql_columns
				FROM {logstore_standard_log} l
                    LEFT JOIN {course} c ON c.id = l.courseid
                    LEFT JOIN {user} u ON u.id = l.userid
                    LEFT JOIN {modules} m ON m.name = l.objecttable
                    LEFT JOIN {course_modules} cm ON cm.id = l.contextinstanceid
                    $sql_join
				WHERE l.component LIKE '%mod_%' $sql_filter $sql_having $sql_order", $params);
    }
    public function report68($params){
        $columns = array("qz.name", "ansyes", "ansno");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "qz.course", "courses");
        $sql_select = "";
        $sql_from = "";

        $sql_filter .= $this->get_filter_in_sql($params->custom,'qz.id');

        if($params->courseid){
            $sql_filter .= $this->get_filter_in_sql($params->courseid,'qz.course');
            $sql_filter .= " AND c.id = qz.course ";
            $sql_select .= ", c.fullname as course";
            $sql_from .= ", {course} c";
        }
        if($params->users){
            $sql_filter .= $this->get_filter_in_sql($params->users,'qt.userid');
            $users = explode(",", $params->users);
            if(count($users) == 1 and !empty($users)){
                $sql_select .= ", CONCAT(u.firstname, ' ', u.lastname) as username";
                $sql_from .= ", {user} u";
                $sql_filter .= " AND u.id = qt.userid";
            }else{
                $sql_select .= ", '' as username";
                $sql_from .= "";
            }
        }
        if($params->cohortid){
            if($params->custom2){
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
                $this->params['cohortid3'] = $params->cohortid;
                $sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE (a.clusterid = :cohortid1 OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = :cohortid2)) AND b.cuserid = a.userid)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt";
                $sql_select .= ", cm.cohorts";
                $sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE id IN (:cohortid3)) cm";
            }else{
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
                $sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE cohortid  IN (:cohortid1))";
                $sql_group = "GROUP BY qt.quiz, qt.attempt";
                $sql_select .= ", cm.cohorts";
                $sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE id  IN (:cohortid2)) cm";
            }
        }else{
            $sql_group = "GROUP BY qt.quiz, qt.attempt";
        }

        return $this->get_report_data("
                SELECT qas.id, qt.id AS attempt,
				    qz.name,
					qt.userid,
					qt.timestart,
					qt.quiz,
					qt.attempt,
				    SUM(IF(d.value=0,1,0)) AS ansyes,
				    SUM(IF(d.value=1,1,0)) AS ansno,
				    SUM(IF(d.value=2,1,0)) AS ansne,
				    (SELECT MAX(attempt) FROM {quiz_attempts}) AS attempts $sql_select
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
				    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state <> 'inprogress'  $sql_filter
				$sql_group $sql_having
				ORDER BY qt.attempt ASC", $params);
    }


    public function report69($params){

        $columns = array("qz.name", "ansyes", "ansno");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "qz.course", "courses");

        $sql_select = "";
        $sql_from = "";
        $sql_attempts = "";

        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        if($params->custom){
            $sql_filter .= $this->get_filter_in_sql($params->custom,'qz.id');
            $sql_attempts = " WHERE ".$this->get_filter_in_sql($params->custom,'quiz',false);
        }
        if($params->courseid){
            $sql_filter .= $this->get_filter_in_sql($params->courseid,'qz.course');
            $sql_filter .= " AND c.id = qz.course ";
            $sql_select .= ", c.fullname as course";
            $sql_from .= " {course} c,";

        }
        if($params->cohortid){
            if($params->custom2){
                $in_sql = $this->get_filter_in_sql($params->cohortid,'a.clusterid',false);
                $in_sql2 = $this->get_filter_in_sql($params->cohortid,'parent',false);
                $sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE ($in_sql OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE $in_sql2)) AND b.cuserid = a.userid)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
                $sql_select .= ", cm.cohorts";
                $in_sql = $this->get_filter_in_sql($params->cohortid,'id',false);
                $sql_from .= "(SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE $in_sql) cm, ";
            }else{
                $in_sql = $this->get_filter_in_sql($params->cohortid,'cohortid',false);
                $sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE $in_sql)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";

                $in_sql = $this->get_filter_in_sql($params->cohortid,'id',false);
                $sql_select .= ", cm.cohorts";
                $sql_from .= " (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE $in_sql) cm,";
            }
        }else{
            $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
        }
        if($params->users){
            $in_sql = $this->get_filter_in_sql($params->users,'qt.userid',false);
            $data = $this->get_report_data("
                SELECT qas.id, qt.id AS attempt,
                    qz.name,
                    qt.userid,
                    COUNT(DISTINCT qt.userid) AS users,
                    qt.timestart,
                    qt.quiz,
                    qt.attempt,
                    SUM(IF(d.value=0,1,0)) AS ansyes,
                    SUM(IF(d.value=1,1,0)) AS ansno,
                    SUM(IF(d.value=2,1,0)) AS ansne,
                    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) AS attempts, t.rawname AS tag, ti.tagid,
                    CONCAT(u.firstname, ' ', u.lastname) AS username $sql_select
                FROM
                    {quiz} qz, {user} u, $sql_from
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
                    $in_sql $sql_filter
                $sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC ", $params, false);

            //$sql_filter .= " AND qt.userid NOT IN ($params->users)";
        }else{
            $data = false;
        }

        $data2 = $this->get_report_data("
             SELECT qas.id, qt.id AS attempt,
				    qz.name,
					qt.userid,
					COUNT(DISTINCT qt.userid) AS users,
					qt.timestart,
					qt.quiz,
					qt.attempt,
				    SUM(IF(d.value=0,1,0)) AS ansyes,
				    SUM(IF(d.value=1,1,0)) AS ansno,
				    SUM(IF(d.value=2,1,0)) AS ansne,
				    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) AS attempts, t.rawname AS tag, ti.tagid $sql_select
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
				$sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC ", $params,false);

        if(!$data and !$params->users){
            $data = $data2;
            $data2 = array();
        }

        return array(
            "data2"			=> 	$data2,
            "data"            => $data);
    }

    public function get_max_attempts($params){
        global $DB;

        $sql = "";
        if($params->filter){
            $sql .= " AND q.course = :course ";
            $this->params['course'] = intval($params->filter);
        }
        if($params->custom){
            $sql .= " AND q.id = :custom ";
            $this->params['custom'] = intval($params->custom);
        }
        return $DB->get_record_sql("
                SELECT
                    (SELECT COUNT(DISTINCT t.tagid) AS tags
                        FROM {quiz} q, {quiz_slots} qs, {tag_instance} t
                        WHERE qs.quizid = q.id AND t.itemid = qs.questionid AND t.itemtype ='question' $sql
                        GROUP BY q.course
                        ORDER BY tags DESC
                        LIMIT 1) AS tags,
                    (SELECT MAX(qm.attempt) FROM {quiz_attempts} qm, {quiz} q WHERE qm.quiz = q.id $sql) AS attempts
               ", $this->params);
    }


    public function report56($params){
        $columns = array("username", "c.fullname", "e.enrol", "l.visits", "l.timespend", "progress", "gc.grade", "cc.timecompleted", "ue.timecreated");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom,'ue.userid',false);
        $this->params['userid'] = $params->userid;

        return $this->get_report_data("
            SELECT ue.id,
                    CONCAT(u.firstname, ' ', u.lastname) AS username,
                    u.id AS userid,
                    ue.timecreated AS enrolled,
                    ROUND(((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
                    c.enablecompletion,
                    cc.timecompleted AS complete,
                    e.enrol AS enrols,
                    l.timespend,
                    l.visits,
                    c.id AS cid,
                    c.fullname AS course,
                    c.timemodified AS start_date,
                    round(((cmc.completed/cmm.modules)*100), 0) AS progress
            FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) AS timespend, SUM(lit.visits) AS visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = ue.userid
                LEFT JOIN (SELECT cm.course, COUNT(cm.id) AS modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) cmm ON cmm.course = c.id
                LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 AND cmc.userid=:userid GROUP BY cm.course) cmc ON cmc.course = c.id AND cmc.userid = ue.userid
            WHERE 1 $sql_filter $sql_having $sql_order", $params);
    }

    function report99($params)
    {

        $columns = array_merge(array("u.firstname","u.lastname","u.email","course","name","dateissued","dateexpire"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_filterdate_sql($params, "bi.dateissued");

        $system_b = ($params->custom == 1)?' OR b.courseid IS NULL ':'';
        if(!empty($params->courseid)){
            $sql_filter .= " AND (" . $this->get_filter_in_sql($params->courseid, 'b.courseid', false) . " $system_b)";
        }else{
            $sql_filter .= " AND (b.courseid IS NOT NULL $system_b)";
        }


        return $this->get_report_data("
            SELECT
              bi.id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              c.fullname as course,
              c.id AS cid,
              b.name,
              bi.dateissued,
              bi.dateexpire
              $sql_columns
            FROM {badge} b
              LEFT JOIN {badge_issued} bi ON bi.badgeid=b.id
              LEFT JOIN {user} u ON u.id=bi.userid
              LEFT JOIN {course} c ON c.id=b.courseid
            WHERE bi.id IS NOT NULL AND bi.visible = 1 $sql_filter $sql_having $sql_order", $params);
    }
	function report99_graph($params)
    {
        $sql_filter = $this->get_filterdate_sql($params, "bi.dateissued");

        $system_b = ($params->custom == 1)?' OR b.courseid IS NULL ':'';
        if(!empty($params->courseid)){
            $sql_filter .= " AND (" . $this->get_filter_in_sql($params->courseid, 'b.courseid', false) . " $system_b)";
        }else{
            $sql_filter .= " AND (b.courseid IS NOT NULL $system_b)";
        }
        unset($params->start);

        return $this->get_report_data("
            SELECT
              bi.id,
              COUNT(bi.id) as badges,
              FROM_UNIXTIME(bi.dateissued,'%Y-%m-%d') as time
            FROM {badge} b
              LEFT JOIN {badge_issued} bi ON bi.badgeid=b.id
            WHERE bi.id IS NOT NULL AND bi.visible = 1 $sql_filter
            GROUP BY time", $params);
    }

    function report33($params)
    {
        $columns = array("c.shortname","cou.fullname","modules","users", "enrolled_student", "users_completed");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_modules = ltrim($this->get_modules_sql(''),' ,');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $courseid = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid,'cc.courseid',false):1;

        return $this->get_report_data("
            SELECT
              c.id,
              c.shortname as competency,
              cou.fullname as course,
              c.path,
              ROUND((COUNT(DISTINCT cu.userid)*100)/COUNT(DISTINCT ra.userid),1) as users,
              COUNT(DISTINCT ra.userid) as enrolled_student,
              COUNT(DISTINCT cou_com.userid) as users_completed,

              GROUP_CONCAT(DISTINCT (SELECT $sql_modules
                FROM mdl_course_modules cm
                  LEFT JOIN mdl_modules m ON m.id=cm.module
                WHERE cm.id = comm.cmid
              )) as modules

            FROM {competency_coursecomp} cc
              LEFT JOIN {competency} c ON c.id=cc.competencyid
              LEFT JOIN {competency_usercompcourse} cu ON cu.courseid=cc.courseid AND cu.competencyid=c.id AND cu.proficiency=1
              LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=cc.courseid
              LEFT JOIN {role_assignments} ra ON ra.contextid=con.id $learner_roles
              LEFT JOIN {competency_modulecomp} comm ON comm.competencyid=c.id
              LEFT JOIN {course} cou ON cou.id=cc.courseid
              LEFT JOIN {course_completions} cou_com ON cou_com.course=cc.courseid AND cou_com.timecompleted>0
            WHERE $courseid $sql_having
            GROUP BY c.id $sql_order", $params);
    }
    function report86($params)
    {
        global $CFG;
        require_once ($CFG->dirroot . "/competency/classes/competency.php");
        $columns = array_merge(array("u.firstname","u.lastname","course","comu.grade","comu.proficiency"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $this->params['competency_id'] = $params->custom;

        $competency = new \core_competency\competency($params->custom);
        $scale = $competency->get_scale();

        $data =  $this->get_report_data("
            SELECT
              u.id,
              u.firstname,
              u.lastname,
              comu.grade,
              comu.proficiency,
              c.fullname as course
              $sql_columns
            FROM {competency} com
              LEFT JOIN {competency_coursecomp} comc ON comc.competencyid=com.id
              LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=comc.courseid
              LEFT JOIN {role_assignments} ra ON ra.contextid=con.id $learner_roles
              LEFT JOIN {user} u ON u.id=ra.userid
              LEFT JOIN {competency_usercompcourse} comu ON comu.competencyid=com.id AND comu.userid=u.id
              LEFT JOIN {course} c ON c.id=comc.courseid
            WHERE com.id=:competency_id AND u.id IS NOT NULL $sql_having
            GROUP BY u.id,c.id $sql_order", $params,false);

        return array('data'=>$data, 'scale'=>$scale->scale_items);
    }
    function get_competency($params)
    {
        global $DB;
        return $DB->get_records('competency',array(),'sortorder ASC','id,shortname');
    }

    public function analytic1($params){
        global $DB;

        $where_sql = "";
        $select_sql = "";

        if(!empty($params->custom) || $params->custom === 0){
            $select_sql = "LEFT JOIN {role_assignments} ra ON log.contextid=ra.contextid and ra.userid=log.userid";
            $params->custom = clean_param($params->custom, PARAM_SEQUENCE);

            $sql_enabled = $this->get_filter_in_sql($params->custom,'ra.roleid',false);
            if(in_array(0,explode(',', $params->custom))){
                $where_sql = "AND ($sql_enabled OR ra.roleid IS NULL)";
            }else{
                $where_sql = "AND $sql_enabled";
            }
        }

        if(empty($params->courseid))
            return array("data" => array());

        $where_sql .= $this->get_filter_in_sql($params->courseid,'courseid');
        $where_sql .= $this->get_filterdate_sql($params, 'timecreated');

        $data = $DB->get_records_sql("
                  SELECT log.id,
                         COUNT(log.id) AS count,
                         WEEKDAY(FROM_UNIXTIME(log.timecreated,'%Y-%m-%d %T')) AS day,
                         IF(FROM_UNIXTIME(log.timecreated,'%H')>=6 && FROM_UNIXTIME(log.timecreated,'%H')<12,'1',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=12 && FROM_UNIXTIME(log.timecreated,'%H')<17,'2',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=17 && FROM_UNIXTIME(log.timecreated,'%H')<=23,'3',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=0 && FROM_UNIXTIME(log.timecreated,'%H')<6,'4','undef')))) AS time_of_day
                  FROM {logstore_standard_log} log
                    $select_sql
                  WHERE 1 $where_sql
                  GROUP BY day,time_of_day
                  ORDER BY time_of_day, day
				", $this->params);

        return array("data" => $data);
    }
    public function analytic2($params){
        global $DB;
        $fields = explode(',',$params->custom2);

        $field_ids = array(0);
        foreach($fields as $field){
            if(strpos($field,'=') > 0){
                list($id,$name) = explode('=',$field);
                $field_ids[] = $id;
            }
        }

        $sql_enabled = $this->get_filter_in_sql(implode(',',$field_ids),'uif.id',false);

        $data = $DB->get_records_sql("
                  SELECT uid.id,
                         uif.id AS fieldid,
                         uif.name,
                         COUNT(uid.userid) AS users,
                         uid.data
                  FROM {user_info_field} uif
                     LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
                  WHERE $sql_enabled
                  GROUP BY uid.data,uif.id
                ", $this->params);

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
                    $join_sql .= (!$enabled_tracking)?" LEFT JOIN (SELECT lit.userid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit GROUP BY lit.userid) lit ON lit.userid = u.id ":'';
                    $select_sql .= ($field == 'total_visits')?' lit.visits as total_visits, ':' lit.timespend as time_spent, ';
                    $coll[] = $field;
                    $enabled_tracking = true;
                }else{
                    if(empty($field)) continue;
                    list($id,$name) = explode('=',$field);
                    $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid={$id} ";
                    $select_sql .= " uid{$id}.data as field_{$id}, ";
                    if($params->custom->field_id != 0){
                        $where[] = " (uid{$id}.fieldid=:field_id{$id} AND uid{$id}.data=:field_value{$id}) ";
                        $this->params["field_id{$id}"] = $params->custom->field_id;
                        $this->params["field_value{$id}"] = $params->custom->field_value;
                    }
                    $coll[] = "field_{$id}";
                }
            }

            if(!empty($where))
                $where_sql = 'AND ('.implode('OR',$where).')';

            $order_sql = $this->get_order_sql($params, $coll);
            $limit_sql = $this->get_limit_sql($params);

            $sql = "SELECT u.id,
						   u.firstname,
						   u.lastname,
						   u.email,
						   $select_sql
						   u.id AS userid
					FROM {user} u
						$join_sql
					WHERE u.id>1 $where_sql
					GROUP BY u.id $order_sql $limit_sql";

            $users = $DB->get_records_sql($sql,$this->params);
            $size = $this->count_records($sql,'id',$this->params);
            return array('users'=>$users,"recordsTotal" => $size,"recordsFiltered" => $size,'data'=>$data);
        }

        $join_sql = $select_sql = '';
        $sql_params = array();
        foreach($fields as $field){
            if($field == 'average_grade' || $field == 'total_visits' || $field == 'time_spent' || $field == 'courses_enrolled'){
                $select_sql .= " 0 as {$field}, ";
            }else{
                if(empty($field)) continue;
                list($id,$name) = explode('=',$field);
                $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid=:fieldid{$id} ";
                $select_sql .= " uid{$id}.data as field_{$id}, ";
                $sql_params["fieldid{$id}"] = $id;
            }

        }
        $user = $DB->get_record_sql("SELECT u.id,
									        u.firstname,
									        u.lastname,
									        u.email,
									        $select_sql
									        u.id AS userid
									 FROM {user} u
										$join_sql
									 WHERE u.id>0
									 LIMIT 1
									",$sql_params);
        return array("data" => $data, 'user'=>$user);
    }

    public function get_quizes($params){
        global $DB;

        $sql = '';
        if(!empty($params->courseid)){
            $sql_enabled = $this->get_filter_in_sql($params->courseid,'q.course',false);
            $sql = " WHERE $sql_enabled";
        }

        $data = $DB->get_records_sql("SELECT q.id,
                                             q.name,
                                             c.id AS courseid,
                                             c.fullname AS coursename
                                      FROM {quiz} q
                                        LEFT JOIN {course} c ON c.id=q.course
                                      $sql
                                     ", $this->params);

        return array('data'=>$data);
    }

    public function analytic3($params){
        global $DB;
        $data = array();
        if(is_numeric($params->custom)){
            $where = '';
            if($params->custom > 0){
                $where .= ' AND q.id=:custom';
                $this->params['custom'] = $params->custom;
            }
            if($params->courseid > 0){
                $where .= " AND q.course=:courseid";
                $this->params['courseid'] = $params->courseid;
            }

            $data = $DB->get_records_sql("
                      SELECT qas.id,
                             que.id,
                             que.name,
                             SUM(IF(qas.state LIKE '%partial' OR qas.state LIKE '%right',1,0)) AS rightanswer,
                             COUNT(qas.id) AS allanswer
                      FROM {quiz} q
                        LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                        LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                        LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
                        LEFT JOIN {question} que ON que.id=qua.questionid
                      WHERE que.id IS NOT NULL $where
                      GROUP BY que.id
                     ", $this->params);

            $time = $DB->get_records_sql("
                      SELECT qa.id,
                             COUNT(qa.id) AS count,
                             WEEKDAY(FROM_UNIXTIME(qa.timefinish,'%Y-%m-%d %T')) AS day,
                             IF(FROM_UNIXTIME(qa.timefinish,'%H')>=6 && FROM_UNIXTIME(qa.timefinish,'%H')<12,'1',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=12 && FROM_UNIXTIME(qa.timefinish,'%H')<17,'2',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=17 && FROM_UNIXTIME(qa.timefinish,'%H')<=23,'3',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=0 && FROM_UNIXTIME(qa.timefinish,'%H')<6,'4','undef')))) AS time_of_day
                     FROM {quiz} q
                        LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.state='finished' AND qa.sumgrades IS NOT NULL
                     WHERE q.id>0 $where
                     GROUP BY day,time_of_day
                     ORDER BY time_of_day, day
                    ", $this->params);

            $grades = $DB->get_records_sql("
                        SELECT gg.id,
                               q.id AS quiz_id,
                               q.name AS quiz_name,
                               ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS gradepass,
                               COUNT(DISTINCT gg.userid) AS users,
                               ROUND(((gg.rawgrade - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS grade
                        FROM {quiz} q
                           LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                           LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid != 2 AND gg.rawgrade IS NOT NULL
                        WHERE gg.rawgrade IS NOT NULL $where
                        GROUP BY ROUND(((gg.rawgrade - gg.rawgrademin)/(gg.rawgrademax - gg.rawgrademin))*100,0),quiz_id
                       ", $this->params);
        }

        return array("data" => $data, "time"=>$time, "grades"=>$grades);
    }
    public function analytic4($params){
        global $DB;

        if(!empty($params->custom)){
            if($params->custom == 'get_countries'){
                $countries = $DB->get_records_sql("
                               SELECT u.id,
                                      u.country,
                                      uid.data AS state,
                                      COUNT(DISTINCT u.id) AS users
                               FROM {user} u
                                  LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                                  LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                               WHERE u.country NOT LIKE ''
                               GROUP BY u.country,uid.data");
                return array("countries" => $countries);
            }else{
                $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "u.country", "state", "course", "e.enrol", "grade", "l.timespend", "complete"), $this->get_filter_columns($params));

                $where = array();
                $where_str = '';
                $custom = unserialize($params->custom);
                if(!empty($custom['country'])){
                    $this->params['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
                    $where[] = "u.country=:country";
                }
                if(isset($custom['state']) && !empty($custom['state'])){
                    $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
                    $where[] = $DB->sql_like('uid.data', ":state", false, false);
                    $this->params['state'] = "%".$custom['state']."%";
                }
                if(isset($custom['enrol']) && !empty($custom['enrol'])){
                    $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
                    $where[] = "e.enrol = :enrol";
                    $this->params['enrol'] = $custom['enrol'];
                }
                if(!empty($where))
                    $where_str = " AND ".implode(' AND ',$where);

                $where_sql = "WHERE u.id IS NOT NULL ".$where_str;
                $order_sql = $this->get_order_sql($params, $columns);
                $limit_sql = $this->get_limit_sql($params);
                $sql_columns = $this->get_columns($params, "u.id");
                $sql = "SELECT ue.id,
                               ROUND(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                               c.enablecompletion,
                               cc.timecompleted AS complete,
                               u.id AS uid,
                               u.email,
                               u.country,
                               uid.data AS state,
                               u.firstname,
                               u.lastname,
                               GROUP_CONCAT(DISTINCT e.enrol) AS enrols,
                               c.id AS cid,
                               c.fullname AS course,
                               l.timespend
							   $sql_columns
						FROM {user} u
						   LEFT JOIN {user_enrolments} ue ON u.id = ue.userid
                           LEFT JOIN {enrol} e ON e.id = ue.enrolid

                           LEFT JOIN {course} c ON c.id = e.courseid
                           LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                           LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                           LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id
                           LEFT JOIN (SELECT lit.userid,
                                             lit.courseid,
                                             sum(lit.timespend) AS timespend
                                      FROM {local_intelliboard_tracking} lit
                                      GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id
                           LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                           LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
						$where_sql
						GROUP BY u.id, c.id $order_sql $limit_sql
						";

                $users = $DB->get_records_sql($sql, $this->params);
                $size = $this->count_records($sql, 'id', $this->params);

                return array("users" => $users,"recordsTotal" => $size,"recordsFiltered" => $size);
            }
        }

        $methods = $DB->get_records_sql("
                     SELECT e.id,
                            e.enrol,
                            COUNT(DISTINCT ue.id) AS users
                     FROM {enrol} e
                        LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
                     WHERE e.id>0
                     GROUP BY e.enrol");

        $countries = $DB->get_records_sql("
                       SELECT u.id,
                              u.country,
                              uid.data AS state,
                              COUNT(DISTINCT u.id) AS users
                       FROM {user} u
                          LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                          LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                       WHERE u.country NOT LIKE ''
                       GROUP BY u.country,uid.data");

        return array("methods" => $methods, "countries" => $countries);
    }
    public function analytic5($params){
        global $DB;
        $params->custom = clean_param($params->custom,PARAM_INT);
        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['custom3'] = $params->custom;

        $data = $DB->get_records_sql("
                  SELECT qa.id,
                         IF((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt),'first-last',
                            IF(qa.userid=min_att.userid AND qa.attempt=min_att.attempt,'first','last')
                         ) AS `attempt_category`,

                         CONCAT(10*FLOOR(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10),
                                '-',
                                10*FLOOR(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10) + 10,
                                '%'
                            ) AS `range`,
                         COUNT(qa.sumgrades) AS count_att
                  FROM {quiz_attempts} qa
                    JOIN (SELECT id,userid, MAX(attempt) AS attempt
                            FROM {quiz_attempts}
                          WHERE quiz=:custom1 GROUP BY userid ) AS max_att
                    JOIN (SELECT id,userid, MIN(attempt) AS attempt
                            FROM {quiz_attempts}
                          WHERE quiz=:custom2 GROUP BY userid ) AS min_att ON max_att.userid=min_att.userid
                    LEFT JOIN {quiz} q ON q.id=qa.quiz
                  WHERE qa.userid != 2 AND qa.quiz=:custom3 AND qa.sumgrades IS NOT NULL AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))
                  GROUP BY `range`,`attempt_category`
                 ", $this->params);

        $overall_info = $DB->get_record_sql("
                          SELECT
                            (SELECT AVG((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100) AS average
                                FROM {quiz_attempts} qa
                                    LEFT JOIN {quiz} q ON q.id=qa.quiz
                                WHERE qa.userid<>2 AND qa.quiz=:custom1 AND qa.attempt=1 AND qa.state='finished'
                            ) AS average_first_att,
                            (SELECT COUNT(qa.id)
                                FROM {quiz_attempts} qa
                                WHERE qa.userid<>2 AND qa.quiz=:custom2 AND qa.state='finished'
                            ) AS count_att,
                            (SELECT COUNT(qa.attempt)/COUNT(DISTINCT qa.userid)
                                FROM {quiz_attempts} qa
                                WHERE qa.userid<>2 AND qa.quiz=:custom3
                            ) AS avg_att
                       ", $this->params);

        return array("data" => $data, 'overall_info'=>$overall_info);
    }
    public function analytic5table($params){
        global $DB;
        $columns = array("que.id", "que.name", "que.questiontext");
        $order_sql = $this->get_order_sql($params, $columns);
        $limit_sql = $this->get_limit_sql($params);
        $params->custom = clean_param($params->custom,PARAM_INT);
        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['custom3'] = $params->custom;

        $sql = "SELECT qas.id,
					   IF((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt),'first-last',
							IF(qa.userid=min_att.userid AND qa.attempt=min_att.attempt,'first','last')
					   ) AS `attempt_category`,
					   que.id AS questionid,
					   que.name,
					   que.questiontext,
					   AVG(((qas.fraction-qua.minfraction)/(qua.maxfraction-qua.minfraction))*100) as scale,
					   COUNT(qa.id) AS count_users
				FROM {quiz} q
					JOIN (SELECT id,userid, MAX(attempt) AS attempt
							FROM {quiz_attempts}
						  WHERE quiz=:custom1 AND userid != 2 GROUP BY userid ) AS max_att
					JOIN (SELECT id,userid, MIN(attempt) AS attempt
							FROM {quiz_attempts}
						  WHERE quiz=:custom2 AND userid != 2 GROUP BY userid ) AS min_att ON max_att.userid=min_att.userid
					LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))
					LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
					LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.sequencenumber = (SELECT MAX(sequencenumber) FROM {question_attempt_steps} WHERE questionattemptid = qua.id)
					LEFT JOIN {question} que ON que.id=qua.questionid
				WHERE q.id=:custom3
				GROUP BY `attempt_category`,que.id $order_sql $limit_sql";

        $question_info = $DB->get_records_sql($sql, $this->params);
        $size = $this->count_records($sql, 'id', $this->params);

        return array('question_info'=>$question_info,"recordsTotal" => $size,"recordsFiltered" => $size);
    }

    public function analytic6($params){
        global $DB;
        $params->custom = clean_param($params->custom,PARAM_INT);

        $sql_enabled_learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $this->params['custom1'] = $params->custom;
        $this->params['courseid'] = $params->courseid;
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        $interactions = $DB->get_records_sql("
                          SELECT log.id,
                                 COUNT(log.id) AS `all`,
                                 SUM(IF(log.userid=:custom1 ,1,0)) AS user,
                                 FROM_UNIXTIME(log.timecreated,'%m/%d/%Y') AS `day`
                          FROM {context} c
                              LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                              LEFT JOIN {logstore_standard_log} log ON c.instanceid=log.courseid AND ra.userid=log.userid
                          WHERE c.instanceid=:courseid AND c.contextlevel=50 AND log.timecreated BETWEEN :timestart AND :timefinish
                          GROUP BY `day`
                          ORDER BY day DESC
                         ", $this->params);

        $access = $DB->get_records_sql("
                    SELECT log.id,
                           COUNT(log.id) AS `all`,
                           SUM(IF(log.userid=:custom1 ,1,0)) AS user,
                           FROM_UNIXTIME(log.timecreated,'%m/%d/%Y') AS `day`
                    FROM {context} c
                        LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                        LEFT JOIN {logstore_standard_log} log ON c.instanceid=log.courseid AND ra.userid=log.userid
                    WHERE c.instanceid=:courseid AND c.contextlevel=50 AND log.target='course' AND log.action='viewed' AND log.timecreated BETWEEN :timestart AND :timefinish
                    GROUP BY `day`
                    ORDER BY day DESC
                  ", $this->params);

        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['courseid2'] = $params->courseid;
        $this->params['courseid3'] = $params->courseid;
        $timespend = $DB->get_record_sql("
                          SELECT SUM(t.timespend) AS `all`,
                                 tu.timespend AS user
                          FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN (SELECT lit.userid, SUM(lit.timespend) AS timespend
                                          FROM {local_intelliboard_tracking} lit
                                          WHERE lit.courseid=:courseid
                                          GROUP BY lit.userid) t ON t.userid=ra.userid
                            LEFT JOIN (SELECT lit.userid, SUM(lit.timespend) AS timespend
                                        FROM {local_intelliboard_tracking} lit
                                        WHERE lit.courseid=:courseid2 AND lit.userid=:custom1) tu ON tu.userid=:custom2
                          WHERE c.instanceid=:courseid3 AND c.contextlevel=50
                        ", $this->params);

        $count_students = $DB->get_record_sql("
                               SELECT COUNT(DISTINCT ra.userid) AS students
                               FROM {context} c
                                    LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                               WHERE c.instanceid=:courseid AND c.contextlevel=50
                              ", $this->params);

        $user_quiz = $DB->get_records_sql("
                       SELECT qa.id,
                             COUNT(qa.id) AS `all`,
                             SUM(IF(qa.userid=:custom1,1,0)) AS `user`,
                             FROM_UNIXTIME(qa.timefinish,'%m/%d/%Y') AS `day`
                       FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {quiz} q ON q.course=c.instanceid
                            LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.userid=ra.userid AND qa.state='finished'
                       WHERE c.instanceid=:courseid AND c.contextlevel=50 AND qa.id IS NOT NULL AND qa.timefinish BETWEEN :timestart AND :timefinish
                       GROUP BY `day`
                      ", $this->params);

        $user_assign = $DB->get_records_sql("
                         SELECT asub.id,
                                COUNT(asub.id) as `all`,
                                SUM(IF(asub.userid=:custom1,1,0)) as `user`,
                                FROM_UNIXTIME(asub.timemodified,'%m/%d/%Y') as `day`
                         FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {assign} a ON a.course=c.instanceid
                            LEFT JOIN {assign_submission} asub ON asub.assignment=a.id AND asub.userid=ra.userid AND asub.status='submitted'
                         WHERE c.instanceid=:courseid AND c.contextlevel=50 AND asub.id IS NOT NULL AND asub.timemodified BETWEEN :timestart AND :timefinish
                         GROUP BY `day`
                        ", $this->params);

        $score = $DB->get_record_sql("
                    SELECT
                      (SELECT round(avg((g.finalgrade/g.rawgrademax)*100), 0)
                          FROM {grade_items} gi, {grade_grades} g
                          WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = :courseid
                      ) AS avg,
                      (SELECT round(((g.finalgrade/g.rawgrademax)*100), 0)
                          FROM {grade_items} gi, {grade_grades} g
                          WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = :courseid2 AND g.userid = :custom1
                      ) AS user
                ", $this->params);
        return array("interactions"  => $interactions,
            "access"        => $access,
            "timespend"     => $timespend,
            "user_quiz"     => $user_quiz,
            "user_assign"   => $user_assign,
            "score"         => $score,
            "count_students"=> $count_students);
    }

    public function analytic7($params){
        global $DB;

        $sql_enabled_learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_enabled_courseid = $this->get_filter_in_sql($params->courseid,'c.instanceid');

        $countries = $DB->get_records_sql("
                       SELECT u.id,
                              u.country,
                              uid.data AS state,
                              COUNT(DISTINCT u.id) AS users
                        FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {user} u ON u.id=ra.userid
                            LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                            LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ra.userid
                        WHERE c.contextlevel=50 $sql_enabled_courseid AND u.id IS NOT NULL
                        GROUP BY u.country,uid.data
                       ", $this->params);

        if($params->custom == 'get_countries'){
            return array("countries" => $countries);
        }

        $sql_course = $this->get_filter_in_sql($params->courseid,'e.courseid');
        $enroll_methods = $DB->get_records_sql("
                            SELECT e.id,
                                   e.enrol,
                                   COUNT(DISTINCT ue.id) AS users
                            FROM {enrol} e
                                LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
                            WHERE e.id>0 $sql_course
                            GROUP BY e.enrol
                         ", $this->params);

        $complettions = $DB->get_record_sql("
                         SELECT SUM(IF(gg.finalgrade>gi.grademin AND cc.timecompleted IS NULL,1,0)) AS not_completed,
                                SUM(IF(cc.timecompleted>0,1,0)) AS completed,
                                SUM(IF(cc.timestarted>0 AND cc.timecompleted IS NULL AND (gg.finalgrade=gi.grademin OR gg.finalgrade IS NULL),1,0)) AS in_progress
                         FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {user} u ON u.id=ra.userid
                            LEFT JOIN {course_completions} cc ON cc.course=c.instanceid AND cc.userid=u.id
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.instanceid
                            LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
                         WHERE c.contextlevel=50 $sql_enabled_courseid AND u.id IS NOT NULL
                        ", $this->params);

        $grade_range = $DB->get_records_sql("
                         SELECT CONCAT(10*FLOOR((((gg.finalgrade-gi.grademin)/(gi.grademax-gi.grademin))*100)/10),
                                         '-',
                                         10*FLOOR((((gg.finalgrade-gi.grademin)/(gi.grademax-gi.grademin))*100)/10) + 10,
                                         '%'
                                  ) as `range`,
                                COUNT(DISTINCT gg.userid) AS users
                         FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {grade_items} gi ON gi.courseid=c.instanceid AND gi.itemtype='course'
                            LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=ra.userid
                         WHERE c.contextlevel=50 $sql_enabled_courseid AND gg.rawgrademax IS NOT NULL
                         GROUP BY `range`
                        ", $this->params);

        return array("countries"      => $countries,
            "enroll_methods" => $enroll_methods,
            "complettions"   => $complettions,
            "grade_range"    => $grade_range);
    }

    public function analytic7table($params){
        global $DB;

        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "c.fullname", "u.country", "uid.data", "e.enrol", "l.visits", "l.timespend", "grade", "cc.timecompleted", "ue.timecreated"), $this->get_filter_columns($params));

        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_limit = $this->get_limit_sql($params);

        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'ra.roleid',false);
        $where = array($sql_enabled);
        $where_str = '';
        $custom = unserialize($params->custom);
        if(!empty($custom['country']) && $custom['country'] != 'world'){
            $this->params['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
            $where[] = "u.country=:country";
        }
        if(isset($custom['state']) && !empty($custom['state'])){
            $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
            $where[] = $DB->sql_like('uid.data', ":state", false, false);
            $this->params['state'] = "%(".$custom['state'].")%";

        }
        if(isset($custom['enrol']) && !empty($custom['enrol'])){
            $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
            $where[] = $DB->sql_like('e.enrol', ":enrol", false, false);
            $this->params['enrol'] = "%".$custom['enrol']."%";

        }
        if(isset($custom['grades']) && !empty($custom['grades'])){
            $custom['grades'] = clean_param($custom['grades'],PARAM_ALPHANUMEXT);
            $grades = explode('-',$custom['grades']);
            $grades[1] = (empty($grades[1]))?110:$grades[1];
            $where[] = "ROUND(((g.finalgrade/gi.grademax)*100), 0) BETWEEN :grade_min AND :grade_max";
            $this->params['grade_min'] = $grades[0];
            $this->params['grade_max'] = $grades[1]-0.001;
        }
        if(isset($custom['user_status']) && !empty($custom['user_status'])){
            $custom['user_status'] = clean_param($custom['user_status'],PARAM_INT);
            if($custom['user_status'] == 1){
                $where[] = "(round(((g.finalgrade/gi.grademax)*100), 0)>0 AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
            }elseif($custom['user_status'] == 2){
                $where[] = "cc.timecompleted>0";
            }elseif($custom['user_status'] == 3){
                $where[] = "(cc.timestarted>0 AND (ROUND(((g.finalgrade/gi.grademax)*100), 0)=0 OR g.finalgrade IS NULL) AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
            }
        }
        if(!empty($where))
            $where_str = " AND ".implode(' AND ',$where);

        $where_sql = "WHERE u.id IS NOT NULL ".$where_str;

        $sql = "SELECT ue.id,
                       ue.timecreated AS enrolled,
                       round(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                       c.enablecompletion,
                       cc.timecompleted AS complete,
                       u.id AS uid,
                       u.email,
                       u.country,
                       uid.data AS state,
                       u.firstname,
                       u.lastname,
                       GROUP_CONCAT( DISTINCT e.enrol) AS enrols,
                       l.timespend,
                       l.visits,
                       c.id AS cid,
                       c.fullname AS course,
                       c.timemodified AS start_date
                       $sql_columns
                FROM {user_enrolments} ue
                    LEFT JOIN {enrol} e ON e.id = ue.enrolid
                    LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                    LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                    LEFT JOIN {user} AS u ON u.id = ue.userid
                    LEFT JOIN {course} AS c ON c.id = e.courseid
                    LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid

                    LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                    LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                    LEFT JOIN (SELECT lit.userid,
                                   lit.courseid,
                                   sum(lit.timespend) AS timespend,
                                   sum(lit.visits) AS visits
                                 FROM
                                   {local_intelliboard_tracking} lit
                                 GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                    LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                    LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
                $where_sql $sql_filter
                GROUP BY u.id, c.id
                $sql_order $sql_limit";

        $data = $DB->get_records_sql($sql, $this->params);

        $size = $this->count_records($sql, 'id', $this->params);
        return array(
            "recordsTotal"    => $size,
            "recordsFiltered" => $size,
            "data"            => $data);
    }

    public function analytic8($params){
        global $DB;

        $columns = array("coursename", "cohortname", "learners_completed", "learners_not_completed", "learners_overdue", "avg_grade", "timespend");

        $sql_filter = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'cm.cohortid');

        $sql_order = $this->get_order_sql($params, $columns);
        $sql_limit = $this->get_limit_sql($params);
        $params->custom = clean_param($params->custom, PARAM_INT);
        $this->params['custom'] = ($params->custom)?$params->custom:time();
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        $sql = "SELECT ue.id,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.cohortid,
                       coh.name AS cohortname,
                       round(AVG(((g.finalgrade/gi.grademax)*100)), 0) AS avg_grade,
                       SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>0,1,0)) AS learners_completed,
                       SUM(IF(cr.completion IS NOT NULL AND (cc.timecompleted=0 OR cc.timecompleted IS NULL),1,0)) AS learners_not_completed,
                       SUM(IF(cr.completion IS NOT NULL AND cc.timecompleted>:custom ,1,0)) AS learners_overdue,
                       AVG(l.timespend) AS timespend
                FROM {user_enrolments} ue
                  LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                  LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                  LEFT JOIN {user} u ON u.id = ue.userid
                  LEFT JOIN {course} c ON c.id = e.courseid
                  LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                  LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) AS timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id
                  LEFT JOIN {cohort_members} cm ON cm.userid = u.id
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) AS completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=e.courseid
                WHERE ue.timecreated BETWEEN :timestart AND :timefinish $sql_filter
                GROUP BY c.id,cm.cohortid $sql_order $sql_limit";

        $data = $DB->get_records_sql($sql, $this->params);
        $size = $this->count_records($sql, 'id', $this->params);

        return array(
            "recordsTotal"    => $size,
            "recordsFiltered" => $size,
            "data"            => $data);
    }

    public function analytic8details($params){
        global $DB;
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
            $sql_where = " AND cc.timecompleted>:duedate";
            $this->params['duedate'] = $custom->duedate;
        }

        $columns = array_merge(array("coursename", "cohortname", "learnername", "u.email", "grade", "l.timespend","cc.timecompleted"), $this->get_filter_columns($params));
        $sql_filter = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'cm.cohortid');
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
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        $sql = "SELECT ue.id,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.cohortid,
                       coh.name AS cohortname,
                       ROUND(((g.finalgrade/gi.grademax)*100), 0) AS grade,
                       l.timespend,
                       CONCAT(u.firstname, ' ', u.lastname) AS learnername,
                       u.email,
                       cc.timecompleted
                       $sql_columns
                FROM {user_enrolments} ue
                  LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
                  LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                  LEFT JOIN {user} AS u ON u.id = ue.userid
                  LEFT JOIN {course} AS c ON c.id = e.courseid
                  LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                  LEFT JOIN (SELECT lit.userid,
                               lit.courseid,
                               sum(lit.timespend) AS timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                  LEFT JOIN {cohort_members} cm ON cm.userid = u.id
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) AS completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=e.courseid
                WHERE cr.completion IS NOT NULL AND ue.timecreated BETWEEN :timestart AND :timefinish
                $sql_where $sql_filter $sql_order $sql_limit";


        $data = $DB->get_records_sql($sql, $this->params);
        $size = $this->count_records($sql, 'id', $this->params);


        return array(
            "recordsTotal"    => $size,
            "recordsFiltered" => $size,
            "data"            => $data);
    }
    public function analytic9($params){
        global $CFG,$DB;
        require_once($CFG->dirroot .'/course/lib.php');

        $custom = json_decode($params->custom);
        $context = context_course::instance($params->courseid);
        $modinfo = get_fast_modinfo($params->courseid);

        $modules = array_keys(get_module_types_names());
        $sql_cm_end = "";
        $sql_cm_if = array();
        foreach($modules as $module_name){
            $sql_cm_if[] = "IF(m.name='{$module_name}', (SELECT name FROM {{$module_name}} WHERE id = cm.instance)";
            $sql_cm_end .= ")";
        }
        $sql_columns =  ($sql_cm_if) ? ",".implode(",", $sql_cm_if).",'NONE'".$sql_cm_end." AS activity" : "";

        $sql_where = $sql_join = '';
        $sql_where .= $this->get_filter_in_sql($custom->section_number,'sec.section');

        $sql_select_user = 'ra.userid';
        $sql_where_user = '';
        if(!empty($custom->userid)){
            $sql_enabled = $this->get_filter_in_sql($custom->userid,'ra.userid',false);
            $sql_select_user = " IF($sql_enabled,ra.userid,NULL) ";

            $sql_where_user = $this->get_filter_in_sql($custom->userid,'ra.userid');
        }

        $this->params['contextid'] = $context->id;
        $sql_enabled_learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        if(!empty($custom->groupid)){
            $sql_enabled = $this->get_filter_in_sql($custom->groupid,'gm.groupid');

            $sql_join .= " LEFT JOIN {groups_members} gm ON gm.userid=ra.userid $sql_enabled ";
            $sql_select_user = " IF(gm.id IS NOT NULL,$sql_select_user,NULL) ";

            $sql = "SELECT COUNT(DISTINCT ra.userid)
                      FROM {role_assignments} ra
                        $sql_join
                      WHERE ra.contextid=:contextid $sql_enabled_learner_roles AND gm.id IS NOT NULL".$sql_where_user;
        }else{
            $sql = "SELECT COUNT(DISTINCT ra.userid)
                    FROM {role_assignments} ra
                    WHERE ra.contextid=:contextid $sql_enabled_learner_roles".$sql_where_user;
        }

        $enrolled_learners = $DB->count_records_sql($sql,$this->params);

        $this->params['courseid'] = $params->courseid;
        $this->params['courseid1'] = $params->courseid;
        $this->params['courseid2'] = $params->courseid;
        $this->params['courseid3'] = $params->courseid;
        $data = array();
        if($custom->type == 'per_activity'){
            $sql = "SELECT cm.id,
                           sec.section,
                           COUNT(DISTINCT $sql_select_user) AS user_completed
                           $sql_columns
                    FROM {course_modules} cm
                      LEFT JOIN {modules} m ON m.id=cm.module
                      LEFT JOIN {course_sections} sec ON sec.id=cm.section
                      LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate>0
                      LEFT JOIN {role_assignments} ra ON ra.userid=cmc.userid AND ra.contextid=:contextid $sql_enabled_learner_roles
                      $sql_join
                    WHERE cm.course=:courseid AND cm.completion>0 $sql_where GROUP BY cm.id";

            $data = $DB->get_records_sql($sql,$this->params);
        }elseif($custom->type == 'per_activity_type'){
            $sql = "SELECT cm.id,
                           sec.section,
                           COUNT(DISTINCT IF(com.completed=ncom.need_complete,$sql_select_user,NULL)) AS user_completed,
                           m.name AS activity
                    FROM {course_modules} cm
                      LEFT JOIN {modules} m ON m.id=cm.module
                      LEFT JOIN {course_sections} sec ON sec.id=cm.section
                      LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate>0
                      LEFT JOIN {role_assignments} ra ON ra.userid=cmc.userid AND ra.contextid=:contextid $sql_enabled_learner_roles
                      LEFT JOIN (SELECT cm.section,cm.module,cmc.userid, COUNT(DISTINCT cmc.id) AS completed
                                 FROM {course_modules} cm
                                    LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate>0
                                 WHERE cm.course=:courseid1 AND cm.completion>0
                                 GROUP BY cmc.userid,cm.section,cm.module) com ON com.userid=ra.userid AND com.section=cm.section AND com.module=cm.module
                      LEFT JOIN (SELECT cm.section,cm.module,COUNT(DISTINCT cm.id) AS need_complete
                                 FROM {course_modules} cm
                                 WHERE cm.course=:courseid2 AND cm.completion>0
                                 GROUP BY cm.section,cm.module) ncom ON ncom.section=cm.section AND ncom.module=cm.module
                      $sql_join
                    WHERE cm.course=:courseid3 AND cm.completion>0 $sql_where
                    GROUP BY cm.section,cm.module";

            $data = $DB->get_records_sql($sql,$this->params);

            foreach($data as $id=>$object){
                $data[$id]->activity = get_string("modulename", "$object->activity");
            }
        }


        $course_topics = array();
        foreach($modinfo->get_section_info_all() as $number => $section){
            $course_topics[$number] = get_section_name($params->courseid,$section);
        }

        return array(
            "enrolled_users"    => $enrolled_learners,
            "course_topics"    => $course_topics,
            "data"            => $data);
    }
    public function get_course_sections($params){
        global $CFG;
        require_once($CFG->dirroot .'/course/lib.php');

        $modinfo = get_fast_modinfo($params->courseid);

        $course_topics = array();
        foreach($modinfo->get_section_info_all() as $number => $section){
            $course_topics[$number] = get_section_name($params->courseid,$section);
        }

        return array("data" => $course_topics);
    }

    public function get_course_user_groups($params){
        global $DB;

        $data = $DB->get_records('groups',array('courseid'=>$params->courseid),'','id,name');

        return array("data" => $data);
    }


    function get_all_system_info($params){
        global $DB;

        $avg = $DB->get_record_sql('SELECT
                                  (SELECT AVG(round(((g.finalgrade/g.rawgrademax)*100), 0)) AS grade
                                   FROM {grade_items} gi
                                     LEFT JOIN {grade_grades} g ON g.itemid = gi.id
                                   WHERE gi.itemtype = "course" AND g.finalgrade IS NOT NULL) AS grade,
                                  (SELECT SUM(timespend)
                                    FROM {local_intelliboard_tracking}) AS timespent,
                                  (SELECT AVG(timespend)
                                    FROM {local_intelliboard_tracking}) AS avg_timespent ');
        $browsers = $DB->get_records_sql('SELECT lit.useragent,COUNT(DISTINCT lit.userid) AS users
                                            FROM {local_intelliboard_tracking} lit
                                            GROUP BY lit.useragent');
        $http_request = $DB->get_records_sql("SELECT m.id, m.name, SUM(lit.visits) AS visits
                                                FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
                                                WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module GROUP BY m.id");

        return array("browsers"     =>  $browsers,
            "http_request" =>  $http_request,
            "avg"          =>  $avg);
    }

    public function get_course_instructors($params){
        global $DB;

        $sql = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql .= $this->get_filter_user_sql($params, "u.");

        return $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) AS name, u.email
                                     FROM {role_assignments} AS ra
                                        JOIN {user} AS u ON ra.userid = u.id
                                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                                     WHERE ctx.contextlevel = 50 $sql", $this->params);
    }
    public function get_course_discussions($params){
        global $DB;

        $sql = "";
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'course',false);
        }
        return $DB->get_records_sql("SELECT id, name FROM {forum} $sql", $this->params);
    }

    public function get_cohort_users($params){
        global $DB;

        $sql = "";
        if($params->custom2){
            if($params->cohortid){
                $sql = " AND (a.clusterid = :cohortid1 or a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = :cohortid2))";
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
            }
            return $DB->get_records_sql("SELECT DISTINCT b.muserid AS id, CONCAT(u.firstname,' ',u.lastname) AS name
                                          FROM {local_elisprogram_uset_asign} a,{local_elisprogram_usr_mdl} b, {local_elisprogram_usr} u
                                          WHERE a.userid = u.id AND b.cuserid = a.userid AND b.muserid IN (
                                            SELECT DISTINCT userid FROM {quiz_attempts} WHERE state = 'finished') $sql", $this->params);
        }else{
            $sql = $this->get_filter_in_sql($params->cohortid,'cm.cohortid');
            if($params->courseid){
                $sql_enabled = $this->get_filter_in_sql($params->courseid,'e.courseid',false);
                $sql .= " AND u.id IN(SELECT distinct ue.userid FROM {user_enrolments} ue, {enrol} e WHERE $sql_enabled  and ue.enrolid = e.id)";
            }
            return $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) AS name
                                         FROM {user} u, {cohort_members} cm
                                         WHERE cm.userid = u.id AND u.deleted = 0 AND u.suspended = 0 AND u.id IN (
                                            SELECT DISTINCT userid FROM {quiz_attempts} WHERE state = 'finished') $sql", $this->params);
        }
    }
    public function get_users($params){
        global $DB;

        $sql = "";
        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        $sql .= $this->get_filter_in_sql($params->custom,'us.id');
        $sql .= $this->get_filter_user_sql($params, "us.");
        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        $this->params['courseid'] = $params->courseid;
        $data = $DB->get_records_sql("SELECT us.id, CONCAT(us.firstname,' ',us.lastname) AS name
                                        FROM {context} c
                                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled
                                            LEFT JOIN {user} us ON us.id=ra.userid
                                        WHERE us.id IS NOT NULL AND c.contextlevel=50 AND c.instanceid=:courseid $sql
								    ",$this->params);

        return array("data" => $data);
    }

    public function get_grade_letters($params){
        global $DB;

        $data = $DB->get_records_sql("SELECT id,lowerboundary,letter
										FROM {grade_letters}
										WHERE contextid=1
									");

        return array("letters" => $data);
    }

    public function get_questions($params){
        global $CFG, $DB;

        if($CFG->version < 2012120301){
            $sql_extra = "q.questions";
        }else{
            $sql_extra = "qat.layout";
        }

        $this->params['filter'] = intval($params->filter);
        return $DB->get_records_sql("
             SELECT qa.id,
                    ROUND(((qa.maxmark * qas.fraction) * q.grade / q.sumgrades),2) as grade,
                    qa.slot,
                    qu.id as attempt,
                    q.name as quiz,
                    que.name as question,
                    que.questiontext,
                    qas.userid,
                    qas.state,
                    qas.timecreated,
                    FORMAT(((LENGTH($sql_extra) - LENGTH(REPLACE($sql_extra, ',', '')) + 1)/2), 0) as questions
             FROM
                {question_attempts} qa,
                {question_attempt_steps} qas,
                {question_usages} qu,
                {question} que,
                {quiz} q,
                {quiz_attempts} qat,
                {context} cx,
                {course_modules} cm
             WHERE qat.id = :filter
               AND q.id = qat.quiz
               AND cm.instance = q.id
               AND cx.instanceid = cm.id
               AND qu.contextid = cx.id
               AND qa.questionusageid = qu.id
               AND qas.questionattemptid = qa.id
               AND que.id = qa.questionid
               AND qas.state <> 'todo'
               AND qas.state <> 'complete'
               AND qas.userid = qat.userid
             ORDER BY qas.timecreated DESC
					",$this->params);
    }

    public function get_activity($params){
        global $DB;

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
            $sql_params = $this->params;
            $sql_params['timestart'] = $params->timestart;
            $sql_params['timefinish'] = $params->timefinish;
            $data['enrols'] = $DB->get_records_sql("
                    SELECT ue.id,
                           u.id AS uid,
                           CONCAT( u.firstname, ' ', u.lastname ) AS name,
                           u.email,
                           u.username,
                           ue.timecreated AS timepoint,
                           cx.id AS context,
                           c.id AS cid,
                           c.fullname AS course,
                           GROUP_CONCAT( DISTINCT e.enrol) AS enrols,
                           GROUP_CONCAT( DISTINCT r.shortname) AS roles
                    FROM {user_enrolments} ue
                        LEFT JOIN {user} u ON u.id = ue.userid
                        LEFT JOIN {enrol} e ON e.id = ue.enrolid
                        LEFT JOIN {course} c ON c.id = e.courseid
                        LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
                        LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
                        LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
                        LEFT JOIN {role} r ON r.id = ra.roleid
                    WHERE ue.timecreated BETWEEN :timestart AND :timefinish $sql
                    GROUP BY ue.id
                    ORDER BY ue.timecreated DESC
                    LIMIT 10", $sql_params);
        }
        if($config->users){
            $sql_params = $this->params;
            $sql_params['timestart'] = $params->timestart;
            $sql_params['timefinish'] = $params->timefinish;
            $data['users'] = $DB->get_records_sql("
                          SELECT  u.id AS uid,
                                  CONCAT( u.firstname, ' ', u.lastname ) AS name,
                                  u.email, u.username,
                                  u.timecreated AS timepoint,
                                  cx.id AS context,
                                  u.auth
                          FROM {user} u
                              LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
                          WHERE u.timecreated BETWEEN :timestart AND :timefinish $sql
                          ORDER BY u.timecreated DESC
                          LIMIT 10", $sql_params);
        }
        if($config->completions){
            $sql_params = $this->params;
            $sql_params['timestart'] = $params->timestart;
            $sql_params['timefinish'] = $params->timefinish;
            $data['completions'] = $DB->get_records_sql("
                        SELECT cc.id,
                                u.id AS uid,
                                CONCAT( u.firstname, ' ', u.lastname ) AS name,
                                u.email,
                                u.username,
                                cx.id AS context,
                                cc.timecompleted AS timepoint,
                                c.id AS cid,
                                c.fullname AS course
                        FROM {course_completions} cc, {course} c, {user} u
                            LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
                        WHERE u.id = cc.userid AND c.id = cc.course AND cc.timecompleted BETWEEN :timestart AND :timefinish $sql
                        ORDER BY cc.timecompleted DESC
                        LIMIT 10", $sql_params);
        }
        if($config->grades){
            $sql_params = $this->params;
            $sql_params['timestart1'] = $params->timestart;
            $sql_params['timefinish1'] = $params->timefinish;
            $sql_params['timestart2'] = $params->timestart;
            $sql_params['timefinish2'] = $params->timefinish;
            $data['grades'] = $DB->get_records_sql("
                        SELECT g.id,
                               u.id AS uid,
                               CONCAT( u.firstname, ' ', u.lastname ) AS name,
                               u.email, u.username,
                               cx.id AS context,
                               ((g.finalgrade/g.rawgrademax)*100) AS grade,
                               IFNULL(g.timemodified, g.timecreated)  AS timepoint,
                               gi.itemname,
                               gi.itemtype,
                               gi.itemmodule,
                               c.id AS cid,
                               c.fullname AS course
                        FROM {grade_grades} g,
                             {grade_items} gi,
                             {course} c,
                             {user} u
                           LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
                        WHERE gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid AND g.finalgrade IS NOT NULL AND (g.timecreated BETWEEN :timestart1 AND :timefinish1 OR g.timemodified BETWEEN :timestart2 AND :timefinish2) $sql
                        ORDER BY g.timecreated DESC
                        LIMIT 10", $sql_params);
        }
        if($config->online){
            $sql_params = $this->params;
            $sql_params['onlinestart'] = $onlinestart;
            $sql_params['timefinish'] = $params->timefinish;
            $data['online'] = $DB->get_records_sql("
                        SELECT u.id AS uid,
                               CONCAT( u.firstname, ' ', u.lastname ) AS name,
                               u.lastaccess AS timepoint,
                               cx.id AS context
                        FROM {user} u
                            LEFT JOIN {context} cx ON cx.instanceid = u.id AND cx.contextlevel = 30
                        WHERE u.lastaccess BETWEEN :onlinestart AND :timefinish $sql
                        ORDER BY u.timecreated DESC
                        LIMIT 10", $sql_params);
        }

        return $data;
    }
    public function get_total_info($params){
        global $DB;

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
                                        (SELECT COUNT(*) FROM {user} WHERE 1 $sql2) AS users,
                                        (SELECT COUNT(*) FROM {course} WHERE category > 0 $sql3) AS courses,
                                        (SELECT COUNT(*) FROM {course_modules} WHERE 1 $sql4) AS modules,
                                        (SELECT COUNT(*) FROM {course_categories} WHERE visible = 1) AS categories,
                                        (SELECT COUNT(*) FROM {user} WHERE lastaccess > 0 $sql2) AS learners,
			                        $sql_files", $this->params);
    }
    public function get_system_users($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "u.id", "users");
        $sql2 = $this->get_teacher_sql($params, "userid", "users");
        $sql3 = $this->get_teacher_sql($params, "e.userid", "users");

        return $DB->get_record_sql("
        	SELECT
				(SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' $sql) AS users,
				(SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.deleted = 1 $sql) AS deleted,
				(SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.deleted = 0 AND u.suspended = 0 AND u.lastaccess > 0 $sql) AS active,
				(SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND (u.confirmed = 0 OR u.deleted = 1) $sql) AS deactive,
				(SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.suspended = 1 $sql) AS suspended,
				(SELECT COUNT(DISTINCT userid) FROM {course_completions} WHERE timecompleted > 0 $sql2) AS graduated,
				(SELECT COUNT(DISTINCT e.userid) FROM {enrol} ee, {user_enrolments} e WHERE ee.id = e.enrolid $sql3) AS enrolled,
				(SELECT COUNT(DISTINCT e.userid) FROM {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'cohort' AND e.enrolid = ee.id $sql3) AS enrol_cohort,
				(SELECT COUNT(DISTINCT e.userid) FROM {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'manual' AND e.enrolid = ee.id $sql3) AS enrol_manual,
				(SELECT COUNT(DISTINCT e.userid) FROM {enrol} ee, {user_enrolments} e WHERE ee.enrol = 'self' AND e.enrolid = ee.id $sql3) AS enrol_self",
				$this->params);
    }

    public function get_system_courses($params){
        global $DB;

        $sql1 = $this->get_teacher_sql($params, "course", "courses");
        $sql2 = $this->get_teacher_sql($params, "id", "courses");
        $sql3 = $this->get_teacher_sql($params, "cm.course", "courses");
        $sql4 = $this->get_teacher_sql($params, "userid", "users");
        $sql_learner_roles = $this->get_filter_in_sql($params->learner_roles,'roleid',false);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles,'roleid',false);

        return $DB->get_record_sql("SELECT
                                        (SELECT COUNT(*) FROM {course_completions} WHERE timecompleted > 0 $sql1) AS graduates,
                                        (SELECT COUNT(*) FROM {course_modules} WHERE visible = 1 $sql1) AS modules,
                                        (SELECT COUNT(*) FROM {course} WHERE visible = 1 AND category > 0 $sql2) AS visible,
                                        (SELECT COUNT(*) FROM {course} WHERE visible = 0 AND category > 0 $sql2) AS hidden,
                                        (SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE status = 1 $sql4) AS expired,
                                        (SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE $sql_learner_roles $sql4) AS students,
                                        (SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE $sql_teacher_roles $sql4) AS tutors,
                                        (SELECT COUNT(*) FROM {course_modules_completion} WHERE completionstate = 1 $sql4) AS completed,
                                        (SELECT COUNT(DISTINCT (param)) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql4) AS reviewed,
                                        (SELECT COUNT(cm.id) FROM {course_modules} cm, {modules} m WHERE m.name = 'certificate' AND cm.module = m.id $sql3) AS certificates
                                   ", $this->params);
    }

    public function get_system_load($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        return $DB->get_record_sql("SELECT
                                        (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) AS sitetimespend,
                                        (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) AS coursetimespend,
                                        (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) AS activitytimespend,
                                        (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) AS sitevisits,
                                        (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) AS coursevisits,
                                        (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) AS activityvisits
                                   ", $this->params);
    }

    public function get_module_visits($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "lit.userid", "users");
        $sql .= $this->get_filter_module_sql($params, "cm.");

        return $DB->get_records_sql("SELECT m.id,
                                            m.name,
                                            SUM(lit.visits) AS visits
                                     FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
                                     WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module $sql
                                     GROUP BY m.id", $this->params);
    }
    public function get_useragents($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "lit.userid", "users");

        return $DB->get_records_sql("SELECT lit.id,
                                            lit.useragent AS name,
                                            COUNT(lit.id) AS amount
                                     FROM {local_intelliboard_tracking} lit
                                     WHERE lit.useragent != '' $sql
                                     GROUP BY lit.useragent", $this->params);
    }
    public function get_useros($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "lit.userid", "users");

        return $DB->get_records_sql("SELECT lit.id,
                                            lit.useros AS name,
                                            COUNT(lit.id) AS amount
                                     FROM {local_intelliboard_tracking} lit
                                     WHERE lit.useros != '' $sql
                                     GROUP BY lit.useros", $this->params);
    }
    public function get_userlang($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "lit.userid", "users");

        return $DB->get_records_sql("SELECT lit.id,
                                            lit.userlang AS name,
                                            COUNT(lit.id) AS amount
                                     FROM {local_intelliboard_tracking} lit
                                     WHERE lit.userlang != '' $sql
                                     GROUP BY lit.userlang", $this->params);
    }

    //update
    public function get_module_timespend($params){
        global $DB;

        $sql0 = $this->get_teacher_sql($params, "userid", "users");
        $sql = $this->get_teacher_sql($params, "lit.userid", "users");
        $sql .= $this->get_filter_module_sql($params, "cm.");

        return $DB->get_records_sql("SELECT m.id,
                                            m.name,
                                            (SUM(lit.timespend) / (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql0)*100) AS timeval,
                                            sum(lit.timespend) AS timespend
                                     FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
                                     WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module $sql
                                     GROUP BY m.id", $this->params);
    }

    public function get_users_count($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "id", "users");
        $sql .= $this->get_filter_user_sql($params, "");

        return $DB->get_records_sql("SELECT auth,
                                            COUNT(*) AS users
                                     FROM {user}
                                     WHERE 1 $sql
                                     GROUP BY auth", $this->params);
    }
    public function get_most_visited_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "l.courseid", "courses");
        $sql .= $this->get_filter_course_sql($params, "c.");

        if($params->sizemode){
            $sql_columns = ", '-' as grade";
            $sql_join = "";
            $sql_order = "";
        }else{
            $sql_columns = ", gc.grade";
            $sql_join = "LEFT JOIN (SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), 0) AS grade
							FROM {grade_items} gi, {grade_grades} g
							WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
							GROUP BY gi.courseid) as gc ON gc.courseid = c.id";
            $sql_order = " ORDER BY visits DESC ";
        }

        return $DB->get_records_sql("
        	SELECT c.id,
                c.fullname,
                sum(l.visits) AS visits,
                sum(l.timespend) AS timespend $sql_columns
            FROM {local_intelliboard_tracking} l
                LEFT JOIN {course} c ON c.id = l.courseid $sql_join
            WHERE c.category > 0 AND l.courseid > 0 $sql
            GROUP BY l.courseid $sql_order
            LIMIT 10", $this->params);
    }
    public function get_no_visited_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql2 = $this->get_filterdate_sql($params, "lastaccess");

        return $DB->get_records_sql("SELECT c.id,
                                            c.fullname,
                                            c.timecreated
					                 FROM  {course} c
					                 WHERE c.category > 0 AND c.id NOT IN (
					                    SELECT courseid FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql2 GROUP BY courseid) $sql
					                 LIMIT 10", $this->params);
    }
    public function get_active_users($params){
        global $DB;

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

        return $DB->get_records_sql("
        	SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) AS name,
				u.lastaccess,
				ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade,
				COUNT(DISTINCT e.courseid) AS courses,
				lit.timespend, lit.visits
			FROM {user} u
				LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
				LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
				LEFT JOIN (SELECT userid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY userid) lit ON lit.userid = u.id
			WHERE lit.visits > 0 $sql
			GROUP BY u.id $sql_order
			LIMIT 10", $this->params);
    }

    public function get_enrollments_per_course($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql .= $this->get_filterdate_sql($params, "ue.timemodified");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql .= $this->get_filter_enrol_sql($params, "ue.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");

        return $DB->get_records_sql("
        	SELECT c.id,
				c.fullname,
				COUNT(DISTINCT ue.userid ) AS nums
			FROM
				{course} c,
				{enrol} e,
				{user_enrolments} ue
			WHERE e.courseid = c.id AND ue.enrolid = e.id $sql
			GROUP BY c.id LIMIT 0, 100", $this->params); // maximum
    }
    public function get_size_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");

        if($params->sizemode){
            return null;
        }else{
            return $DB->get_records_sql("SELECT c.id,
                                                c.timecreated,
                                                c.fullname,
                                                fs.coursesize,
                                                fm.modulessize
                                        FROM {course} c
                                            LEFT JOIN (SELECT c.instanceid AS course, SUM( f.filesize ) AS coursesize FROM {files} f, {context} c WHERE c.id = f.contextid AND c.contextlevel = 50 GROUP BY c.instanceid) fs ON fs.course = c.id
                                            LEFT JOIN (SELECT cm.course, SUM( f.filesize ) AS modulessize FROM {course_modules} cm, {files} f, {context} ctx WHERE ctx.id = f.contextid AND ctx.instanceid = cm.id AND ctx.contextlevel = 70 GROUP BY cm.course) fm ON fm.course = c.id
                                        WHERE c.category > 0 $sql
                                        LIMIT 20", $this->params);
        }

    }
    public function get_active_ip_users($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "u.id", "users");

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        return $DB->get_records_sql("SELECT l.userid,
                                            l.userip,
                                            u.lastaccess AS time,
                                            sum(l.visits) AS visits,
                                            CONCAT( u.firstname, ' ', u.lastname ) AS name
                                    FROM {local_intelliboard_tracking} l,  {user} u
                                    WHERE u.id = l.userid AND l.lastaccess BETWEEN :timestart AND :timefinish $sql
                                    GROUP BY l.userid
                                    ORDER BY visits  DESC
                                    LIMIT 10", $this->params);
    }

    public function get_active_courses_per_day($params){
        global $DB;

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

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepoint, SUM(courses) AS courses
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY FLOOR(timepoint / $ext) * $ext", $this->params);
        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->courses;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function get_unique_sessions($params){
        global $DB;

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

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepoint, SUM(sessions) AS users
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY FLOOR(timepoint / $ext) * $ext", $this->params);
        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->users;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function get_new_courses_per_day($params){
        global $DB;

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

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(timecreated / $ext) * $ext AS time, COUNT(id) AS courses
                                      FROM {course}
                                      WHERE category > 0 AND FLOOR(timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY FLOOR(timecreated / $ext) * $ext", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->time.'.'.$item->courses;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;

    }
    public function get_users_per_day($params){
        global $DB;

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

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(timecreated / $ext) * $ext AS timepoint, COUNT(id) AS users
                                      FROM {user}
                                      WHERE FLOOR(timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
                                      GROUP BY FLOOR(timecreated / $ext) * $ext", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->users;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function get_active_users_per_day($params){
        global $DB;

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

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepoint, SUM(visits) AS users
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY FLOOR(timepoint / $ext) * $ext", $this->params);



        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->users;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }

    public function get_countries($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "id", "users");
        $sql .= $this->get_filter_user_sql($params, "");

        return $DB->get_records_sql("SELECT country, COUNT(*) AS users
                                     FROM {user} u
                                     WHERE country != '' $sql
                                     GROUP BY country", $this->params);
    }
    public function get_cohorts($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, name FROM {cohort} ORDER BY name");
    }
    public function get_elisuset($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, name FROM {local_elisprogram_uset} ORDER BY name");
    }
    public function get_totara_pos($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, fullname FROM {pos} WHERE visible = 1 ORDER BY fullname");
    }
    public function get_scorm_user_attempts($params){
        global $DB;

        $this->params['courseid'] = $params->courseid;
        $this->params['userid'] = $params->userid;

        if($params->userid){
        	$this->params['userid'] = (int)$params->userid;
        	$sql = " AND t.userid = :userid";
        }else{
        	$sql = "";
        }


        return $DB->get_records_sql("SELECT DISTINCT b.attempt
                                     FROM {scorm} a, {scorm_scoes_track} b
                                     WHERE a.course = :courseid AND b.scormid = a.id $sql", $this->params);
    }
    public function get_course_users($params){
        global $DB;

        $sql_filter = $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $this->params['courseid'] = $params->courseid;
        return $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname
                                     FROM {user_enrolments} ue, {enrol} e, {user} u
                                     WHERE e.courseid = :courseid AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter", $this->params);
    }

    public function get_info($params){
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');

        return array('version' => get_component_version('local_intelliboard'));
    }
    public function get_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");

        $sql_filter = $this->get_filter_course_sql($params, "c.");

        if($params->filter){
            $sql_filter .= " AND " . $DB->sql_like('c.fullname', ":fullname", false, false);
            $this->params['fullname'] = "%$params->filter%";
        }


        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        if($params->custom){
            $sql_enabled = $this->get_filter_in_sql($params->custom,'ue.userid');
            $sql_filter .= " AND c.id IN(SELECT DISTINCT(e.courseid) FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql_enabled)";
        }

        return $this->get_report_data("SELECT c.id,
                                            c.fullname,
                                            ca.id AS cid,
                                            ca.name AS category
                                        FROM {course} c, {course_categories} ca
                                        WHERE c.category = ca.id $sql $sql_filter
                                        ORDER BY c.fullname", $params, false);
    }
    public function get_modules($params){
        global $DB;

        $sql = "";
        if($params->custom){
            $sql = " AND name IN (SELECT itemmodule FROM {grade_items} GROUP BY itemmodule)";
        }
        return $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1 $sql");
    }
    public function get_outcomes($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, shortname, fullname FROM {grade_outcomes} WHERE courseid > 0");
    }
    public function get_roles($params){
        global $DB;

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
    public function get_roles_fix_name($params){
        $roles = role_fix_names(get_all_roles());
        return $roles;
    }
    public function get_tutors($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_INT);

        if($params->filter){
            $filter = "a.roleid = :roleid";
            $this->params['roleid'] = $params->filter;
        }else{
            $filter = $this->get_filter_in_sql($params->teacher_roles,'a.roleid',false);
        }

        return $DB->get_records_sql("SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) AS name, u.email
                                     FROM {user} u
                                        LEFT JOIN {role_assignments} a ON a.userid = u.id
                                     WHERE $filter AND u.deleted = 0 AND u.confirmed = 1
                                     GROUP BY u.id",$this->params);
    }


    public function get_cminfo($params){
        global $DB;

        $module = $DB->get_record_sql("SELECT cm.id, cm.instance, m.name
                                       FROM {course_modules} cm, {modules} m
                                       WHERE m.id = cm.module AND cm.id = :id
                                      ",array('id'=>intval($params->custom)));

        return $DB->get_record($module->name, array('id'=>$module->instance));
    }

    public function get_enrols($params){
        global $DB;

        return $DB->get_records_sql("SELECT e.id, e.enrol FROM {enrol} e GROUP BY e.enrol");
    }


    public function get_learner($params){
        global $DB;

        if($params->userid){
            $sql_params = $this->params;
            $sql_params['userid1'] = $params->userid;
            $sql_params['userid2'] = $params->userid;
            $sql_params['userid3'] = $params->userid;
            $sql_params['userid4'] = $params->userid;
            $sql_params['userid5'] = $params->userid;
            $user = $DB->get_record_sql("SELECT
                                            u.*,
                                            cx.id AS context,
                                            COUNT(c.id) AS completed,
                                            gc.grade,
                                            lit.timespend_site, lit.visits_site,
                                            lit2.timespend_courses, lit2.visits_courses,
                                            lit3.timespend_modules, lit3.visits_modules,
                                            (SELECT COUNT(*) FROM {course} WHERE visible = 1 AND category > 0) AS available_courses
                                         FROM {user} u
                                            LEFT JOIN {course_completions} c ON c.timecompleted > 0 AND c.userid = u.id
                                            LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                                            LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid1) as gc ON gc.userid = u.id
                                            LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_site, SUM(visits) AS visits_site FROM {local_intelliboard_tracking} WHERE userid = :userid2) lit ON lit.userid = u.id
                                            LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_courses, SUM(visits) AS visits_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 AND userid = :userid3) lit2 ON lit2.userid = u.id
                                            LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_modules, SUM(visits) AS visits_modules FROM {local_intelliboard_tracking} WHERE page = 'module' AND userid = :userid4) lit3 ON lit3.userid = u.id
                                         WHERE u.id = :userid5
                                        ", $sql_params);

            if($user->id){
                $filter1 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);
                $filter2 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);
                $sql_params2 = $this->params;
                $sql_params2['userid1'] = $user->id;
                $sql_params2['userid2'] = $user->id;

                $user->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site
                                                  FROM
                                                    (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site,
                                                            ROUND(AVG(b.visits_site),0) as visits_site
                                                        FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
                                                            FROM {local_intelliboard_tracking}
                                                            WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $filter1) AND userid != :userid1
                                                            GROUP BY userid) AS b) a,
					                                (SELECT ROUND(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
                                                        FROM {grade_items} gi, {grade_grades} g
                                                        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid NOT IN (
                                                          SELECT distinct userid FROM {role_assignments} WHERE $filter2) AND g.userid != :userid2 GROUP BY g.userid) b) c
                                                ",$sql_params2);


                $sql_params = $this->params;
                $sql_params['userid'] = $user->id;
                $user->data = $DB->get_records_sql("SELECT uif.id, uif.name, uid.data
                                                    FROM {user_info_field} uif,
                                                         {user_info_data} uid
                                                    WHERE uif.id = uid.fieldid AND uid.userid = :userid
                                                    ORDER BY uif.name
                                                   ",$sql_params);

                $user->grades = $DB->get_records_sql("SELECT g.id, gi.itemmodule, ROUND(AVG( (g.finalgrade/g.rawgrademax)*100),2) AS grade
                                                      FROM {grade_items} gi,
                                                             {grade_grades} g
                                                      WHERE  gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid
                                                      GROUP BY gi.itemmodule
                                                      ORDER BY g.timecreated DESC
                                                     ", $sql_params);

                $user->courses = $DB->get_records_sql("SELECT ue.id,
                                                              ue.userid,
                                                              ROUND(((cmc.completed/cmm.modules)*100), 0) AS completion,
                                                              c.id AS cid,
                                                              c.fullname
                                                       FROM {user_enrolments} ue
                                                           LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                                           LEFT JOIN {course} c ON c.id = e.courseid
                                                           LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid
                                                           LEFT JOIN (SELECT cm.course, COUNT(cm.id) AS modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) AS cmm ON cmm.course = c.id
                                                           LEFT JOIN (SELECT cm.course, cmc.userid, COUNT(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
                                                       WHERE ue.userid = :userid
                                                       GROUP BY e.courseid
                                                       ORDER BY c.fullname
                                                       LIMIT 0, 100
                                                      ",$sql_params);
            }else{
                return false;
            }
        }else{
            return false;
        }
        return $user;
    }

    public function get_learners($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_SEQUENCE);
        $filter = $this->get_filter_in_sql($params->filter,'u.id',false);
        $sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        return $DB->get_records_sql("
           SELECT u.id,u.firstname,u.lastname,u.email,u.firstaccess,u.lastaccess,cx.id AS context, gc.average, ue.courses, c.completed, ROUND(((c.completed/ue.courses)*100), 0) as progress
           FROM {user} u
                LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) AS gc ON gc.userid = u.id
                LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                LEFT JOIN (SELECT ra.userid, COUNT(DISTINCT ctx.instanceid) AS courses FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ra.userid) as ue ON ue.userid = u.id
                LEFT JOIN (SELECT userid, COUNT(id) AS completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY userid) AS c ON c.userid = u.id
           WHERE u.deleted = 0 AND $filter", $this->params);
    }
    public function get_learner_courses($params){
        global $DB;

        $this->params['userid'] = $params->userid;

        return $DB->get_records_sql("
        	SELECT c.id, c.fullname
            FROM {user_enrolments} AS ue
                LEFT JOIN {enrol} AS e ON e.id = ue.enrolid
                LEFT JOIN {course} AS c ON c.id = e.courseid
            WHERE ue.userid = :userid
            GROUP BY e.courseid
            ORDER BY c.fullname ASC", $this->params);

    }
    public function get_course($params){
        global $DB;

        $filter = $this->get_filter_in_sql($params->courseid, 'c.id', false);
        $sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $course = $DB->get_record_sql("
        	SELECT c.id,
				c.fullname,
				c.timecreated,
				c.enablecompletion,
				c.format,
				c.startdate,
				ca.name AS category,
				l.learners,
				cc.completed,
				gc.grade,
				gr.grades,
				cm.modules,
				s.sections,
				lit.timespend,
				lit.visits,
				lit2.timespend AS timespend_modules,
				lit2.visits AS visits_modules
			FROM {course} c
				LEFT JOIN {course_categories} ca ON ca.id = c.category
				LEFT JOIN (SELECT course, COUNT(id) AS modules FROM {course_modules} WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
				LEFT JOIN (SELECT gi.courseid, COUNT(g.id) AS grades FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) AS gr ON gr.courseid = c.id
				LEFT JOIN (SELECT course, COUNT(*) AS sections FROM {course_sections} WHERE visible = 1 GROUP BY course) AS s ON s.course = c.id
				LEFT JOIN (SELECT gi.courseid, ROUND(AVG((g.finalgrade/g.rawgrademax)*100), 0) AS grade
							FROM {grade_items} gi, {grade_grades} g
							WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
							GROUP BY gi.courseid) AS gc ON gc.courseid = c.id
				LEFT JOIN (SELECT ctx.instanceid, COUNT(DISTINCT ra.userid) as learners FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ctx.instanceid) AS l ON l.instanceid = c.id
				LEFT JOIN (SELECT course, COUNT(DISTINCT userid) AS completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) AS cc ON cc.course = c.id
				LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY courseid) AS lit ON lit.courseid = c.id
				LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY courseid) AS lit2 ON lit2.courseid = c.id
				WHERE $filter", $this->params);

        if($course->id){
            $filter = $this->get_filter_in_sql($params->learner_roles, 'roleid', false, false);
            $this->params['courseid1'] = $course->id;
            $this->params['courseid2'] = $course->id;

            $course->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site
                                                FROM
                                                    (SELECT ROUND(AVG(b.timespend_site),0) AS timespend_site,
                                                            ROUND(AVG(b.visits_site),0) AS visits_site
                                                        FROM (SELECT SUM(timespend) AS timespend_site, SUM(visits) AS visits_site
                                                            FROM {local_intelliboard_tracking}
                                                            WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $filter) AND courseid != :courseid1
                                                            GROUP BY courseid) AS b) a,
						                            (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
                                                        FROM {grade_items} gi, {grade_grades} g
                                                        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid != :courseid2 GROUP BY gi.courseid) b) c
                                               ", $this->params);

            $course->mods = $DB->get_records_sql("SELECT m.id, m.name, COUNT( cm.id ) AS size
                                                  FROM {course_modules} cm, {modules} m
                                                  WHERE cm.visible = 1 AND m.id = cm.module AND cm.course = 2
                                                  GROUP BY cm.module");


            $filter1 = $this->get_filter_in_sql($params->teacher_roles, 'ra.roleid');
            $filter1 .= $this->get_filter_in_sql($params->courseid, 'c.id');

            $course->teachers = $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS name, u.email, cx.id AS context
                                                      FROM {user} u
                                                        LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                                                        LEFT JOIN {role_assignments} AS ra ON u.id = ra.userid
                                                        LEFT JOIN {context} ctx ON ra.contextid = ctx.id
                                                        LEFT JOIN {course} c ON c.id = ctx.instanceid
									                  WHERE ctx.instanceid = c.id $filter1
										              GROUP BY u.id
										             ", $this->params);
        }
        return $course;
    }

    // not tested
    public function get_activity_learners($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_SEQUENCE);

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        list($sql_enabled, $sql_params) = $DB->get_in_or_equal(explode(',', $params->filter), SQL_PARAMS_NAMED, 'userid');
        $this->params = array_merge($this->params,$sql_params);

        $completions = $DB->get_records_sql("SELECT cc.id, cc.timecompleted, c.id AS cid, c.fullname AS course
                                             FROM {course_completions} cc
                                                LEFT JOIN {course} c ON c.id = cc.course
                                                LEFT JOIN {user} u ON u.id = cc.userid
                                             WHERE cc.timecompleted BETWEEN :timestart AND :timefinish AND cc.userid $sql_enabled
                                             ORDER BY cc.timecompleted
                                             DESC LIMIT 10
                                            ", $this->params);

        $enrols = $DB->get_records_sql("SELECT ue.id, ue.timecreated, c.id AS cid, c.fullname AS course
                                        FROM {user_enrolments} ue
                                            LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                            LEFT JOIN {course} c ON c.id = e.courseid
                                            LEFT JOIN {user} u ON u.id = ue.userid
							            WHERE ue.timecreated BETWEEN :timestart AND :timefinish AND ue.userid $sql_enabled
							            GROUP BY ue.userid, e.courseid
							            ORDER BY ue.timecreated DESC
							            LIMIT 10
							           ", $this->params);

        $grades = $DB->get_records_sql("SELECT g.id, round(((g.finalgrade/g.rawgrademax)*100),0) AS grade, gi.courseid, gi.itemname, c.fullname AS course, g.timecreated
                                        FROM
                                            {grade_items} gi,
                                            {grade_grades} g,
                                            {course} c,
                                            {user} u
				                        WHERE g.timecreated BETWEEN :timestart AND :timefinish AND g.userid $sql_enabled AND gi.id = g.itemid AND u.id = g.userid AND c.id = gi.courseid
				                        ORDER BY g.timecreated DESC
				                        LIMIT 10
				                       ", $this->params);

        return array("enrols"=>$enrols, "grades"=>$grades, "completions"=>$completions);
    }

    public function get_learner_visits_per_day($params){
        global $DB;

        $ext = 86400;

        $params->filter = clean_param($params->filter, PARAM_SEQUENCE);

        $sql_filter = "";
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'t.courseid');
        $sql_filter .= $this->get_filter_in_sql($params->filter,'t.userid');

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = $DB->get_records_sql("SELECT FLOOR(l.timepoint / $ext) * $ext as timepoint, SUM(l.visits) as visits
                                      FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                      WHERE l.trackid = t.id AND floor(l.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_filter
                                      GROUP BY FLOOR(l.timepoint / $ext) * $ext
                                     ", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->visits;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function get_course_visits_per_day($params){
        global $DB;

        $ext = 86400;
        $sql_user = "";
        if($params->userid){
            $sql_user = " AND t.userid=:userid";
            $this->params['userid'] = $params->userid;
        }
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        $sql_enabled = $this->get_filter_in_sql($params->courseid,'t.courseid');

        $data = $DB->get_records_sql("SELECT FLOOR(l.timepoint / $ext) * $ext AS timepoint, SUM(l.visits) AS visits
                                      FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                      WHERE l.trackid = t.id $sql_user AND floor(l.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_enabled
                                      GROUP BY floor(l.timepoint / $ext) * $ext
                                     ", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timepoint.'.'.$item->visits;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }


    public function get_userinfo($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_INT);

        $this->params['userid'] = $params->filter;
        return $DB->get_record_sql("SELECT u.*, cx.id AS context
                                    FROM {user} u
                                        LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                                    WHERE u.id = :userid
                                   ", $this->params);
    }
    public function get_user_info_fields_data($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->filter, 'fieldid');
        $sql .= $this->get_filter_in_sql($params->custom, 'userid');

        return $DB->get_records_sql("
        	SELECT id, fieldid, data, count(id) as items
			FROM {user_info_data}
			WHERE data != '' $sql
			GROUP BY data
			ORDER BY data ASC", $this->params);
    }
    public function get_user_info_fields($params){
        global $DB;

        return $DB->get_records_sql("SELECT uif.id, uif.name, uic.name AS category
                                     FROM {user_info_field} uif, {user_info_category} uic
                                     WHERE uif.categoryid = uic.id
                                     ORDER BY uif.name");
    }
    public function get_reportcard($params){
        global $CFG, $DB;

        $data = array();
        $sql_params = $this->params;
        $sql_params['userid1'] = $params->userid;
        $sql_params['userid2'] = $params->userid;
        $sql_params['userid3'] = $params->userid;
        $sql_params['userid4'] = $params->userid;
        $sql_params['userid5'] = $params->userid;
        $sql_params['userid6'] = $params->userid;
        $sql_params['userid7'] = $params->userid;
        $sql_params['completionexpected'] = time();
        $data['stats'] = $DB->get_record_sql("SELECT
	            (SELECT COUNT(DISTINCT e.courseid) FROM {user_enrolments} ue, {enrol} e WHERE e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid AND ue.userid = :userid1) AS courses,
	            (SELECT COUNT(DISTINCT course) FROM {course_completions} WHERE timecompleted > 0 AND userid = :userid2) AS completed,
	            (SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=:userid3 WHERE cm.visible = 1 AND cm.course IN (
	                SELECT DISTINCT e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid and ue.userid = :userid4) AND cm.visible=1 AND cm.completionexpected < :completionexpected AND cm.completionexpected>0 AND (cmc.id IS NULL OR cmc.completionstate=0)) as missed,
	            (SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm WHERE cm.course IN (
	                SELECT DISTINCT e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid AND ue.userid = :userid5) AND cm.visible=1 AND cm.completionexpected>0) AS current,
	            (SELECT COUNT(id) FROM {quiz} WHERE course IN (
	                SELECT DISTINCT e.courseid FROM {user_enrolments} ue, {enrol} e WHERE  e.status = 0 AND ue.status = 0 AND e.id = ue.enrolid AND ue.userid = :userid6) AND id NOT IN (
	                    SELECT quiz FROM {quiz_grades} WHERE userid = :userid7 AND grade > 0)) AS quizes
                                            ", $sql_params);

        $sql_params['completionexpected_min'] = time()-86400;
        $sql_params['completionexpected_max'] = time();
        $data['courses'] = $DB->get_records_sql("SELECT c.id, c.fullname, a.assignments, b.missing, t.quizes, cc.timecompleted, g.grade
                                                 FROM {user_enrolments} ue
                                                    LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                                    LEFT JOIN {course} c ON c.id = e.courseid
                                                    LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = :userid1
                                                    LEFT JOIN (SELECT gi.courseid, (g.finalgrade/g.rawgrademax)*100 AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid2 GROUP BY gi.courseid) g ON g.courseid = c.id
                                                    LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as missing FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=:userid3 WHERE cm.visible=1 AND cm.completionexpected < :completionexpected  AND cm.completionexpected > 0 AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) b ON b.course = c.id
                                                    LEFT JOIN (SELECT cm.course, COUNT(DISTINCT cm.id) as assignments FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=:userid4 WHERE cm.visible=1 AND cm.completionexpected BETWEEN :completionexpected_min AND :completionexpected_max AND (cmc.id IS NULL OR cmc.completionstate=0) GROUP BY cm.course) a ON a.course = c.id
                                                    LEFT JOIN (SELECT course, COUNT(id) AS quizes FROM {quiz} WHERE id NOT IN (SELECT quiz FROM {quiz_grades} WHERE userid = :userid5 AND grade > 0) GROUP BY course) t ON t.course = c.id
                                                 WHERE ue.status = 0 AND e.status = 0 AND ue.userid = :userid6
                                                 ORDER BY c.fullname ASC
                                                ", $sql_params);

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
    public function get_dashboard_avg($params){
        global $DB;

        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'roleid',false);

        return $DB->get_record_sql("
        	SELECT a.timespend_site, a.visits_site, c.grade_site
        	FROM
	            (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site, ROUND(AVG(b.visits_site),0) as visits_site
	                FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
	                    FROM {local_intelliboard_tracking}
	                    WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $sql_enabled) AND userid != 2 GROUP BY userid) AS b) a,
	            (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade
	                FROM {grade_items} gi, {grade_grades} g
	        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) b) c", $this->params);
    }
    public function get_dashboard_countries($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, "id", "users");
        $sql .= $this->get_filter_user_sql($params, "");

        return $DB->get_records_sql("SELECT country, count(*) AS users FROM {user} WHERE country <> '' $sql GROUP BY country", $this->params);
    }
    public function get_dashboard_enrols($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, "e.courseid", "courses");
        $sql .= $this->get_filter_enrol_sql($params, "ue.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");

        return $DB->get_records_sql("SELECT e.id, e.enrol, COUNT(ue.id) AS enrols FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql GROUP BY e.enrol", $this->params);
    }
    public function get_dashboard_info($params)
    {
        global $DB;

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

        $this->params['timestart'] = $timestart;
        $this->params['timefinish'] = $timefinish;

        $data[] = $DB->get_records_sql("
			SELECT floor(timepoint / $ext) * $ext AS timepoint, SUM(sessions) AS visits
			FROM {local_intelliboard_totals}
			WHERE timepoint BETWEEN :timestart AND :timefinish
			GROUP BY floor(timepoint / $ext) * $ext", $this->params);

        $data[] = $DB->get_records_sql("
			SELECT floor(timecreated / $ext) * $ext AS timecreated, COUNT(DISTINCT (userid)) AS users
			FROM {user_enrolments}
			WHERE timecreated BETWEEN :timestart AND :timefinish $sql
			GROUP BY floor(timecreated / $ext) * $ext", $this->params);

        $data[] = $DB->get_records_sql("
			SELECT floor(timecompleted / $ext) * $ext AS timecreated, COUNT(DISTINCT (userid)) AS users
			FROM {course_completions}
			WHERE timecompleted BETWEEN :timestart AND :timefinish $sql
			GROUP BY floor(timecompleted / $ext) * $ext", $this->params);

        return $data;
    }
    public function get_dashboard_stats($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        $this->params['timeyesterday1'] = strtotime('yesterday');
        $this->params['timeyesterday2'] = strtotime('yesterday');
        $this->params['timeyesterday3'] = strtotime('yesterday');
        $this->params['timelastweek1'] = strtotime('last week');
        $this->params['timelastweek2'] = strtotime('last week');
        $this->params['timelastweek3'] = strtotime('last week');
        $this->params['timetoday1'] = strtotime('today');
        $this->params['timetoday2'] = strtotime('today');
        $this->params['timetoday3'] = strtotime('today');
        $this->params['timeweek1'] = strtotime('previous monday');
        $this->params['timeweek2'] = strtotime('previous monday');
        $this->params['timeweek3'] = strtotime('previous monday');
        $this->params['timefinish1'] = time();
        $this->params['timefinish2'] = time();
        $this->params['timefinish3'] = time();
        $this->params['timefinish4'] = time();
        $this->params['timefinish5'] = time();
        $this->params['timefinish6'] = time();

        $data = array();
        if($params->sizemode){
            $data[] = array();
        }else{
            $data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timeyesterday1 AND :timetoday1) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timelastweek1 AND :timeweek1) as sessions_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN :timeyesterday2 AND :timetoday2 $sql) as enrolments_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN :timelastweek2 AND :timeweek2 $sql) as enrolments_week,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN :timeyesterday3 AND :timetoday3 $sql) as compl_today,
			(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN :timelastweek3 AND :timeweek3 $sql) as compl_week", $this->params);
        }
        $data[] = $DB->get_record_sql("SELECT
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timetoday1 AND :timefinish1) as sessions_today,
			(SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timeweek1 AND :timefinish2) as sessions_week,
			(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN :timetoday2 AND :timefinish3 $sql) as enrolments_today,
			(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN :timeweek2 AND :timefinish4 $sql) as enrolments_week,
			(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN :timetoday3 AND :timefinish5 $sql) as compl_today,
			(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN :timeweek3 AND :timefinish6 $sql) as compl_week", $this->params);
        return $data;
    }

    public function set_notification_enrol($params)
    {
        set_config("enrol", $params->notification_enrol, "local_intelliboard");
        set_config("enrol_email", $params->notification_email, "local_intelliboard");
        set_config("enrol_subject", $params->notification_subject, "local_intelliboard");
        set_config("enrol_message", $params->notification_message, "local_intelliboard");
        return true;
    }
    public function set_notification_auth($params)
    {
        set_config("auth", $params->notification_auth, "local_intelliboard");
        set_config("auth_email", $params->notification_email, "local_intelliboard");
        set_config("auth_subject", $params->notification_subject, "local_intelliboard");
        set_config("auth_message", $params->notification_message, "local_intelliboard");
        return true;
    }
    private function count_records($sql,$unique_id = 'id',$params=array())
    {
        global $DB;
        if(strpos($sql,"LIMIT") !== false)
            $sql = strstr($sql,"LIMIT",true);

        $sql = "SELECT COUNT(cou.$unique_id) FROM (".$sql.") cou";
        return $DB->count_records_sql($sql,$params);
    }
    public function get_teacher_sql($params, $column, $type)
    {
        $sql = '';

        if(isset($params->userid) and $params->userid and $type){
            $this->params['txu1'] = $params->userid;
            $this->params['txu2'] = $params->userid;

            $sql0 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
            $sql1 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
            $sql2 = $this->get_filter_in_sql($params->learner_roles, "ra2.roleid");
            $sql3 = $this->get_filter_in_sql($params->learner_roles, "ra2.roleid");

            if($type == "users"){
                $sql2 = $this->get_filter_in_sql($params->learner_roles, "ra2.roleid");
                $sql3 = $this->get_filter_in_sql($params->learner_roles, "ra2.roleid");

                $sql = " AND (
				$column IN(SELECT distinct(ra2.userid) as id FROM {role_assignments} ra
					JOIN {context} ctx ON ra.contextid = ctx.id
	                JOIN {role_assignments} ra2 ON ra2.contextid = ctx.id $sql2
					WHERE ra.userid = :txu1 AND ctx.contextlevel = 50 $sql0)
				OR
				$column IN (SELECT distinct(ra2.userid) as id FROM {role_assignments} ra
					JOIN {context} ctx ON ra.contextid = ctx.id
	    			JOIN {course} c ON c.category = ctx.instanceid
	    			JOIN {context} ctx2 ON  ctx2.instanceid = c.id AND ctx2.contextlevel = 50
	    			JOIN {role_assignments} AS ra2 ON ra2.contextid = ctx2.id $sql3
					WHERE ra.userid = :txu2 AND ctx.contextlevel = 40 $sql1)
				)";
            }elseif($type == "courses"){
                $sql = "AND (
				$column IN(SELECT distinct(ctx.instanceid) as id FROM {role_assignments} ra
					JOIN {context} ctx ON ra.contextid = ctx.id
					WHERE ra.userid = :txu1 AND ctx.contextlevel = 50 $sql0)
				OR
				$column IN(SELECT distinct(c.id) as id FROM {role_assignments} ra
					JOIN {context} ctx ON ra.contextid = ctx.id
					JOIN {course} c ON c.category = ctx.instanceid
					WHERE ra.userid = :txu2 AND ctx.contextlevel = 40 $sql1)
				)";
            }
        }
        return $sql;
    }

    /**
     * parse feedback to needed view
     * @param object $v - row from feedback table
     */
    private function parseFeedbackAnswer($v){
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
