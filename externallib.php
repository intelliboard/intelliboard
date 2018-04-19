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
	public $debug = false;
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
                            'completion' => new external_value(PARAM_SEQUENCE, 'Completion status param', VALUE_OPTIONAL, 0),
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
                            'filter_module_visible' => new external_value(PARAM_INT, 'filter_module_visible', VALUE_OPTIONAL, 0),
                            'scales' => new external_value(PARAM_INT, 'custom scales', VALUE_OPTIONAL, 0),
                            'scale_raw' => new external_value(PARAM_INT, 'custom scale_raw', VALUE_OPTIONAL, 0),
                            'scale_total' => new external_value(PARAM_FLOAT, 'custom scale_total', VALUE_OPTIONAL, 0),
                            'scale_value' => new external_value(PARAM_FLOAT, 'custom scale_value', VALUE_OPTIONAL, 0),
                            'scale_percentage' => new external_value(PARAM_INT, 'custom scale_percentage', VALUE_OPTIONAL, 0)
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
		$params->completion = (isset($params->completion)) ? $params->completion : "1,2";
		$params->filter_columns = (isset($params->filter_columns)) ? $params->filter_columns : "0,1";
		$params->filter_profile = (isset($params->filter_profile)) ? $params->filter_profile : 0;
		$params->timestart = (isset($params->timestart)) ? $params->timestart : 0;
		$params->timefinish = (isset($params->timefinish)) ? $params->timefinish : 0;
		$params->sizemode = (isset($params->sizemode)) ? $params->sizemode : 0;
		$params->debug = (isset($params->debug)) ? (int)$params->debug : 0;
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
		$params->scales = (isset($params->scales)) ? $params->scales : 0;
		$params->scale_raw = (isset($params->scale_raw)) ? $params->scale_raw : 0;
		$params->scale_total = (isset($params->scale_total)) ? $params->scale_total : 0;
		$params->scale_value = (isset($params->scale_value)) ? $params->scale_value : 0;
		$params->scale_percentage = (isset($params->scale_percentage)) ? $params->scale_percentage : 0;

		if($params->debug){
			$CFG->debug = (E_ALL | E_STRICT);
			$CFG->debugdisplay = 1;
		}

		//Available functions
		$functions = array('report1','report2','report3','report4','report5','report6','report7','report8','report9','report10','report11','report12','report13','report14','report15','report16','report17','report18','report18_graph','report19','report20','report21','report22','report23','report24','report25','report26','report27','report28','report29','report30','report31','report32','get_scormattempts','get_competency','get_competency_templates','report33','report34','report35','report36','report37','report38','report39','report40','report41','report43','report44','report45','report42','report46','report47','report58','report66','report72','report73','report75','report76','report77','report79','report80','report81','report82','report83','report84','report85','report86','report87','report88','report89','report90','report91','report92','report93','report94','report95','report96','report97','report98','report99','report99_graph','report100','report101','report102','report103','report104','report105','report106','report107','report108','report109','report110','report111','report112','report113','report114','report114_graph','report115','report116','report117','report118','report119','report120','report121','report122','report123','report124','report125','report126','report127','report128','get_course_grade_categories','get_course_modules','report78','report74','report71','report70','report67','report68','report69','get_max_attempts','report56','analytic1','analytic2','get_quizes','analytic3','analytic4','analytic5','analytic5table','analytic6','analytic7','analytic7table','analytic8','analytic8details','get_visits_perday','get_visits_perweek','get_live_info','get_course_instructors','get_course_discussions','get_course_questionnaire','get_course_survey','get_course_questionnaire_questions','get_course_survey_questions','get_cohort_users','get_users','get_grade_letters','get_questions','get_activity','get_total_info','get_system_users','get_system_courses','get_system_load','get_module_visits','get_useragents','get_useros','get_userlang','get_module_timespend','get_users_count','get_most_visited_courses','get_no_visited_courses','get_active_users','get_enrollments_per_course','get_size_courses','get_active_ip_users','get_active_courses_per_day','get_unique_sessions','get_new_courses_per_day','get_users_per_day','get_active_users_per_day','get_countries','get_cohorts','get_elisuset','get_totara_pos','get_scorm_user_attempts','get_course_users','get_info','get_courses','get_userids','get_modules','get_outcomes','get_roles','get_roles_fix_name','get_tutors','get_cminfo','get_enrols','get_teacher_sql','get_learner','get_learners','get_learner_courses','get_course','get_activity_learners','get_learner_visits_per_day','get_course_visits_per_day','get_userinfo','get_user_info_fields_data','get_user_info_fields','get_reportcard','get_dashboard_avg','get_dashboard_countries','get_dashboard_enrols','get_dashboard_info','get_dashboard_stats','set_notification_enrol','set_notification_auth','count_records','parseFeedbackAnswer','analytic9','get_course_sections','get_course_user_groups','get_all_system_info','get_course_assignments','get_data_question_answers','get_course_databases','get_databases_question','get_history_items','get_history_grades','widget27','widget28','widget29','widget30','widget31');

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
		$sql = $this->get_filter_course_sql($params, 'c.');
		$sql .= $this->get_filter_enrol_sql($params, 'e.');
		$sql .= $this->get_filter_enrol_sql($params, 'ue.');

		return ($params->filter_enrolled_users) ? " AND {$column} IN (SELECT DISTINCT userid FROM {user_enrolments} ue, {enrol} e, {course} c WHERE ue.enrolid = e.id AND c.id = e.courseid $sql)" : "";
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
                $this->prfx = $this->prfx + 1;
				$key = "column$column".$this->prfx;
				$this->params[$key] = $column;
				$data .= ", (SELECT d.data FROM {user_info_data} d, {user_info_field} f WHERE f.id = :$key AND d.fieldid = f.id AND d.userid = $field) AS field$column";
			}
			return $data;
		}else{
			return "";
		}
	}
	private function get_completion($params, $prefix = "", $sep = true)
	{
		if(!empty($params->completion)){
			return $this->get_filter_in_sql($params->completion, $prefix."completionstate", $sep);
		}else{
			$prefix = ($sep) ? " AND ".$prefix : $prefix;
			return $prefix . "completionstate IN(1,2)";
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
				if($column and in_array($i, $filter_columns)){
                    $this->prfx = $this->prfx + 1;
					$key = clean_param($column, PARAM_ALPHANUMEXT).$this->prfx;
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
						$this->prfx = $this->prfx + 1;
						$field->fieldid = (int)$field->fieldid; //fieldid -> int
						$key = "field$field->fieldid";
						$unickey = "field{$this->prfx}_{$field->fieldid}_{$i}";
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
			$items = (is_array($sequence)) ? $sequence : explode(",", clean_param($sequence, PARAM_SEQUENCE));
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

		if ($params->debug === 2){
			return array($query, $this->params);
		}

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
		$sql_columns = "";
        $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods", $this->params);
        foreach($modules as $module){
            $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
        }
		return ($sql_columns) ? ", CASE $sql_columns ELSE 'NONE' END AS activity" : "'' AS activity";
	}

	public function report1($params)
	{
		$columns = array_merge(array("u.firstname","u.lastname", "u.email", "c.fullname", "enrols", "l.visits", "l.timespend", "grade", "cc.timecompleted","ue.timecreated", "ul.timeaccess", "ue.timeend", "cc.timecompleted","u.idnumber","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
		$sql_join_filter = ""; $sql_mode = 0;
		$grade_single = intelliboard_grade_sql(false, $params);

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
				$grade_single AS grade,
				c.enablecompletion,
				cc.timecompleted AS complete,
				u.id AS uid,
				u.email,
				u.idnumber,
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
				JOIN {enrol} e ON e.id = ue.enrolid
				JOIN {user} u ON u.id = ue.userid
				JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
        		LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
				$sql_join
			WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
	}

	public function report2($params)
	{
		$columns = array_merge(array("c.fullname", "learners", "modules", "completed", "l.visits", "l.timespend", "grade", "c.timecreated"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
		$sql_filter .= $this->get_filter_enrol_sql($params, "e.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$grade_avg = intelliboard_grade_sql(true, $params);

		$sql_compl = "";
		if ($params->custom == 1) {
			$sql_compl = $this->get_filterdate_sql($params, "cc.timecompleted");
		} elseif ($params->custom == 2) {
			$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		} else {
			$sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
		}

		if ($params->sizemode) {
			$sql_columns = ", '0' AS timespend, '0' AS visits";
			$sql_join = "";
		} else {
			$sql_columns = ", l.timespend, l.visits";
			$sql_join = " LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY courseid) l ON l.courseid = c.id";
			$sql_having = ", l.timespend, l.visits " . $sql_having;
		}

		return $this->get_report_data("
			SELECT c.id,
				c.fullname AS course,
				c.timecreated AS created,
				c.enablecompletion,
				$grade_avg AS grade,
				COUNT(DISTINCT cc.userid) AS completed,
				COUNT(DISTINCT ue.userid) AS learners,
				(SELECT COUNT(id) FROM {course_modules} WHERE visible = 1 AND course = c.id) AS modules
				$sql_columns
			FROM {course} c
				LEFT JOIN {enrol} e ON e.courseid = c.id
				LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
				LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = c.id AND cc.userid = ue.userid $sql_compl
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
		        LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
		        $sql_join
			WHERE c.id > 0 $sql_filter GROUP BY c.id $sql_having $sql_order", $params);
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
		$grade_avg = intelliboard_grade_sql(true, $params);
		$completion = $this->get_completion($params, "");

		$sql_join = "";

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ",
			(SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' AND param = cm.id) AS timespend,
			(SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE page = 'module' AND param = cm.id) AS visits";
		}

		return $this->get_report_data("
			SELECT
				cm.id,
				m.name AS moduletype,
				cm.added,
				cm.completion,
				c.fullname,
				(SELECT COUNT(id) FROM {course_modules_completion} WHERE coursemoduleid = cm.id $completion) AS completed,
				(SELECT $grade_avg FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.iteminstance = cm.instance AND g.itemid = gi.id AND g.finalgrade IS NOT NULL) AS grade
				$sql_columns
			FROM {course_modules} cm
				JOIN {modules} m ON m.id = cm.module
				JOIN {course} c ON c.id = cm.course
			WHERE cm.id > 0 $sql_filter $sql_having $sql_order", $params);
	}
	public function report4($params)
	{
		$columns = array_merge(array("u.firstname","u.lastname","u.email","registered","courses","completed_activities","completed_courses","lit.visits","lit.timespend","grade", "u.lastaccess"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_in_sql($params->custom2, "ra.roleid");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$grade_avg = intelliboard_grade_sql(true, $params);
		$completion = $this->get_completion($params, "");

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
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			if($sql_raw){
				$sql_columns .= ", lit.timespend, lit.visits";
				$sql_join .= " LEFT JOIN (SELECT userid, SUM(timespend) as timespend, SUM(visits) as visits FROM
							{local_intelliboard_tracking}
						WHERE courseid > 0 GROUP BY userid) as lit ON lit.userid = u.id";
			}else{
				$sql_columns .= ", lit.timespend, lit.visits";
				$sql_join .= " LEFT JOIN (SELECT t.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM
							{local_intelliboard_tracking} t,
							{local_intelliboard_logs} l
						WHERE l.trackid = t.id AND t.courseid > 0 $sql_join_filter GROUP BY t.userid) as lit ON lit.userid = u.id";

			}
			$sql_having  = ", lit.timespend, lit.visits" . $sql_having;
		}
		return $this->get_report_data("
			SELECT u.id,
				u.firstname,
				u.lastname,
				u.email,
				u.lastaccess,
				u.timecreated as registered,
				$grade_avg as grade,
				COUNT(DISTINCT ctx.instanceid) as courses,
				COUNT(DISTINCT cc.id) as completed_courses,
				ca.completed_activities
				$sql_columns
			FROM {user} u
				JOIN {role_assignments} ra ON ra.userid = u.id
				JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel=50
				LEFT JOIN (SELECT COUNT(id) AS completed_activities, userid FROM {course_modules_completion} WHERE id > 0 $completion GROUP BY userid) ca ON ca.userid = u.id
				LEFT JOIN {course_completions} cc ON cc.course = ctx.instanceid AND cc.userid = u.id AND cc.timecompleted > 0
				LEFT JOIN {grade_items} gi ON gi.courseid = ctx.instanceid AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL
				$sql_join
			WHERE u.id > 0 $sql_filter GROUP BY u.id, lit.timespend, lit.visits, ca.completed_activities $sql_having $sql_order", $params);
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
				MAX(f1.files) AS files,
				MAX(ff.videos) AS videos,
				MAX(l1.urls) AS urls,
				MAX(l0.evideos) AS evideos,
				MAX(l2.assignments) AS assignments,
				MAX(l3.quizes) AS quizes,
				MAX(l4.forums) AS forums,
				MAX(l5.attendances) AS attendances
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
			WHERE u.id > 0 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
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
		$grade_avg = intelliboard_grade_sql(true, $params);
		$sql_columns = $this->get_columns($params, "u.id");
		$completion = $this->get_completion($params, "cmc.");

		$sql_join = "";
		if($params->cohortid){
			$sql_join = " LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
		}

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
		}else{
			$sql_columns .= ", MAX(lit.timespend) AS timespend, MAX(lit.visits) AS visits";
			$sql_join .= " LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY courseid, userid) lit ON lit.courseid = c.id AND lit.userid = u.id";
		}

		return $this->get_report_data("
			SELECT MAX(ue.id) AS id,
				MAX(cri.gradepass) AS gradepass,
				MAX(ue.timecreated) as started,
				$grade_avg AS grade,
				MAX(cc.timecompleted) as complete,
				c.id as cid,
				c.fullname,
				u.email,
				u.id AS userid,
				u.firstname,
				u.lastname,
				c.enablecompletion,
				(SELECT COUNT(DISTINCT cmc.id) FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id $completion AND cm.completion > 0 AND cm.course = c.id AND cmc.userid = u.id) AS completed,
				(SELECT $grade_avg FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid=c.id) AS average
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
			WHERE ue.id > 0 $sql_filter GROUP BY u.id, c.id $sql_having $sql_order", $params);
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
		$grade_single = intelliboard_grade_sql(false, $params);
		$completion1 = $this->get_completion($params, "cmc.");
		$completion2 = $this->get_completion($params, "cmc.");

		if($params->sizemode){
			$sql_columns .= ", '0' as grade";
			$sql_join = "";
		}else{
			$sql_columns .= ", gc.grade AS grade";
			$sql_join = "
					LEFT JOIN (SELECT gi.courseid, g.userid, $grade_single AS grade
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
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 $completion2 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
				$sql_join
			WHERE ue.id > 0 $sql_filter GROUP BY ue.userid, e.courseid $sql_having $sql_order", $params);
	}
	public function report8($params)
	{
		$columns = array_merge(array("teacher","courses","learners","activelearners","completedlearners","grade"), $this->get_filter_columns($params));

		$sql_filter = $this->get_teacher_sql($params, "u.id", "users");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_columns = $this->get_columns($params, "u.id");
		$grade_avg = intelliboard_grade_sql(true, $params);

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
				LEFT JOIN (SELECT gi.courseid, $grade_avg AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) g ON g.courseid = ctx.instanceid
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
			WHERE q.id > 0 $sql_filter
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
			WHERE qa.id > 0 $sql_filter $sql_having $sql_order", $params);
	}
	public function report11($params)
	{
		$columns = array_merge(array("u.firstname", "u.lastname", "course", "u.email", "enrolled", "complete", "grade", "complete"), $this->get_filter_columns($params));

		$sql_columns = $this->get_columns($params, "u.id");
		$sql_having = $this->get_filter_sql($params, $columns);
		$sql_order = $this->get_order_sql($params, $columns);
		$sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
		$sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
		$sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
		$sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
		$grade_single = intelliboard_grade_sql(false, $params);

		$sql_join = "";
		if($params->cohortid){
			$sql_join = "LEFT JOIN {cohort_members} cm ON cm.userid = u.id";
			$sql_filter .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");
		}

		return $this->get_report_data("
			SELECT ue.id,
				ue.timecreated as enrolled,
				cc.timecompleted as complete,
				$grade_single AS grade,
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
			WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
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
		$grade_avg = intelliboard_grade_sql(true, $params);

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
				$grade_avg AS grade
				$sql_columns
			FROM {role_assignments} ra
				JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				JOIN {course} c ON c.id = ctx.instanceid
				LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ra.userid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ra.userid
				$sql_join
			WHERE c.id $sql_filter
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
				JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				JOIN {user} u ON u.id = ra.userid
				LEFT JOIN (SELECT c.instanceid, COUNT(DISTINCT r.userid) as learners
						FROM {role_assignments} r, {context} AS c WHERE c.id = r.contextid $sql1 GROUP BY c.instanceid) v ON v.instanceid = ctx.instanceid
				$sql_join
			WHERE u.id > 0 $sql_filter
			GROUP BY ra.userid $sql_having $sql_order", $params);
	}


	public function report14($params)
	{
		$columns = array_merge(
			array(
				"u.firstname",
				"u.lastname",
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
		$sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
		$grade_avg = intelliboard_grade_sql(true, $params);
		$sql_role = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

		if($params->sizemode){
			$sql_columns .= ", '0' as timespend, '0' as visits";
			$sql_join = "";
		}else{
			$sql_columns .= ", lit.timespend AS timespend, lit.visits AS visits";
			$sql_join = "
					LEFT JOIN (SELECT userid, SUM(timespend) as timespend, SUM(visits) as visits
						FROM {local_intelliboard_tracking} GROUP BY userid) lit ON lit.userid = u.id";
		}

		return $this->get_report_data("
			SELECT u.id, u.lastaccess,
				u.firstname,
				u.lastname,
				u.email,
				grd.grade,
				grd.courses
				$sql_columns
			FROM {user} u
				JOIN (SELECT ra.userid, $grade_avg AS grade, COUNT(DISTINCT ctx.instanceid) as courses
					FROM {role_assignments} ra
					JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
					JOIN {user} u ON u.id = ra.userid
					LEFT JOIN {grade_items} gi ON gi.courseid = ctx.instanceid AND gi.itemtype = 'course'
					LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ra.userid AND g.finalgrade IS NOT NULL
					WHERE gi.itemtype = 'course' $sql_role
					GROUP BY ra.userid) grd ON grd.userid =u.id
				$sql_join
			WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params);
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
			WHERE e.id > 0 $sql_filter GROUP BY e.enrol $sql_having $sql_order", $params);
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
			WHERE c.id > 0 $sql_filter GROUP BY f.course $sql_having $sql_order", $params);
    }


	public function report17($params)
    {
        $columns = array_merge(array("c.fullname", "f.name", "avg_month", "avg_week", "avg_day", "popular_hour.hour", "popular_week.week", "popular_day.day"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql_course1 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f.course",false):1;
        $sql_course2 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):1;
        $sql_course3 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):1;
        $sql_course4 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):1;

        return $this->get_report_data("
                SELECT
                  f.id as fid,
                  f.name,
                  c.fullname,
                  COUNT(DISTINCT fp.id) AS all_posts,
                  COUNT(DISTINCT fp.id)/PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m')) as avg_month,
                  COUNT(DISTINCT fp.id)/CEILING(DATEDIFF(DATE_FORMAT(NOW(), '%Y%m%d'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m%d'))/7) as avg_week,
                  COUNT(DISTINCT fp.id)/DATEDIFF(DATE_FORMAT(NOW(), '%Y%m%d'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m%d')) as avg_day,
                  popular_day.day AS popular_day,
                  popular_hour.hour AS popular_hour,
                  popular_week.week AS popular_week
                FROM {forum} f
                  JOIN {modules} m ON m.name='forum'
                  LEFT JOIN {course_modules} cm ON cm.course=f.course AND cm.instance=f.id AND cm.module=m.id

                  LEFT JOIN {course} c ON c.id=f.course
                  LEFT JOIN {forum_discussions} fd ON fd.forum=f.id
                  LEFT JOIN {forum_posts} fp ON fp.discussion=fd.id

                  LEFT JOIN ( SELECT a.posts,a.day,a.id
                                FROM ( SELECT
                                          COUNT(DISTINCT fp2.id) as posts,
                                          WEEKDAY(FROM_UNIXTIME(fp2.created,'%Y-%m-%d %T')) AS day,
                                          f2.id
                                        FROM {forum} f2
                                          LEFT JOIN {forum_discussions} fd2 ON fd2.forum=f2.id
                                          LEFT JOIN {forum_posts} fp2 ON fp2.discussion=fd2.id
                                        WHERE $sql_course4
                                        GROUP BY day,f2.id
                                        ORDER BY posts DESC
                                    ) AS a
                            GROUP BY a.id) as popular_day ON popular_day.id=f.id

                  LEFT JOIN ( SELECT a.posts,a.hour,a.id
                                FROM ( SELECT
                                          COUNT(DISTINCT fp2.id) as posts,
                                          FROM_UNIXTIME(fp2.created,'%H') AS hour,
                                          f2.id
                                        FROM {forum} f2
                                          LEFT JOIN {forum_discussions} fd2 ON fd2.forum=f2.id
                                          LEFT JOIN {forum_posts} fp2 ON fp2.discussion=fd2.id
                                        WHERE $sql_course3
                                        GROUP BY hour,f2.id
                                        ORDER BY posts DESC
                                    ) AS a
                            GROUP BY a.id) as popular_hour ON popular_hour.id=f.id

                  LEFT JOIN ( SELECT a.posts,a.week,a.id
                                FROM ( SELECT
                                          COUNT(DISTINCT fp2.id) as posts,
                                          CEILING(DAYOFMONTH(FROM_UNIXTIME(fp2.created,'%Y-%m-%d %T'))/7) AS week,
                                          f2.id
                                        FROM {forum} f2
                                          LEFT JOIN {forum_discussions} fd2 ON fd2.forum=f2.id
                                          LEFT JOIN {forum_posts} fp2 ON fp2.discussion=fd2.id
                                        WHERE $sql_course2
                                        GROUP BY week,f2.id
                                        ORDER BY posts DESC
                                    ) AS a
                            GROUP BY a.id) as popular_week ON popular_week.id=f.id

                WHERE $sql_course1 $sql_filter
                GROUP BY f.id $sql_having $sql_order", $params);
    }

    public function report18($params)
    {
        $columns = array_merge(array("f.name", "u.firstname","u.lastname","course", "discussions", "posts"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
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
				$sql_columns
			FROM
				{forum_discussions} fd
				LEFT JOIN {user} u ON u.id = fd.userid
				LEFT JOIN {course} c ON c.id = fd.course
				LEFT JOIN {forum} f ON f.id = fd.forum
				LEFT JOIN {forum_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
			WHERE fd.id $sql_filter GROUP BY u.id, f.id $sql_having $sql_order", $params);
    }

    public function report18_graph($params)
    {
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $params->length = -1;

        return $this->get_report_data("
			SELECT fd.id,
			    COUNT(DISTINCT fp.id) AS count,
			    WEEKDAY(FROM_UNIXTIME(fp.created,'%Y-%m-%d %T')) AS day,
			    IF(FROM_UNIXTIME(fp.created,'%H')>=6 && FROM_UNIXTIME(fp.created,'%H')<12,'1',
                             IF(FROM_UNIXTIME(fp.created,'%H')>=12 && FROM_UNIXTIME(fp.created,'%H')<17,'2',
                             IF(FROM_UNIXTIME(fp.created,'%H')>=17 && FROM_UNIXTIME(fp.created,'%H')<=23,'3',
                             IF(FROM_UNIXTIME(fp.created,'%H')>=0 && FROM_UNIXTIME(fp.created,'%H')<6,'4','undef')))) AS time_of_day
			FROM
				{forum_discussions} fd
				LEFT JOIN {user} u ON u.id = fd.userid
				LEFT JOIN {course} c ON c.id = fd.course
				LEFT JOIN {forum} f ON f.id = fd.forum
				LEFT JOIN {forum_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
			WHERE fd.id $sql_filter GROUP BY day,time_of_day ORDER BY time_of_day, day", $params);
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
			WHERE s.id $sql_filter GROUP BY s.id $sql_having $sql_order", $params);
    }
    public function report21($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "u.idnumber", "s.name", "c.fullname", "attempts", "t.duration","t.starttime","cmc.timemodified", "score"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filterdate_sql($params, "t.starttime");
        $grade_single = intelliboard_grade_sql(false, $params);

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
        }

        return $this->get_report_data("
        	SELECT t.*,
                u.firstname,
                u.lastname,
                u.email,
                u.idnumber,
                s.name,
                c.fullname,
                cmc.completionstate,
                cmc.timemodified as completiondate,
                round(sg.score, 0) as score
				$sql_columns
				FROM (SELECT MIN(id) AS id,userid, scormid,
					MIN(CASE WHEN element = 'x.start.time' THEN value ELSE null END) AS starttime,
					SEC_TO_TIME( SUM( TIME_TO_SEC(CASE WHEN element = 'cmi.core.total_time' THEN value ELSE null END))) AS duration,
					COUNT(DISTINCT(attempt)) as attempts
					FROM {scorm_scoes_track} GROUP BY userid, scormid) t
					JOIN {user} u ON u.id = t.userid
					JOIN {scorm} AS s ON s.id = t.scormid
					JOIN {course} c ON c.id = s.course
					JOIN {modules} m ON m.name = 'scorm'
					JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = s.id
					LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
					LEFT JOIN (SELECT gi.iteminstance, $grade_single AS score, g.userid
						FROM {grade_items} gi, {grade_grades} g WHERE gi.itemmodule = 'scorm' and g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.iteminstance, g.userid) AS sg ON sg.iteminstance = t.scormid and sg.userid= t.userid
					$sql_join
			WHERE t.userid > 0 $sql_filter $sql_having $sql_order", $params);
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
			WHERE qa.id > 0 $sql_filter
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
			WHERE c.id > 0 $sql_filter
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
			WHERE r.id $sql_filter
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
        $grade_avg = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "cmc.");

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
				LEFT JOIN (SELECT gi.courseid, g.userid, $grade_avg AS score FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
				LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) as l ON l.courseid = c.id AND l.userid = u.id
				LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
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
        $columns = array_merge(array("c.fullname","gi.itemname", "u.firstname", "u.lastname", "u.email", "graduated", "grade", "completionstate", "timespend", "visits","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.course");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $grade_single = intelliboard_grade_sql(false, $params);

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend as timespend, l.visits as visits";
            $sql_join = " LEFT JOIN (SELECT userid, param, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY userid, param) l ON l.param = cm.id AND l.userid = u.id";
        }

        return $this->get_report_data("
			SELECT g.id,
				gi.itemname,
				g.userid,
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
				c.fullname,
				g.timemodified as graduated,
				$grade_single AS grade,
				cm.completion,
				cmc.completionstate
				$sql_columns
			FROM {grade_grades} g
				JOIN {grade_items} gi ON gi.id=g.itemid
				JOIN {user} u ON u.id = g.userid
				JOIN {modules} m ON m.name = gi.itemmodule
				JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
				JOIN {course} c ON c.id=cm.course
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
        $grade_single = intelliboard_grade_sql(false, $params);

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
				LEFT JOIN (SELECT gi.courseid, g.userid, $grade_single AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY  gi.courseid, g.userid) as g ON g.courseid = c.id AND g.userid = u.id
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
			WHERE lit.id > 0 $sql_filter GROUP BY lit.userid, lit.courseid $sql_order", $params);
    }
    public function report32($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "courses","lit.timesite","lit.timecourses","lit.timeactivities","u.timecreated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_join_filter = "";

        if((isset($params->custom) and $params->custom == 1) or $params->userid){
            $sql_join_filter = $this->get_filterdate_sql($params, "l.timepoint");
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
				lit.timesite,
				lit.timecourses,
				lit.timeactivities
				$sql_columns
			FROM {role_assignments} ra
                JOIN {user} u ON u.id = ra.userid
                JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                LEFT JOIN (SELECT t.userid,
            			SUM(l.timespend) as timesite,
                        SUM(CASE WHEN t.courseid > 0 THEN l.timespend ELSE null END) as timecourses ,
                        SUM(CASE WHEN t.page = 'module' THEN l.timespend ELSE null END) as timeactivities
                   	FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                   	WHERE l.trackid = t.id
                    GROUP BY t.userid) as lit ON lit.userid = u.id
				$sql_join
			WHERE u.id > 0 $sql_filter
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
        $grade_single = intelliboard_grade_sql(false, $params);
        $completion = $this->get_completion($params, "cmc.");

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);
        $this->params['userid3'] = intval($params->userid);
        $this->params['userid4'] = intval($params->userid);

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
				JOIN {user} u ON u.id = ue.userid
				JOIN {enrol} e ON e.id = ue.enrolid
				JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
				LEFT JOIN (SELECT gi.courseid, $grade_single AS grade
					FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.userid = :userid3
				GROUP BY gi.courseid) as gc ON gc.courseid = c.id
				LEFT JOIN (SELECT courseid, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} WHERE userid = :userid4 GROUP BY courseid) l ON l.courseid = c.id
				LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
				LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 $completion AND cmc.userid = :userid1 GROUP BY cm.course) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
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
        $grade_single = intelliboard_grade_sql(false, $params);

        $this->params['userid'] = intval($params->userid);

        $data = $this->get_report_data("
			SELECT gi.id,
				gi.itemname,
				gi.courseid,
				cm.completionexpected,
				g.userid,
				g.timemodified as graduated,
				$grade_single as grade,
				cm.completion,
				cmc.completionstate,
				l.timespend,
				l.visits
			FROM {grade_items} gi
				LEFT JOIN {user} u ON u.id = :userid
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
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
			FROM {local_intelliboard_tracking} l, {course} c
			WHERE c.id = l.courseid AND l.userid = :userid $sql_having $sql_order", $params);
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
			WHERE ue.id > 0 $sql_filter
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
			WHERE u.id > 0 $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report40($params)
    {
        $columns = array_merge(array("course", "u.firstname", "u.lastname", "email", "e.enrol", "ue.timecreated", "grade", "lastaccess", ""), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql1 = $this->get_filterdate_sql($params, "l.timepoint");
        $grade_single = intelliboard_grade_sql(false, $params);

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
				$grade_single AS grade,
				c.id as cid,
				c.fullname as course
				$sql_columns
			FROM {user_enrolments} ue
				JOIN {user} u ON u.id = ue.userid
				JOIN {enrol} e ON e.id = ue.enrolid
				JOIN {course} c ON c.id = e.courseid
				LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
				LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
        		LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
				LEFT JOIN (SELECT t.id,t.userid,t.courseid FROM
					{local_intelliboard_tracking} t,
					{local_intelliboard_logs} l
				WHERE l.trackid = t.id $sql1 GROUP BY t.courseid, t.userid) as l ON l.courseid = e.courseid AND l.userid = ue.userid $sql_join
			WHERE l.id IS NULL $sql_filter $sql_having $sql_order", $params);
    }
    public function report41($params)
    {
        $columns = array_merge(array("course", "u.firstname","u.lastname","email", "certificate", "ci.timecreated", "ci.code","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country",""), $this->get_filter_columns($params));

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
			WHERE ci.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report43($params)
    {
        $columns = array_merge(array("user", "completed_courses", "grade", "lit.visits", "lit.timespend", "u.timecreated"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");

        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $grade_avg = intelliboard_grade_sql(true, $params);

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", lit.timespend, lit.visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) lit ON lit.userid = u.id";
            $sql_having = ", lit.timespend, lit.visits " . $sql_having;
        }

        return $this->get_report_data("
			SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as user,
				u.email,
				u.timecreated,
				$grade_avg AS grade,
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
			WHERE u.id > 0 $sql_filter
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
			WHERE c.id > 0 $sql_filter
			GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report45($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "all_att", "timespend", "highest_grade", "lowest_grade", "cmc.timemodified"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $completion = $this->get_completion($params, "cmc.");

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
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id $completion AND cmc.userid=qa.userid
			WHERE q.id= :courseid $sql_filter
			GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report42($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","u.email", "c.fullname", "started", "grade", "grade", "cmc.completed", "grade", "complete", "lit.visits", "lit.timespend"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $grade_avg = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "cmc.");

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
                $sql_filter .= ' AND ('.implode(' OR ',$grades).')';
            }
        }

        return $this->get_report_data("
			SELECT ra.id,
				cri.gradepass,
				u.email,
				ra.userid,
				ra.timemodified as started,
				c.id as cid,
				c.fullname,
				git.average,
				$grade_avg AS grade,
				cmc.completed,
				u.firstname,
				u.lastname,
				lit.timespend,
				lit.visits,
				c.enablecompletion,
				cc.timecompleted as complete
				$sql_columns
			FROM {role_assignments} AS ra
				JOIN {user} u ON ra.userid = u.id
				JOIN {context} AS ctx ON ctx.id = ra.contextid
				JOIN {course} c ON c.id = ctx.instanceid
				JOIN {enrol} e ON e.courseid = c.id
				JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = u.id
				LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id
				LEFT JOIN {course_completion_criteria} cri ON cri.course = c.id AND cri.criteriatype = 6
				LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
				LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
				LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) lit ON lit.courseid = c.id AND lit.userid = u.id
				LEFT JOIN (SELECT gi.courseid, $grade_avg AS average
						FROM {grade_items} gi, {grade_grades} g
						WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
						GROUP BY gi.courseid) git ON git.courseid=c.id
				LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id $completion GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
			WHERE u.id > 0 $sql_filter
			GROUP BY ra.userid, ctx.instanceid $sql_having $sql_order", $params);
    }
    public function report46($params)
    {
        $this->params['userid'] = intval($params->userid);

        $grade_single = intelliboard_grade_sql(false, $params);

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
			   $grade_single as grade,
			   g.rawgrademax as max_grade,
			   cc.timecompleted as course_completed,
			   g.timemodified as timegraded,
			   IF(gi.itemmodule='assign',MAX(ass.timecreated),
				 IF(gi.itemmodule='quiz',MAX(qa.timefinish),NULL)) as lastsubmit
			FROM {grade_items} gi
				LEFT JOIN {grade_categories} gc ON IF(gi.itemtype='category' OR gi.itemtype='course' ,gc.id=gi.iteminstance,gc.id=gi.categoryid)
				LEFT JOIN {grade_grades} g ON g.itemid=gi.id
				LEFT JOIN {course} c ON c.id=gi.courseid
				LEFT JOIN {quiz_attempts} qa ON qa.quiz=gi.iteminstance AND qa.userid=g.userid AND qa.state='finished'
				LEFT JOIN {assign_submission} ass ON ass.assignment=gi.iteminstance AND ass.userid=g.userid AND ass.status='submitted'
				LEFT JOIN {course_completions} cc ON cc.course=gi.courseid AND cc.userid=g.userid
			WHERE g.userid = :userid AND gi.id IS NOT NULL
			GROUP BY gi.id
			ORDER BY gc.depth desc, gc.id ASC", $params);
    }
    public function report47($params)
    {
        $columns = array_merge(array("u_related.firstname","u_related.lastname", "u_related.email", "course_name", "role", "lsl.all_count", "user_action", "action_role", "action", "timecreated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "ra.userid");
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
			WHERE ra.id > 0 $sql_filter
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
		$columns = array_merge(array("u.firstname", "u.lastname", "u.email", "u.idnumber", "course", "assignment", "a.duedate", "s.status", "u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country",""), $this->get_filter_columns($params));

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
				u.idnumber,
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
				LEFT JOIN {modules} m ON m.name = 'assign'
				LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
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
        $columns = array("mc_course", "mco_name", "mci_userid", "mci_certid", "mu_firstname", "mu_lastname", "issue_date");

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
			WHERE mci.id > 0 $sql_filter $sql_having $sql_order", $params);
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
            "course_name",
            "course_shortname",
            "course_idnumber",
            "cc.timecompleted", "grade"),
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
        $grade_single = intelliboard_grade_sql(false, $params);

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
				c.shortname as course_shortname,
				c.idnumber as course_idnumber,
				$grade_single AS grade
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
			WHERE mf.id > 0 $sql_filter $sql_having $sql_order", $params, false);

        foreach( $data as $k=>$v ){
            $data[$k] = $this->parseFeedbackAnswer($v);
        }

        return array("data" => $data);
    }

    public function report77($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "u.firstname",
            "u.lastname",
            "programs",
            "credits",
            "cc.timecompleted"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "u.id", "users");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "cc.timecompleted");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql_having .= ($sql_having) ? " AND lesson <> '' AND programs <> '' AND credits <> ''" : "HAVING programs <> '' AND credits <> ''";

		$sql_cols = "";
		for($i = 1; $i < 25; $i++){
			$sql_cols .= " WHEN o.name = 'program_number_$i' THEN (SELECT value FROM {course_format_options} WHERE name = 'credit_hours_$i' AND courseid = c.id)";
		}
		$sql_columns .= ($sql_cols) ? ", CASE $sql_cols ELSE 'NONE' END AS credits" : ", '' AS credits";

		$sql_cols = "";
		for($i = 1; $i < 25; $i++){
			$sql_cols .= " WHEN o.name = 'program_number_$i' THEN (SELECT value FROM {course_format_options} WHERE name = 'lesson_name_$i' AND courseid = c.id)";
		}
		$sql_columns .= ($sql_cols) ? ", CASE $sql_cols ELSE 'NONE' END AS lesson" : ", '' AS lesson";

        return $this->get_report_data("
        	SELECT
				CONCAT(cc.id, '-', o.id) AS id,
				u.firstname,
				u.lastname,
				u.email,
				c.fullname,
				cc.timecompleted,
				o.value AS programs
				$sql_columns
			FROM {course_completions} cc, {course} c, {user} u, {course_format_options} o
			WHERE cc.timecompleted > 0 AND u.id = cc.userid AND c.id = cc.course AND o.courseid = c.id AND o.name like '%program_%' $sql_filter $sql_having $sql_order", $params);
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

        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "l.courseid");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "l.lastaccess");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        if($params->custom2){
	        $sql = $this->get_filter_in_sql($params->custom2, 'roleid');
	        $sql_filter .= " AND l.userid IN (SELECT DISTINCT userid FROM {role_assignments} WHERE userid > 0 $sql)";
	    }

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
				l.userid,
				l.courseid,
				m.name as module
				$sql_columns
			FROM {local_intelliboard_tracking} l
				JOIN {user} u ON u.id = l.userid
				JOIN {course} c ON c.id = l.courseid
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
				JOIN {user} u ON u.id = l.userid
				JOIN {course} c ON c.id = l.courseid
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
			SELECT DISTINCT @x:=@x+1 as id, lit.id AS lid, u.firstname,u.lastname, u.email, c.fullname, c.shortname, lit.visits, lit.timespend, lit.firstaccess,lit.lastaccess, cm.instance, m.name as module $sql_columns
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
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 'timecompleted') : '';
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
        $completion = $this->get_completion($params, "cmc.");

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
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql1
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
	        $sql_filter .= " AND l1.userid IN (SELECT DISTINCT userid FROM {role_assignments} WHERE userid > 0 $sql)";
	    }

        return $this->get_report_data("
            SELECT l1.id,
               u.firstname,
               u.lastname,
               u.timecreated AS registered,
               l1.userid,
               l1.timecreated AS loggedin,
               (SELECT l2.timecreated FROM {logstore_standard_log} l2 WHERE l2.userid = l1.userid and l2.action = 'loggedout' and l2.id > l1.id LIMIT 1) AS loggedout
               $sql_columns
			FROM {logstore_standard_log} l1
                JOIN {user} u ON u.id = l1.userid
			WHERE l1.action = 'loggedin' $sql_filter $sql_having $sql_order", $params);
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
            $sql_select[] = "(SELECT CONCAT(firstname,' ',lastname) FROM {user} WHERE id > 0 $sql_filter8) as user";
        }
        $sql_select = ($sql_select) ? ", " . implode(",", $sql_select) : "";
        $completion = $this->get_completion($params, "cmc.");
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

			(SELECT count(cmc.id) FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1
			$completion AND cm.course = :cx4 $sql_filter7) as modules_completed
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
            "tr.review_year",
            "overal_rating",
            "overal_perfomance_rating",
            "behaviors_rating",
            "tr.promotability_hp1",
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
            "tr.behaviors_intelligent",
            "tr.name",
            "tr.jde_eeid"),
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
				tr.name AS form_name,
				tr.jde_eeid AS form_jde,
				ps.fullname AS position,
				tr.title as job_title,
				tr.review_year,
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
                tr.promotability_hp1,
				tr.promotability_hp2,
				tr.promotability_trusted,
				tr.promotability_placement,
				tr.promotability_too_new,
                relocatability as mobility
				$sql_columns
			FROM {local_talentreview} tr
				LEFT JOIN {user} u ON u.id = tr.user_id
				LEFT JOIN {pos_assignment} pa ON pa.userid = u.id
				LEFT JOIN {pos} ps ON ps.id = pa.positionid
			WHERE tr.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report90($params)
    {
        $columns = array_merge(array(
            'outcome_shortname',
            'outcome_fullname',
            'outcome_description',
            'activity',
            'sci.scale',
            'average_grade',
            'grades',
            'course_fullname',
            'course_shortname',
            'category',
            'c.startdate'), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom2,'o.id');
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_avg = intelliboard_grade_sql(true, $params);

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
				$grade_avg AS average_grade,
				COUNT(DISTINCT g.id) AS grades
            FROM {grade_outcomes} o
                LEFT JOIN {course} c ON c.id = o.courseid
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN {scale} sci ON sci.id = o.scaleid
                LEFT JOIN {grade_items} gi ON gi.outcomeid = o.id
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id
            WHERE gi.itemtype = 'mod' $sql_filter
            GROUP BY g.itemid $sql_having $sql_order", $params, false);

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
        $completion = $this->get_completion($params, "cmc.");

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
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql1
			WHERE cm.id > 0 $sql_filter
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
            WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params);
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
        $completion = $this->get_completion($params, "x.");

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
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
                LEFT JOIN (SELECT course, count(id) as modules FROM {course_modules} WHERE visible = 1 AND completion > 0 GROUP BY course) as m ON m.course = c.id
                LEFT JOIN (SELECT cm.course, x.userid, COUNT(DISTINCT x.id) as completed FROM {course_modules} cm, {course_modules_completion} x WHERE x.coursemoduleid = cm.id AND cm.visible = 1 $completion GROUP BY cm.course, x.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
            WHERE ue.id > 0 $sql_filter
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
               u.phone1,
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
			WHERE c.id > 0 $sql_filter $sql_having $sql_order ", $params);
    }

    public function report98($params)
    {
        $columns = array_merge(array("u.id", "u.firstname", "u.lastname", "u.email", "c.fullname", "timespend", "visits"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
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
	           $sql_columns
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
			WHERE u.id > 0 $sql_filter
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
        $columns = array_merge(array("u.firstname", "u.lastname","ue.timecreated", "e.enrol", "e.cost", "c.fullname"), $this->get_filter_columns($params));
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
				u.firstname,
				u.lastname,
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
            GROUP BY timepoint
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
                  GROUP BY timepoint
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
                  GROUP BY timepoint
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
        $columns = array_merge(array("l.timecreated", "l.userid", "u.firstname", "u.lastname", "u.email", "course", "l.objecttable", "activity", "l.origin", "l.ip"), $this->get_filter_columns($params));

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
                    u.firstname,
					u.lastname,
                    l.timecreated,
                    (CASE WHEN l.objecttable = 'forum_discussions' THEN (SELECT name FROM {forum_discussions} WHERE id = l.objectid) ELSE (CASE WHEN l.objecttable = 'forum_posts' THEN (SELECT subject FROM {forum_posts} WHERE id = l.objectid) ELSE '' END) END) AS forumitem
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

        $sql1 = "";
        $sql2 = "";
        if($params->filter){
            $sql1 .= " AND q.course = :course1 ";
            $sql2 .= " AND q.course = :course2 ";
            $this->params['course1'] = intval($params->filter);
            $this->params['course2'] = intval($params->filter);
        }
        if($params->custom){
            $sql1 .= " AND q.id = :custom1 ";
            $sql2 .= " AND q.id = :custom2 ";
            $this->params['custom1'] = intval($params->custom);
            $this->params['custom2'] = intval($params->custom);
        }
        return $DB->get_record_sql("
                SELECT
                    (SELECT COUNT(DISTINCT t.tagid) AS tags
                        FROM {quiz} q, {quiz_slots} qs, {tag_instance} t
                        WHERE qs.quizid = q.id AND t.itemid = qs.questionid AND t.itemtype ='question' $sql1
                        GROUP BY q.course
                        ORDER BY tags DESC
                        LIMIT 1) AS tags,
                    (SELECT MAX(qm.attempt) FROM {quiz_attempts} qm, {quiz} q WHERE qm.quiz = q.id $sql2) AS attempts
               ", $this->params);
    }


    public function report56($params){
        $columns = array("username", "c.fullname", "e.enrol", "l.visits", "l.timespend", "progress", "grade", "cc.timecompleted", "ue.timecreated");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom,'ue.userid',false);
        $grade_single = intelliboard_grade_sql(false, $params);
        $completion = $this->get_completion($params, "cmc.");
        $this->params['userid'] = $params->userid;

        return $this->get_report_data("
            SELECT ue.id,
                    CONCAT(u.firstname, ' ', u.lastname) AS username,
                    u.id AS userid,
                    ue.timecreated AS enrolled,
                    $grade_single AS grade,
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
                LEFT JOIN (SELECT cm.course, count(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 $completion AND cmc.userid=:userid GROUP BY cm.course) cmc ON cmc.course = c.id
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
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
                FROM {course_modules} cm
                  LEFT JOIN {modules} m ON m.id=cm.module
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
            WHERE $courseid
            GROUP BY c.id $sql_having $sql_order", $params);
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
        $this->params['competency_id'] = clean_param($params->custom, PARAM_INT);

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
            WHERE com.id=:competency_id AND u.id IS NOT NULL
            GROUP BY u.id,c.id $sql_having $sql_order", $params,false);

        return array('data'=>$data, 'scale'=>$scale->scale_items);
    }
    function report100($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","cp.name","cp.timecreated","cp.status","progress"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $this->params['template_id'] = $params->custom;

        return $this->get_report_data("
            SELECT
              cp.id,
              u.firstname,
              u.lastname,
              u.id as userid,
              cp.name,
              cp.timecreated,
              cp.status,
              ROUND((SUM(cu.proficiency)/COUNT(ctc.id))*100,2) as progress
              $sql_columns
            FROM {competency_template} ct
              LEFT JOIN {competency_templatecomp} ctc ON ctc.templateid=ct.id
              LEFT JOIN {competency_plan} cp ON cp.templateid=ct.id
              LEFT JOIN {competency_usercomp} cu ON cu.competencyid=ctc.competencyid AND cu.userid=cp.userid
              LEFT JOIN {user} u ON u.id=cp.userid
            WHERE ct.id=:template_id AND cp.id IS NOT NULL
            GROUP BY cp.id $sql_having $sql_order", $params);
    }
    function report101($params)
    {
        $columns = array_merge(array("course","forum","discussion","post","fp.created","u.firstname","u.lastname","last_reply_time","count_reply","last_reply_time","last_reply_firstname"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_filterdate_sql($params, "fp.created");
        $courseid1 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid,'d.course',false):1;
        $courseid2 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid,'f.course',false):1;

        return $this->get_report_data("
            SELECT
              fp.id,
              c.fullname AS course,
              f.name AS forum,
              fd.name AS discussion,
              fp.subject AS post,
              u.id AS userid,
              u.firstname,
              u.lastname,
              fp.created,
              (SELECT COUNT(id) FROM {forum_posts} WHERE parent=fp.id) as count_reply,
              last_reply.time AS last_reply_time,
              last_reply.firstname AS last_reply_firstname,
              last_reply.lastname AS last_reply_lastname,
              last_reply.id AS last_reply_userid
              $sql_columns
            FROM {forum} f
              LEFT JOIN {forum_discussions} fd ON fd.forum=f.id
              LEFT JOIN {forum_posts} fp ON fp.discussion=fd.id
              LEFT JOIN {course} c ON c.id=f.course
              LEFT JOIN {user} u ON u.id=fp.userid
              LEFT JOIN (SELECT *
                          FROM(
                            SELECT
                              post.created AS time,
                              ur.firstname,
                              ur.lastname,
                              ur.id,
                              post.parent
                            FROM {forum_posts} post
                              LEFT JOIN {user} ur ON ur.id=post.userid
                              LEFT JOIN {forum_discussions} d ON d.id=post.discussion
                            WHERE $courseid1
                            ORDER BY post.created DESC
                          ) p
                         GROUP BY p.parent) last_reply ON last_reply.parent=fp.id
            WHERE $courseid2 AND u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params);
    }
    function report102($params)
    {
        $columns = array_merge(array(
        	"course",
        	"assignment",
        	"u.firstname",
        	"u.lastname",
        	"u.idnumber",
        	"u.lastaccess",
        	"lit.timespend",
        	"ass_s.timecreated",
        	"ass_g.grade",
        	"cmc.completionstate",
        	"ass_sl.timemodified"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filterdate_sql($params, "ass_s.timecreated");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->custom,'ass.id');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        return $this->get_report_data("
            SELECT
              @x:=@x+1 AS uniqueid,
              ass_s.id,
              c.fullname AS course,
              u.firstname,
              u.lastname,
              u.idnumber,
              u.id AS userid,
              ass.name AS assignment,
              u.lastaccess,
              lit.timespend,
              ass_s.timecreated AS date_first_submission,
              ass_g.grade AS grade_first_submission,
              cmc.completionstate,
              ass_sl.timemodified AS date_last_submission,
              ass_gl.grade AS grade_last_submission,
              ass_s.attemptnumber,
              ass_sl.attemptnumber AS attemptnumber2,
              sc.scale
              $sql_columns
            FROM (SELECT @x:= 0) AS x,{course} c
                LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=c.id
                LEFT JOIN {role_assignments} ra ON ra.contextid=con.id $learner_roles
                LEFT JOIN {user} u ON u.id=ra.userid
                LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=u.id AND lit.page='course' AND lit.param=c.id

                LEFT JOIN {assign} ass ON c.id=ass.course

                LEFT JOIN {assign_submission} ass_s ON ass_s.assignment=ass.id AND ass_s.attemptnumber=0 AND ass_s.userid = u.id AND ass_s.status = 'submitted'
                LEFT JOIN {assign_grades} ass_g ON ass_g.userid=ass_s.userid AND ass_g.assignment=ass_s.assignment AND ass_g.attemptnumber=ass_s.attemptnumber

                LEFT JOIN {assign_submission} ass_sl ON ass_sl.assignment=ass.id AND ass_sl.latest=1 AND ass_sl.userid=ass_s.userid AND ass_sl.id <> ass_s.id
                LEFT JOIN {assign_grades} ass_gl ON ass_gl.userid=ass_sl.userid AND ass_gl.assignment=ass_sl.assignment AND ass_gl.attemptnumber=ass_sl.attemptnumber

                JOIN {modules} m ON m.name='assign'
                LEFT JOIN {course_modules} cm ON cm.course=ass.course AND cm.module=m.id AND cm.instance=ass.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid=ass_s.userid AND cmc.coursemoduleid=cm.id

                LEFT JOIN {grade_items} gi ON gi.courseid=ass.course AND gi.itemtype='mod' AND gi.itemmodule='assign' AND gi.iteminstance=ass.id
                LEFT JOIN {scale} sc ON sc.id=gi.scaleid
            WHERE u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params);
    }
	function report103($params)
    {
        $columns = array_merge(array("t.fullname","t.name","t.activity","t.firstname","t.lastname", "t.time_on"), $this->get_filter_columns($params));

        $sql_having1 = $this->get_filter_sql($params, $columns);
        $sql_having2 = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns1 = $this->get_columns($params, "u.id");
        $sql_columns2 = $this->get_columns($params, "u.id");
        $sql_filter1 = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter1 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter1 .= $this->get_filter_course_sql($params, "c.");
        $sql_filter1 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter1 .= $this->get_filter_enrol_sql($params, "e.");

        $sql_filter2 = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter2 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");
        $sql_filter2 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter2 .= $this->get_filter_enrol_sql($params, "e.");
        $sql1 = $sql2 = '';

        if($params->userid > 0){
            $sql1 = 'LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=c.id
                LEFT JOIN {role_assignments} ra ON ra.contextid=con.id AND ra.userid=:userid1 '.$this->get_filter_in_sql($params->teacher_roles,'ra.roleid');

            $sql2 = 'LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=c.id
                LEFT JOIN {role_assignments} ra ON ra.contextid=con.id AND ra.userid=:userid2 '.$this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
            $this->params['userid1'] = $this->params['userid2'] = $params->userid;

            $sql_filter1 .= ' AND ra.id IS NOT NULL';
            $sql_filter2 .= ' AND ra.id IS NOT NULL';
        }

        return $this->get_report_data("
            SELECT t.* FROM ((SELECT
              CONCAT(cm.id,u.id,s.id) AS uniqueid,
              cm.id AS cmid,
              a.id,
              a.name,
              u.id as userid,
              u.firstname,
              u.lastname,
              c.fullname,
              s.timemodified as time_on,
              'assignment' AS activity,
              '' as slot,
              '' as attempt
              $sql_columns1
            FROM {assign_submission} s
              LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid AND g.attemptnumber = s.attemptnumber
              LEFT JOIN {assign} a ON a.id = s.assignment
              LEFT JOIN {course} c ON c.id=a.course
              LEFT JOIN {user} u ON u.id = s.userid

              JOIN {modules} m ON m.name='assign'
              JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=a.id AND cm.visible=1
              JOIN {enrol} e ON e.courseid=c.id
              JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.userid=u.id
              $sql1
            WHERE s.latest = 1 AND s.timemodified IS NOT NULL AND s.status = 'submitted' AND (s.timemodified >= g.timemodified OR g.timemodified IS NULL OR g.grade IS NULL)
            $sql_filter1 $sql_having2)

            UNION ALL

            (SELECT
              CONCAT(cm.id,u.id,quiza.id) AS uniqueid,
              cm.id AS cmid,
              qz.id,
              qz.name,
              u.id as userid,
              u.firstname,
              u.lastname,
              c.fullname,
              quiza.timefinish as time_on,
              'quiz' AS activity,
              qa.slot,
              quiza.id as attempt
              $sql_columns2
            FROM {quiz_attempts} quiza
              LEFT JOIN {question_attempts} qa ON qa.questionusageid = quiza.uniqueid
              LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id AND qas.sequencenumber = (
                    SELECT MAX(sequencenumber)
                    FROM {question_attempt_steps}
                    WHERE questionattemptid = qa.id
                    )
              LEFT JOIN {quiz} qz ON qz.id = quiza.quiz
              LEFT JOIN {course} c ON c.id=qz.course
              LEFT JOIN {user} u ON u.id=quiza.userid

              JOIN {modules} m ON m.name='quiz'
              JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=qz.id AND cm.visible=1
              JOIN {enrol} e ON e.courseid=c.id
              JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.userid=u.id
              $sql2
            WHERE quiza.preview = 0 AND quiza.state = 'finished' AND qas.state='needsgrading'
            $sql_filter2 $sql_having1)) t $sql_order", $params);
    }

    function report104($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","u.email","u.username","u.idnumber"), $this->get_filter_columns($params));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        foreach($modules['modules'] as $module){
        	$completion = $this->get_completion($params, "");

            $module = (object)$module;
            $sql_select .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion) AS completed_$module->id";
            $columns[] = "completed_$module->id";
        }
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom,'ctx.instanceid');
        $sql_columns = $this->get_columns($params, "u.id");
        $sql = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $data = $this->get_report_data("
            SELECT
              u.id,
              u.email,
              u.username,
              u.idnumber,
              u.firstname,
              u.lastname
              $sql_select
              $sql_columns

            FROM {context} ctx
              LEFT JOIN {role_assignments} ra ON ctx.id = ra.contextid $sql
              LEFT JOIN {user} u ON u.id=ra.userid
            WHERE ctx.contextlevel = 50 AND u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params,false);

        return array('modules' => $modules['modules'],
            'data'    => $data);
    }
    function get_course_modules($params)
    {
        global $DB;

        $sql_modules = $this->get_modules_sql('');
        $course = clean_param($params->custom, PARAM_INT);
        $sql_filter = "AND cm.completion>0";
        if($params->custom2 == 'all'){
            $sql_filter = "";
        }

        $modules = $DB->get_records_sql("
                        SELECT
                          cm.id
                          $sql_modules
                        FROM {course_modules} cm
                          LEFT JOIN {modules} m ON m.id=cm.module
                        WHERE cm.course=:course $sql_filter",array('course'=>$course));

        return array('modules' => $modules);
    }
    function get_course_assignments($params)
    {
        global $DB;

        $sql = "";
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'course',false);
        }
        return $DB->get_records_sql("SELECT id, name FROM {assign} $sql", $this->params);
    }

    function report105($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","c.shortname","ce.descidentifier","ce.url","ce.timecreated"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_filter = $this->get_filter_in_sql($params->custom2,'c.id');

        $data = $this->get_report_data("
            SELECT
              ce.id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              c.shortname,
              ce.url,
              ce.descidentifier,
              ce.desccomponent,
              ce.desca,
              ce.timecreated
              $sql_columns
            FROM {competency_evidence} ce
              LEFT JOIN {competency_usercomp} cu ON ce.usercompetencyid = cu.id
              LEFT JOIN {competency} c ON c.id=cu.competencyid
              LEFT JOIN {user} u ON u.id=cu.userid
            WHERE u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params, false);

        foreach($data as &$item){
            $item->desc = get_string($item->descidentifier,$item->desccomponent,$item->desca);
        }

        return array('data'=>$data);
    }
    function report106($params)
    {
        $params->custom = json_decode($params->custom);
        $this->params['module'] = clean_param($params->custom->module, PARAM_INT);
        $this->params['course'] = clean_param($params->custom->course, PARAM_INT);

        $sql_filter = '';
        if($params->timestart > 0){
            $sql_filter = "AND (lit.lastaccess<:timestart OR lit.lastaccess IS NULL)";
            $this->params['timestart'] = $params->timestart;
        }

        return $this->get_report_data("
            SELECT
              u.id,
              u.firstname,
              u.lastname,
              lit.lastaccess
            FROM {enrol} e
              LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
              LEFT JOIN {user} u ON u.id=ue.userid
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ue.userid AND lit.page='module' AND lit.param=:module
            WHERE e.courseid=:course AND ue.userid IS NOT NULL $sql_filter
            GROUP BY ue.userid", $params);
    }
    function report107($params)
    {
        $columns = array("c.fullname","d.name");

        $sql_syb_where = $sql_join = $sql_filter = '';
        $sql_columns = 'd.id,';
        $sql_group = '';
        if($params->custom == 2){
            $sql_columns = 'CONCAT(d.id,u.id) as id,u.id as userid,u.firstname,u.lastname,';
            $sql_syb_where =  ' AND dr.userid=u.id';
            $sql_join =  "LEFT JOIN {data_records} mdr ON mdr.dataid=d.id
                          LEFT JOIN {user} u ON u.id=mdr.userid";
            $sql_filter .= ' AND u.id IS NOT NULL';
            $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
            $sql_group = ' GROUP BY d.id,u.id';

            $columns[] = 'u.firstname';
            $columns[] = 'u.lastname';
            $columns[] = 'avg_answer';
        }elseif($params->custom == 3){
            $sql_columns = 'CONCAT(d.id,u.id,mdr.id) as id,u.id as userid,u.firstname,u.lastname,mdr.timecreated as submitted,';
            $sql_syb_where =  ' AND dr.userid=u.id AND dr.id=mdr.id';
            $sql_join =  "LEFT JOIN {data_records} mdr ON mdr.dataid=d.id
                          LEFT JOIN {user} u ON u.id=mdr.userid";
            $sql_filter .= ' AND u.id IS NOT NULL';
            $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
            $sql_group = ' GROUP BY d.id,u.id,mdr.id';

            $columns[] = 'u.firstname';
            $columns[] = 'u.lastname';
            $columns[] = 'avg_answer';
            $columns[] = 'mdr.timecreated';

            $custom3 = explode(',',$params->custom3);
            if(!empty($custom3)){
                foreach($custom3 as $item){
                    $item = clean_param($item, PARAM_INT);
                    $sql_columns .= "(SELECT content FROM {data_content} WHERE fieldid=$item AND recordid=mdr.id) as field_$item,";
                }
            }
        }

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'d.course');
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'d.id');

        return $this->get_report_data("
            SELECT
              $sql_columns
              d.name,
              c.fullname as course,
              c.id as courseid,
              (
                SELECT
                  AVG(dc.content)
                FROM {data_records} dr
                  LEFT JOIN {data_content} dc ON dc.recordid=dr.id
                  LEFT JOIN {data_fields} df ON df.id=dc.fieldid
                WHERE df.type='menu' AND dr.dataid=d.id AND dc.content REGEXP '[0-9]' $sql_syb_where
              ) avg_answer

            FROM {data} d
              LEFT JOIN {course} c ON c.id=d.course
              $sql_join
            WHERE 1 $sql_filter $sql_group $sql_having $sql_order
            ", $params);
    }
    function report108($params)
    {
        $columns = array("c.fullname","d.name","df.name","count_answers","avg_answer");

        $max_answer = clean_param($params->custom, PARAM_INT);
        $sql = '';
        for($i=0;$i<=$max_answer;$i++){
            $columns[] = "count_answer_$i";
            $prev = $i-1;
            if($i==0){
                $sql .= ",SUM(IF(dc.content = '', 1,0)) AS count_answer_$i";
            }else{
                $sql .= ",SUM(IF(dc.content>$prev AND dc.content<=$i, 1,0)) AS count_answer_$i";
            }
        }

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'d.course');
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'d.id');

        return $this->get_report_data("
            SELECT
              df.id,
              c.fullname AS course,
              d.name,
              df.name AS question,
              COUNT(DISTINCT dc.id) AS count_answers,
              (
                SELECT
                  AVG(dc2.content)
                FROM {data_content} dc2
                WHERE dc2.fieldid=df.id AND dc2.content REGEXP '[0-9]'
              ) avg_answer
              $sql

            FROM {data} d
              LEFT JOIN {course} c ON c.id=d.course
              LEFT JOIN {data_fields} df ON df.dataid=d.id
              LEFT JOIN {data_content} dc ON dc.fieldid=df.id
            WHERE df.type='menu' AND df.param1 REGEXP '[0-9]' $sql_filter
            GROUP BY df.id $sql_having $sql_order
            ", $params);
    }
    function report109($params)
    {
    	$columns = array_merge(array(
    		"c.fullname",
    		"a.name",
    		"u.firstname",
    		"u.lastname",
    		"u.idnumber",
    		"s.attemptnumber",
    		"s.timemodified",
    		"s.status",
    		"g.timemodified",
    		"g.timemodified"
    		), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "s.timemodified");

        return $this->get_report_data("
			SELECT l.id,
				a.name as assignment,
				a.id as assignmentid,
				a.duedate,
				c.fullname as course,
				c.id as courseid,
				s.status,
				s.userid,
				s.timemodified,
				u.firstname,
				u.lastname,
				u.idnumber,
				u2.firstname AS marker_firstname,
				u2.lastname AS marker_lastname,
				f.workflowstate,
				s.attemptnumber,
				cmc.completionstate,
				g.timemodified AS graded,
				g.grade
				$sql_columns
			FROM {assign_user_mapping} l
				LEFT JOIN {user} u ON u.id = l.userid
				LEFT JOIN {assign} a ON a.id = l.assignment
				LEFT JOIN {course} c ON c.id = a.course
				LEFT JOIN {assign_submission} s ON s.userid = u.id AND s.assignment = a.id
				LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = u.id AND g.attemptnumber = s.attemptnumber
				LEFT JOIN {assign_user_flags} f ON f.assignment = a.id AND f.userid = u.id
				LEFT JOIN {user} u2 ON u2.id = f.allocatedmarker
				LEFT JOIN {modules} m ON m.name = 'assign'
				LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
				LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid = u.id
			WHERE l.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    function report110($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "cc.name",
            "internet_explorer",
            "firefox",
            "chrome",
            "safari",
            "microsoft_edge",
            "opera",
            "netscape",
            "maxthon",
            "konqueror",
            "mobile_browser",
            "other_browser",
            "windows_10",
            "windows_8_1",
            "windows_8",
            "windows_7",
            "windows_vista",
            "windows_server",
            "windows_xp",
            "windows_2000",
            "windows_me",
            "windows_98",
            "windows_95",
            "windows_3_11",
            "mac_os_x",
            "mac_os_9",
            "linux",
            "ubuntu",
            "iphone",
            "ipod",
            "ipad",
            "android",
            "blackberry",
            "mobile",
            "other_platform",
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT
              c.id,
              c.fullname AS course,
              cc.name AS category_name,

              ROUND((SUM(IF(lit.useragent='Internet Explorer',lit.timespend,0))*100)/SUM(lit.timespend),2) AS internet_explorer,
              ROUND((SUM(IF(lit.useragent='Firefox',lit.timespend,0))*100)/SUM(lit.timespend),2) AS firefox,
              ROUND((SUM(IF(lit.useragent='Chrome',lit.timespend,0))*100)/SUM(lit.timespend),2) AS chrome,
              ROUND((SUM(IF(lit.useragent='Safari',lit.timespend,0))*100)/SUM(lit.timespend),2) AS safari,
              ROUND((SUM(IF(lit.useragent='Microsoft Edge',lit.timespend,0))*100)/SUM(lit.timespend),2) AS microsoft_edge,
              ROUND((SUM(IF(lit.useragent='Opera',lit.timespend,0))*100)/SUM(lit.timespend),2) AS opera,
              ROUND((SUM(IF(lit.useragent='Netscape',lit.timespend,0))*100)/SUM(lit.timespend),2) AS netscape,
              ROUND((SUM(IF(lit.useragent='Maxthon',lit.timespend,0))*100)/SUM(lit.timespend),2) AS maxthon,
              ROUND((SUM(IF(lit.useragent='Konqueror',lit.timespend,0))*100)/SUM(lit.timespend),2) AS konqueror,
              ROUND((SUM(IF(lit.useragent='Mobile browser',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mobile_browser,
              ROUND((SUM(IF(lit.useragent='Unknown Browser' OR lit.useragent IS NULL ,lit.timespend,0))*100)/SUM(lit.timespend),2) AS other_browser,

              ROUND((SUM(IF(lit.useros='Windows 10',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_10,
              ROUND((SUM(IF(lit.useros='Windows 8.1',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_8_1,
              ROUND((SUM(IF(lit.useros='Windows 8',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_8,
              ROUND((SUM(IF(lit.useros='Windows 7',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_7,
              ROUND((SUM(IF(lit.useros='Windows Vista',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_vista,
              ROUND((SUM(IF(lit.useros='Windows Server 2003/XP x64',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_server,
              ROUND((SUM(IF(lit.useros='Windows XP',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_xp,
              ROUND((SUM(IF(lit.useros='Windows 2000',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_2000,
              ROUND((SUM(IF(lit.useros='Windows ME',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_me,
              ROUND((SUM(IF(lit.useros='Windows 98',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_98,
              ROUND((SUM(IF(lit.useros='Windows 95',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_95,
              ROUND((SUM(IF(lit.useros='Windows 3.11',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_3_11,
              ROUND((SUM(IF(lit.useros='Mac OS X',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mac_os_x,
              ROUND((SUM(IF(lit.useros='Mac OS 9',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mac_os_9,
              ROUND((SUM(IF(lit.useros='Linux',lit.timespend,0))*100)/SUM(lit.timespend),2) AS linux,
              ROUND((SUM(IF(lit.useros='Ubuntu',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ubuntu,
              ROUND((SUM(IF(lit.useros='iPhone',lit.timespend,0))*100)/SUM(lit.timespend),2) AS iphone,
              ROUND((SUM(IF(lit.useros='iPod',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ipod,
              ROUND((SUM(IF(lit.useros='iPad',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ipad,
              ROUND((SUM(IF(lit.useros='Android',lit.timespend,0))*100)/SUM(lit.timespend),2) AS android,
              ROUND((SUM(IF(lit.useros='BlackBerry',lit.timespend,0))*100)/SUM(lit.timespend),2) AS blackberry,
              ROUND((SUM(IF(lit.useros='Mobile',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mobile,
              ROUND((SUM(IF(lit.useros='Unknown OS Platform' OR lit.useros IS NULL ,lit.timespend,0))*100)/SUM(lit.timespend),2) AS other_platform
            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
            GROUP BY lit.courseid $sql_having $sql_order", $params);
    }

    function report111($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "u.idnumber",
            "c.fullname",
            "cc.name",
            "internet_explorer",
            "firefox",
            "chrome",
            "safari",
            "microsoft_edge",
            "opera",
            "netscape",
            "maxthon",
            "konqueror",
            "mobile_browser",
            "other_browser",
            "windows_10",
            "windows_8_1",
            "windows_8",
            "windows_7",
            "windows_vista",
            "windows_server",
            "windows_xp",
            "windows_2000",
            "windows_me",
            "windows_98",
            "windows_95",
            "windows_3_11",
            "mac_os_x",
            "mac_os_9",
            "linux",
            "ubuntu",
            "iphone",
            "ipod",
            "ipad",
            "android",
            "blackberry",
            "mobile",
            "other_platform",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT
              lit.id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              u.idnumber,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,

              ROUND((SUM(IF(lit.useragent='Internet Explorer',lit.timespend,0))*100)/SUM(lit.timespend),2) AS internet_explorer,
              ROUND((SUM(IF(lit.useragent='Firefox',lit.timespend,0))*100)/SUM(lit.timespend),2) AS firefox,
              ROUND((SUM(IF(lit.useragent='Chrome',lit.timespend,0))*100)/SUM(lit.timespend),2) AS chrome,
              ROUND((SUM(IF(lit.useragent='Safari',lit.timespend,0))*100)/SUM(lit.timespend),2) AS safari,
              ROUND((SUM(IF(lit.useragent='Microsoft Edge',lit.timespend,0))*100)/SUM(lit.timespend),2) AS microsoft_edge,
              ROUND((SUM(IF(lit.useragent='Opera',lit.timespend,0))*100)/SUM(lit.timespend),2) AS opera,
              ROUND((SUM(IF(lit.useragent='Netscape',lit.timespend,0))*100)/SUM(lit.timespend),2) AS netscape,
              ROUND((SUM(IF(lit.useragent='Maxthon',lit.timespend,0))*100)/SUM(lit.timespend),2) AS maxthon,
              ROUND((SUM(IF(lit.useragent='Konqueror',lit.timespend,0))*100)/SUM(lit.timespend),2) AS konqueror,
              ROUND((SUM(IF(lit.useragent='Mobile browser',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mobile_browser,
              ROUND((SUM(IF(lit.useragent='Unknown Browser' OR lit.useragent IS NULL ,lit.timespend,0))*100)/SUM(lit.timespend),2) AS other_browser,

              ROUND((SUM(IF(lit.useros='Windows 10',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_10,
              ROUND((SUM(IF(lit.useros='Windows 8.1',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_8_1,
              ROUND((SUM(IF(lit.useros='Windows 8',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_8,
              ROUND((SUM(IF(lit.useros='Windows 7',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_7,
              ROUND((SUM(IF(lit.useros='Windows Vista',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_vista,
              ROUND((SUM(IF(lit.useros='Windows Server 2003/XP x64',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_server,
              ROUND((SUM(IF(lit.useros='Windows XP',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_xp,
              ROUND((SUM(IF(lit.useros='Windows 2000',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_2000,
              ROUND((SUM(IF(lit.useros='Windows ME',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_me,
              ROUND((SUM(IF(lit.useros='Windows 98',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_98,
              ROUND((SUM(IF(lit.useros='Windows 95',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_95,
              ROUND((SUM(IF(lit.useros='Windows 3.11',lit.timespend,0))*100)/SUM(lit.timespend),2) AS windows_3_11,
              ROUND((SUM(IF(lit.useros='Mac OS X',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mac_os_x,
              ROUND((SUM(IF(lit.useros='Mac OS 9',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mac_os_9,
              ROUND((SUM(IF(lit.useros='Linux',lit.timespend,0))*100)/SUM(lit.timespend),2) AS linux,
              ROUND((SUM(IF(lit.useros='Ubuntu',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ubuntu,
              ROUND((SUM(IF(lit.useros='iPhone',lit.timespend,0))*100)/SUM(lit.timespend),2) AS iphone,
              ROUND((SUM(IF(lit.useros='iPod',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ipod,
              ROUND((SUM(IF(lit.useros='iPad',lit.timespend,0))*100)/SUM(lit.timespend),2) AS ipad,
              ROUND((SUM(IF(lit.useros='Android',lit.timespend,0))*100)/SUM(lit.timespend),2) AS android,
              ROUND((SUM(IF(lit.useros='BlackBerry',lit.timespend,0))*100)/SUM(lit.timespend),2) AS blackberry,
              ROUND((SUM(IF(lit.useros='Mobile',lit.timespend,0))*100)/SUM(lit.timespend),2) AS mobile,
              ROUND((SUM(IF(lit.useros='Unknown OS Platform' OR lit.useros IS NULL ,lit.timespend,0))*100)/SUM(lit.timespend),2) AS other_platform
              $sql_columns
            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
              LEFT JOIN {user} u ON u.id=lit.userid
            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
            GROUP BY lit.courseid,lit.userid $sql_having $sql_order", $params);
    }

    function report112($params)
    {
        $columns = array_merge(array(
            "temp.course",
            "temp.category_name",
            "temp.most_active_day",
            "temp.most_active_time_of_day",
            "temp.time_of_day",
            "temp.sunday_hours",
            "temp.sunday_percent",
            "temp.monday_hours",
            "temp.monday_percent",
            "temp.tuesday_hours",
            "temp.tuesday_percent",
            "temp.wednesday_hours",
            "temp.wednesday_percent",
            "temp.thursday_hours",
            "temp.thursday_percent",
            "temp.friday_hours",
            "temp.friday_percent",
            "temp.saturday_hours",
            "temp.saturday_percent",
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql_filter2 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter3 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter3 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter4 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter4 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter5 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter5 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter6 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter6 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter6 .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT * FROM (SELECT
              CONCAT(lit.id,lid.id) AS id,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,
              popular_day.day AS most_active_day,
              popular_time.active_time_of_day AS most_active_time_of_day,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

              IF(lid.timepoint>=0 && lid.timepoint<6,1,
                IF(lid.timepoint>=6 && lid.timepoint<9,2,
                  IF(lid.timepoint>=9 && lid.timepoint<12,3,
                    IF(lid.timepoint>=12 && lid.timepoint<15,4,
                      IF(lid.timepoint>=15 && lid.timepoint<18,5,
                        IF(lid.timepoint>=18 && lid.timepoint<21,6,
                          IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS time_of_day

            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
              LEFT JOIN (SELECT a.courseid,a.day,a.sum_timespend
                         FROM (
                                SELECT
                                  lit.courseid,
                                  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
                                  SUM(lil.timespend) AS sum_timespend

                                FROM {local_intelliboard_tracking} lit
                                  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                  LEFT JOIN {course} c ON c.id = lit.courseid
                                WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
                                GROUP BY lit.courseid,day
                                HAVING sum_timespend>0
                                ORDER BY sum_timespend DESC
                              ) AS a
                         GROUP BY a.courseid) AS popular_day ON popular_day.courseid=lit.courseid
              LEFT JOIN (SELECT a.courseid,a.active_time_of_day,a.sum_timespend
                         FROM (
                                SELECT
                                  lit.courseid,
                                  IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                        IF(lid.timepoint>=6 && lid.timepoint<9,2,
                                           IF(lid.timepoint>=9 && lid.timepoint<12,3,
                                              IF(lid.timepoint>=12 && lid.timepoint<15,4,
                                                 IF(lid.timepoint>=15 && lid.timepoint<18,5,
                                                    IF(lid.timepoint>=18 && lid.timepoint<21,6,
                                                       IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
                                     SUM(lid.timespend) AS sum_timespend

                                FROM {local_intelliboard_tracking} lit
                                  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
                                  LEFT JOIN {course} c ON c.id = lit.courseid
                                WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
                                GROUP BY lit.courseid,active_time_of_day
                                HAVING sum_timespend>0
                                ORDER BY sum_timespend DESC
                              ) AS a
                         GROUP BY a.courseid) AS popular_time ON popular_time.courseid=lit.courseid

              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category

            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
            GROUP BY lit.courseid, `time_of_day`
            HAVING `time_of_day`>0

            UNION

            SELECT
              lit.id,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,
              popular_day.day AS most_active_day,
              popular_time.active_time_of_day AS most_active_time_of_day,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

              0 AS time_of_day

            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN (SELECT a.courseid,a.day,a.sum_timespend
                         FROM (
                                SELECT
                                  lit.courseid,
                                  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
                                  SUM(lil.timespend) AS sum_timespend

                                FROM {local_intelliboard_tracking} lit
                                  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                  LEFT JOIN {course} c ON c.id = lit.courseid
                                WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
                                GROUP BY lit.courseid,day
                                HAVING sum_timespend>0
                                ORDER BY sum_timespend DESC
                              ) AS a
                         GROUP BY a.courseid) AS popular_day ON popular_day.courseid=lit.courseid

              LEFT JOIN (SELECT a.courseid,a.active_time_of_day,a.sum_timespend
                         FROM (
                                SELECT
                                  lit.courseid,
                                  IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                        IF(lid.timepoint>=6 && lid.timepoint<9,2,
                                           IF(lid.timepoint>=9 && lid.timepoint<12,3,
                                              IF(lid.timepoint>=12 && lid.timepoint<15,4,
                                                 IF(lid.timepoint>=15 && lid.timepoint<18,5,
                                                    IF(lid.timepoint>=18 && lid.timepoint<21,6,
                                                       IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
                                     SUM(lid.timespend) AS sum_timespend

                                FROM {local_intelliboard_tracking} lit
                                  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
                                  LEFT JOIN {course} c ON c.id = lit.courseid
                                WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
                                GROUP BY lit.courseid,active_time_of_day
                                HAVING sum_timespend>0
                                ORDER BY sum_timespend DESC
                              ) AS a
                         GROUP BY a.courseid) AS popular_time ON popular_time.courseid=lit.courseid
              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
            GROUP BY lit.courseid ) AS temp $sql_having $sql_order", $params);
    }

    function report113($params)
    {
        $columns = array_merge(array(
            "temp.firstname",
            "temp.lastname",
            "temp.email",
            "temp.idnumber",
            "temp.course",
            "temp.category_name",
            "temp.most_active_day",
            "temp.most_active_time_of_day",
            "temp.time_of_day",
            "temp.sunday_hours",
            "temp.sunday_percent",
            "temp.monday_hours",
            "temp.monday_percent",
            "temp.tuesday_hours",
            "temp.tuesday_percent",
            "temp.wednesday_hours",
            "temp.wednesday_percent",
            "temp.thursday_hours",
            "temp.thursday_percent",
            "temp.friday_hours",
            "temp.friday_percent",
            "temp.saturday_hours",
            "temp.saturday_percent",
        ), $this->get_filter_columns($params));

        $sql_columns1 = $this->get_columns($params, "u.id");
        $sql_columns2 = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        $sql_filter1 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter1 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter1 .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter1 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter1 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter2 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter2 .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter2 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter3 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter3 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter4 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter4 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter5 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter5 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter6 = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter6 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter6 .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
			SELECT * FROM (SELECT
              CONCAT(lit.id,lid.id) AS id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              u.idnumber,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,
              popular_day.day AS most_active_day,
              popular_time.active_time_of_day AS most_active_time_of_day,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS sunday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

              IF(lid.timepoint>=0 && lid.timepoint<6,1,
                IF(lid.timepoint>=6 && lid.timepoint<9,2,
                  IF(lid.timepoint>=9 && lid.timepoint<12,3,
                    IF(lid.timepoint>=12 && lid.timepoint<15,4,
                      IF(lid.timepoint>=15 && lid.timepoint<18,5,
                        IF(lid.timepoint>=18 && lid.timepoint<21,6,
                          IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS time_of_day
              $sql_columns1

            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
              LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.day,a.sum_timespend,a.userid
                          FROM (
                            SELECT
                              lit.courseid,
                              lit.userid,
                              WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
                              SUM(lil.timespend) AS sum_timespend

                            FROM {local_intelliboard_tracking} lit
                              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                              LEFT JOIN {course} c ON c.id = lit.courseid
                              LEFT JOIN {user} u ON u.id=lit.userid
                            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
                            GROUP BY lit.courseid,day,lit.userid
                            HAVING sum_timespend>0
                            ORDER BY sum_timespend DESC
                               ) AS a
                        GROUP BY a.courseid, a.userid) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid

              LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.active_time_of_day,a.sum_timespend,a.userid
                            FROM (
                                   SELECT
                                     lit.courseid,
                                     lit.userid,
                                     IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                        IF(lid.timepoint>=6 && lid.timepoint<9,2,
                                           IF(lid.timepoint>=9 && lid.timepoint<12,3,
                                              IF(lid.timepoint>=12 && lid.timepoint<15,4,
                                                 IF(lid.timepoint>=15 && lid.timepoint<18,5,
                                                    IF(lid.timepoint>=18 && lid.timepoint<21,6,
                                                       IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
                                     SUM(lid.timespend) AS sum_timespend

                                   FROM {local_intelliboard_tracking} lit
                                     LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                     LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
                                     LEFT JOIN {course} c ON c.id = lit.courseid
                                     LEFT JOIN {user} u ON u.id=lit.userid
                                   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
                                   GROUP BY lit.courseid,active_time_of_day,lit.userid
                                   HAVING sum_timespend>0
                                   ORDER BY sum_timespend DESC
                                 ) AS a
                            GROUP BY a.courseid, a.userid) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid

              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
              LEFT JOIN {user} u ON u.id=lit.userid

            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter1
            GROUP BY lit.courseid,lit.userid,`time_of_day`
            HAVING `time_of_day`>0

            UNION

            SELECT
              lit.id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              u.idnumber,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,
              popular_day.day AS most_active_day,
              popular_time.active_time_of_day AS most_active_time_of_day,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

              SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
              ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

              0 AS time_of_day
              $sql_columns2

            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.day,a.sum_timespend,a.userid
                          FROM (
                            SELECT
                              lit.courseid,
                              lit.userid,
                              WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
                              SUM(lil.timespend) AS sum_timespend

                            FROM {local_intelliboard_tracking} lit
                              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                              LEFT JOIN {course} c ON c.id = lit.courseid
                              LEFT JOIN {user} u ON u.id=lit.userid
                            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
                            GROUP BY lit.courseid,day,lit.userid
                            HAVING sum_timespend>0
                            ORDER BY sum_timespend DESC
                               ) AS a
                        GROUP BY a.courseid, a.userid) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid

              LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.active_time_of_day,a.sum_timespend,a.userid
                            FROM (
                                   SELECT
                                     lit.courseid,
                                     lit.userid,
                                     IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                        IF(lid.timepoint>=6 && lid.timepoint<9,2,
                                           IF(lid.timepoint>=9 && lid.timepoint<12,3,
                                              IF(lid.timepoint>=12 && lid.timepoint<15,4,
                                                 IF(lid.timepoint>=15 && lid.timepoint<18,5,
                                                    IF(lid.timepoint>=18 && lid.timepoint<21,6,
                                                       IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
                                     SUM(lid.timespend) AS sum_timespend

                                   FROM {local_intelliboard_tracking} lit
                                     LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                     LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
                                     LEFT JOIN {course} c ON c.id = lit.courseid
                                     LEFT JOIN {user} u ON u.id=lit.userid
                                   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
                                   GROUP BY lit.courseid,active_time_of_day,lit.userid
                                   HAVING sum_timespend>0
                                   ORDER BY sum_timespend DESC
                                 ) AS a
                            GROUP BY a.courseid, a.userid) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid

              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
              LEFT JOIN {user} u ON u.id=lit.userid

            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
            GROUP BY lit.courseid,lit.userid ) AS temp $sql_having $sql_order", $params);
    }

    function report114($params){
        global $DB;

        $columns = array_merge(array(
            "course_name",
            "course_created",
            "c.startdate",
            "u.firstname",
            "u.lastname",
            "learners",
            "events",
            "completed_modules",
            "post_per_day",
            "visits",
            "assignment",
            "quiz",
            "workshop",
            "added_course_module",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'c.timecreated');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
		$sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles4 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $sql_cm1 = $this->get_filter_module_sql($params, "cm.");
        $sql_cm2 = $this->get_filter_module_sql($params, "cm.");
        $completion = $this->get_completion($params, "cmc.");

        return $this->get_report_data("
			SELECT
				ra.id,
				c.id AS courseid,
				c.fullname AS course_name,
				c.timecreated AS course_created,
				c.startdate,
				u.id AS teacher_id,
				u.lastname,
				u.firstname,
				(SELECT COUNT(distinct ras.userid) FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $learner_roles1 AND con.instanceid = c.id) AS learners,
				(SELECT COUNT(distinct e.id) FROM {event} e, {role_assignments} ras, {context} con WHERE ras.userid = e.userid $learner_roles2 AND con.id = ras.contextid AND con.contextlevel = 50 AND e.courseid = con.instanceid AND con.instanceid = c.id) AS events,
				(SELECT COUNT(DISTINCT fp.id)/(TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(c.timecreated))) FROM {role_assignments} ras, {context} con, {forum_discussions} fd, {forum_posts} fp WHERE con.id = ras.contextid $learner_roles3 AND con.contextlevel = 50 AND fd.course = con.instanceid AND fp.userid = ras.userid AND fp.discussion = fd.id AND con.instanceid = c.id) AS post_per_day,
				(SELECT SUM(lil.visits) / COUNT(DISTINCT timepoint) FROM {role_assignments} ras, {context} con, {local_intelliboard_tracking} lit, {local_intelliboard_logs} lil WHERE con.id = ras.contextid $learner_roles4 AND con.contextlevel = 50 AND con.instanceid = lit.courseid AND ras.userid = lit.userid AND lil.trackid = lit.id AND con.instanceid = c.id) AS visits,
				(SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm WHERE cm.course = c.id $sql_cm1) AS modules,
				(SELECT COUNT(DISTINCT cmc.id) FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql_cm2 WHERE cm.course = c.id) AS completed_modules,
				(SELECT COUNT(id) FROM {assign} WHERE course = c.id) AS assignment,
				(SELECT COUNT(id) FROM {quiz} WHERE course = c.id) AS quiz,
				(SELECT COUNT(id) FROM {workshop} WHERE course = c.id) AS workshop
				$sql_columns
			FROM {role_assignments} ra
				JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
				JOIN {course} c ON c.id= ctx.instanceid
				JOIN {user} u ON u.id=ra.userid
			WHERE ra.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

   function report114_graph($params){
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $teacher_roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra_students.roleid');
        $params->length = -1;

        return $this->get_report_data("
			SELECT
              e.id,
              DATE_FORMAT(DATE(FROM_UNIXTIME(e.timestart)), \"%Y-%m-%d\") AS date,
              COUNT(DISTINCT IF(e.userid=ra_students.userid, e.id, NULL)) AS student_events,
              COUNT(DISTINCT IF(e.userid=ra.userid, e.id, NULL)) AS teacher_events

            FROM {course} c
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id $teacher_roles
              LEFT JOIN {role_assignments} ra_students ON ra_students.contextid = con.id $learner_roles
              LEFT JOIN {event} e ON e.courseid=c.id AND (e.userid = ra_students.userid OR e.userid=ra.userid)
            WHERE e.id IS NOT NULL $sql_filter
            GROUP BY date
            ORDER BY e.timestart", $params);
    }

    function report115($params){
        global $DB, $PAGE;

        $columns = array_merge(array(
            "teacher_photo",
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "c.timecreated",
            "enrolled_students",
            "assignments",
            "needs_grading",
            "avg_time_to_grade",
            "logins_by_teacher",
            "teacher_posts",
            "teacher_posts",
            "teacher_events",
            "added_course_module",
            "modified_course_module",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql_log_timecreated = $this->get_filterdate_sql($params, 'lil.timepoint');
        $sql_fp_created = $this->get_filterdate_sql($params, 'fp.created');
        $sql_fp_created1 = $this->get_filterdate_sql($params, 'fp.created');
        $sql_fp_created2 = $this->get_filterdate_sql($params, 'fp.created');

        $this->params['grade_period'] = strtotime('+7 days');

        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $teacher_roles1 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles2 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles3 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles4 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');

        $data = $this->get_report_data("
			SELECT
                  ra.id,
                  c.id AS courseid,
                  c.fullname AS course_name,
                  c.timecreated AS course_created,
                  u.id AS teacher_id,
                  u.lastname,
                  u.firstname,
                  u.email,
                  u.picture AS teacher_picture,
                  u.firstnamephonetic AS teacher_firstnamephonetic,
                  u.lastnamephonetic AS teacher_lastnamephonetic,
                  u.middlename AS teacher_middlename,
                  u.alternatename AS teacher_alternatename,
                  u.imagealt AS teacher_imagealt,
                  (SELECT COUNT(distinct ras.userid) FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $learner_roles1 AND con.instanceid = c.id) AS enrolled_students,
                  (SELECT COUNT(ass.id) FROM {assign} ass WHERE ass.course = c.id) AS assignments,
                  (SELECT COUNT(DISTINCT sub.id)
                   FROM {assign_submission} sub
                     LEFT JOIN {assign_grades} as_grade ON as_grade.userid = sub.userid AND as_grade.assignment = sub.assignment AND as_grade.attemptnumber = sub.attemptnumber
                     JOIN {assign} ass ON ass.id=sub.assignment
                   WHERE sub.status = 'submitted' AND sub.latest=1 AND sub.timecreated > :grade_period AND (as_grade.grade IS NULL OR as_grade.grade<0) AND ass.course=c.id) AS needs_grading,
                  (SELECT AVG(as_grade.timecreated-sub.timecreated)
                   FROM {assign_submission} sub
                     LEFT JOIN {assign_grades} as_grade ON as_grade.userid = sub.userid AND as_grade.assignment = sub.assignment AND as_grade.attemptnumber = sub.attemptnumber
                     JOIN {assign} ass ON ass.id=sub.assignment
                   WHERE sub.status = 'submitted' AND sub.latest=1 AND as_grade.grade IS NOT NULL AND as_grade.grade>=0 AND ass.course=c.id) AS avg_time_to_grade,
                  (SELECT SUM(lil.visits) / COUNT(DISTINCT timepoint) FROM {local_intelliboard_tracking} lit, {local_intelliboard_logs} lil WHERE lil.trackid = lit.id AND lit.courseid = c.id $sql_log_timecreated) AS logins_by_teacher,
                  (SELECT COUNT(DISTINCT fd.id) FROM {forum_discussions} fd, {forum_posts} fp
                  WHERE fp.discussion=fd.id AND fd.course = c.id $sql_fp_created) AS teacher_discussions,
                  (SELECT COUNT(DISTINCT fp.id) FROM {forum_discussions} fd, {forum_posts} fp
                  WHERE fp.discussion = fd.id AND fd.course = c.id $sql_fp_created1) AS teacher_posts,
                  (SELECT COUNT(DISTINCT fp.id) FROM {role_assignments} ras, {context} con, {forum_discussions} fd, {forum_posts} fp
                  WHERE con.id = ras.contextid $learner_roles2 AND con.contextlevel = 50 AND fp.discussion=fd.id AND fd.course=con.instanceid AND con.instanceid = c.id $sql_fp_created2) AS students_posts,
                  (SELECT COUNT(distinct id) FROM {event} WHERE userid = u.id) AS teacher_events,
                  (SELECT COUNT(distinct e.id) FROM {event} e, {role_assignments} ras, {context} con
                  WHERE ras.userid = e.userid $learner_roles3 AND con.id = ras.contextid AND con.contextlevel = 50 AND e.courseid = con.instanceid AND con.instanceid = c.id) AS students_events,
                  (SELECT COUNT(id) FROM {course_modules} WHERE course = c.id) AS added_course_module,
                  '' AS teacher_photo
                  $sql_columns

                FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                  JOIN {course} c ON c.id= ctx.instanceid
                  JOIN {user} u ON u.id=ra.userid
                WHERE ra.id > 0 $sql_filter $sql_having $sql_order", $params,false);

        foreach($data as &$item){
            $user = new stdClass();
            $user->id = $item->teacher_id;
            $user->lastname = $item->lastname;
            $user->firstname = $item->firstname;
            $user->email = $item->email;
            $user->picture = $item->teacher_picture;
            $user->firstnamephonetic = $item->teacher_firstnamephonetic;
            $user->lastnamephonetic = $item->teacher_lastnamephonetic;
            $user->middlename = $item->teacher_middlename;
            $user->alternatename = $item->teacher_alternatename;
            $user->imagealt = $item->teacher_imagealt;

            $userpicture = new user_picture($user);
            $userpicture->size = 1; // Size f1.
            $item->teacher_photo = $userpicture->get_url($PAGE)->out(false);
        }
        return array('data'=>$data);
    }

    function report116($params){

        $columns = array_merge(array(
            "c.fullname",
            "w.name",
            "cm.added",
            "u.firstname",
            "u.lastname",
            "accessed",
            "log.action",
            "grade"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_single = intelliboard_grade_sql(false, $params);

        if($params->custom == 1){
            $sql_filter .= $this->get_filterdate_sql($params, 'log.timecreated');
        }else{
            $sql_filter .= $this->get_filterdate_sql($params, 'cm.added');
        }
        $sql_join = "";
        if(!empty($params->custom2)){
            $sql = $this->get_filter_in_sql($params->custom2, "ras.roleid");
            $sql .= $this->get_filter_in_sql($params->courseid, "c.id");

			$sql_join .= " JOIN (SELECT DISTINCT ras.userid, con.instanceid FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $sql GROUP BY ras.userid, con.instanceid) ra ON ra.userid = u.id AND ra.instanceid = c.id";
        }

        return $this->get_report_data("
        	SELECT
			  log.id,
			  log.timecreated AS accessed,
			  log.userid,
			  log.action,
			  c.id AS courseid,
			  c.fullname AS course_name,
			  w.name AS wiki_name,
			  cm.added AS timecreated,
			  u.firstname,
			  u.lastname,
			  $grade_single AS grade
			  $sql_columns
			FROM {logstore_standard_log} log
			    JOIN {course_module}s cm ON cm.id = log.contextinstanceid
			    JOIN {wiki} w ON w.id = cm.instance
			    JOIN {user} u ON u.id = log.userid
			    JOIN {course} c ON c.id = log.courseid
			    LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
			    LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
			    $sql_join
			WHERE log.component='mod_wiki' $sql_filter $sql_having $sql_order", $params);
    }

    function report117($params){

        $columns = array_merge(array(
            "c.fullname",
            "w.name",
            "count_students",
            "avg_time_students",
            "avg_time_teachers",
            "percent_viewed",
            "percent_edited",
            "percent_comment",
            "avg_grade"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_avg = intelliboard_grade_sql(true, $params);
        $teacher_roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);

        return $this->get_report_data("
			SELECT
              w.id,
              c.id AS courseid,
              c.fullname AS course_name,
              w.name AS wiki_name,
              COUNT(DISTINCT IF(ra.roleid IN(5), ra.userid, NULL)) AS count_students,
              AVG(IF($learner_roles, lit.timespend, NULL)) AS avg_time_students,
              AVG(IF($teacher_roles, lit.timespend, NULL)) AS avg_time_teachers,

              ROUND((COUNT( DISTINCT IF(log.action='created' AND log.target='comment', log.id, NULL))*100)/COUNT(DISTINCT log.id),2) AS percent_comment,
              ROUND((COUNT( DISTINCT IF(log.action='viewed', log.id, NULL))*100)/COUNT(DISTINCT log.id),2) AS percent_viewed,
              ROUND((COUNT( DISTINCT IF((log.action='created' AND log.target<>'comment') OR log.action='updated' OR log.action='deleted', log.id, NULL))*100)/COUNT(DISTINCT log.id),2) AS percent_edited,
              $grade_avg AS avg_grade
              $sql_columns
            FROM {wiki} w
              LEFT JOIN {course} c ON w.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id

              JOIN {modules} m ON m.name='wiki'
              LEFT JOIN {course_modules} cm ON cm.course=w.course AND cm.instance=w.id AND cm.module=m.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.courseid=c.id AND lit.page='module' AND lit.param=cm.id

              LEFT JOIN {logstore_standard_log} log ON log.courseid=w.course AND log.component='mod_wiki' AND log.contextinstanceid=cm.id

              LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
              LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
            WHERE c.id>1 $sql_filter
            GROUP BY w.id $sql_having $sql_order", $params);
    }

    function report118($params){

        $columns = array_merge(array(
            "c.fullname",
            "ch.name",
            "lil.timepoint",
            "lit.firstaccess",
            "lit.lastaccess",
            "lit.timespend",
            "roles",
            "u.firstname",
            "u.lastname",
            "lit.visits"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'lil.timepoint');

        $data = $this->get_report_data("
            SELECT
              CONCAT(ch.id,u.id, lil.timepoint) AS id,
              c.id AS courseid,
              c.fullname AS course_name,
              ch.name AS chat_name,
              lil.timepoint AS timepoint,
              MIN(log.timecreated) AS timeaccess,
              lil.timespend,
              GROUP_CONCAT(DISTINCT ra.roleid) AS roles,
              u.firstname,
              u.lastname,
              u.id AS userid,
              lil.visits
              $sql_columns

            FROM {chat} ch
              LEFT JOIN {course} c ON ch.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id
              LEFT JOIN {user} u ON u.id=ra.userid

              JOIN {modules} m ON m.name='chat'
              LEFT JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=ch.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.param=cm.id AND lit.page='module'
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id

              LEFT JOIN {logstore_standard_log} log ON log.courseid=c.id AND log.component='mod_chat' AND log.contextinstanceid=cm.id AND log.userid=u.id AND log.timecreated BETWEEN lil.timepoint AND lil.timepoint+86400
            WHERE lil.timepoint IS NOT NULL $sql_filter
            GROUP BY ch.id,u.id,lil.timepoint $sql_having $sql_order", $params, false);

        $roles = role_fix_names(get_all_roles());
        foreach($data as &$item){
            $user_roles = explode(',', $item->roles);
            $item->roles = array();
            foreach($roles as $role){
                if(in_array($role->id, $user_roles)){
                    $item->roles[] = $role->localname;
                }
            }
            $item->roles = implode(', ', $item->roles);
        }

        return array('data'=>$data);
    }

    function report119($params){

        $columns = array(
            "c.fullname",
            "ch.name",
            "lil.timepoint",
            "srudent_timespend",
            "teacher_timespend",
            "students",
            "student_messages",
            "teacher_messages"
        );

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'lil.timepoint');

        $teacher_roles1 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $teacher_roles2 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $teacher_roles3 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles4 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);

        return $this->get_report_data("
            SELECT
              CONCAT(ch.id,lil.timepoint) AS id,
              c.id AS courseid,
              c.fullname AS course_name,
              ch.name AS chat_name,
              lil.timepoint AS timepoint,
              SUM(DISTINCT IF($learner_roles1,lil.timespend + ra.userid, 0)) - SUM(DISTINCT IF($learner_roles2,ra.userid, 0)) AS srudent_timespend,
              SUM(DISTINCT IF($teacher_roles1,lil.timespend + ra.userid, 0)) - SUM(DISTINCT IF($teacher_roles2,ra.userid, 0)) AS teacher_timespend,
              COUNT(DISTINCT IF($learner_roles3,ra.userid, NULL )) AS students,
              COUNT(DISTINCT IF($learner_roles4,chm.id, NULL )) AS student_messages,
              COUNT(DISTINCT IF($teacher_roles3,chm.id, NULL )) AS teacher_messages

            FROM {chat} ch
              LEFT JOIN {course} c ON ch.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id

              JOIN {modules} m ON m.name='chat'
              LEFT JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=ch.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.param=cm.id AND lit.page='module'
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN {chat_messages} chm ON chm.userid=ra.userid AND chm.chatid=ch.id AND chm.timestamp BETWEEN lil.timepoint AND lil.timepoint+86400

            WHERE c.id>1 AND lil.timepoint IS NOT NULL $sql_filter
            GROUP BY ch.id,lil.timepoint $sql_having $sql_order", $params);
    }

	function report120($params)
	{
    	$columns = array_merge(array(
    		"c.fullname",
    		"quizname",
    		"que.id",
    		"que.name",
    		"que.questiontext",
    		"answer",
    		"grade"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");

        return $this->get_report_data("
        	SELECT qua.id,
				c.fullname,
				q.name AS quizname,
				que.id AS queid,
				que.name,
				que.questiontext,
				GROUP_CONCAT(DISTINCT(CONCAT(ans.answer,'-spr-',ROUND(ans.fraction, 0)))) AS answer,
				ROUND(AVG(qas.fraction), 1) AS grade
			FROM {quiz} q
				LEFT JOIN {course} c ON c.id = q.course
				LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id
				LEFT JOIN {question_attempts} qua ON qua.questionusageid = qa.uniqueid
				LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qua.id AND qas.fraction IS NOT NULL
				LEFT JOIN {question} que ON que.id = qua.questionid
				LEFT JOIN {question_answers} ans ON ans.question = que.id
			WHERE que.id IS NOT NULL $sql_filter GROUP BY que.id $sql_having $sql_order", $params);
    }


	function report121($params)
	{
		global $DB;

    	$columns = array_merge(array("question", "scale", "scale", "scale"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter1 = $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter2 = $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter2 .= $this->get_filter_in_sql($params->users, "qa.userid");

        $sql_filter3 = $this->get_filter_in_sql($params->custom, "qid");
        $sql_filter3 .= $this->get_filter_in_sql($params->users, "userid");

		$data = $this->get_report_data("
        	SELECT qq.id, qq.name AS question, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale
	        	FROM
				{questionnaire} q
				JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
				JOIN {questionnaire_attempts} qa ON qa.qid = q.id
				JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
				JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
			WHERE q.id > 0 $sql_filter1
			GROUP BY qq.id $sql_having $sql_order", $params, false);

		$data2 = $this->get_report_data("
        	SELECT qq.id, qq.name AS question, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale
	        	FROM
				{questionnaire} q
				JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
				JOIN {questionnaire_attempts} qa ON qa.qid = q.id
				JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
				JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
			WHERE q.id > 0 $sql_filter2
			GROUP BY qq.id", $params, false);

		$row = $DB->get_record_sql("SELECT MAX(rid) AS attempt FROM {questionnaire_attempts} WHERE id > 0 $sql_filter3",$this->params);
		$attempt = isset($row->attempt) ? intval($row->attempt) : 0;
		$sql_filter2 .= $this->get_filter_in_sql($attempt, "qa.rid");

		$data3 = $this->get_report_data("
        	SELECT qq.id, qq.name AS question, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale,
        		q.name AS questionnaire, u.firstname, u.lastname, u.email
	        	FROM
				{questionnaire} q
				JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
				JOIN {questionnaire_attempts} qa ON qa.qid = q.id
				JOIN {user} u ON u.id = qa.userid
				JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
				JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
			WHERE q.id > 0 $sql_filter2
			GROUP BY qq.id", $params, false);

		if ($data) {
			foreach ($data as $key => $value) {
				$data[$key]->scale2 = isset($data2[$key]->scale) ? intval($data2[$key]->scale) : 0;
				$data[$key]->scale3 = isset($data3[$key]->scale) ? intval($data3[$key]->scale) : 0;
			}
		}
		return array('data'=> $data);
    }
    function report122($params)
	{
    	$columns = array_merge(array(
    		"u.firstname",
    		"u.lastname",
    		"u.idnumber",
    		"u.email",
    		"c.shortname",
    		"grade1",
    		"grade2", ""), $this->get_filter_columns($params));

		if (!$params->custom or !$params->custom2) {
    		return array();
    	}


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $sql_join = "";
        $sql_filter1 = "";
        $sql_filter2 = "";
        $sql_select1 = "'0' AS grade1,";
        $sql_select2 = "'0' AS grade2,";
        if ($params->custom) {
        	$date = explode(",", $params->custom);
        	$params->timestart = $date[0];
        	$params->timefinish = $date[1];
        	if ($params->timestart and $params->timefinish) {
	        	$sql = $this->get_filterdate_sql($params, "g1.timemodified");
	        	$grade = intelliboard_grade_sql(true, $params, 'g1.');
	        	$sql_select1 = "$grade AS grade1,";
	        	$sql_filter1 = "LEFT JOIN {grade_grades_history} g1 ON g1.userid = u.id AND g1.itemid = i.id AND g1.finalgrade IS NOT NULL $sql";
			}
        }
        if ($params->custom2) {
        	$date = explode(",", $params->custom2);
        	$params->timestart = $date[0];
        	$params->timefinish = $date[1];
        	if ($params->timestart and $params->timefinish) {
	        	$sql = $this->get_filterdate_sql($params, "g2.timemodified");
	        	$grade = intelliboard_grade_sql(true, $params, 'g2.');
	        	$sql_select2 = "$grade AS grade2,";
	        	$sql_filter2 = "LEFT JOIN {grade_grades_history} g2 ON g2.userid = u.id AND g2.itemid = i.id AND g2.finalgrade IS NOT NULL $sql";
			}
        }
        if ($sql_filter1 or $sql_filter2) {
        	$sql_join = "JOIN {grade_items} i ON itemtype = 'course' AND i.courseid = c.id";
        }

        return $this->get_report_data("
        	SELECT
	        	MAX(ue.id) AS id,
	        	$sql_select1
	        	$sql_select2
	        	u.id AS userid,
	        	u.firstname,
	        	u.lastname,
	        	u.idnumber,
	        	u.email,
	        	c.id AS courseid,
	        	c.shortname, c.fullname
			FROM {user_enrolments} ue
				JOIN {enrol} e ON e.id = ue.enrolid
				JOIN {course} c ON c.id = e.courseid
				JOIN {user} u ON u.id = ue.userid
				$sql_join
				$sql_filter1
				$sql_filter2
			WHERE u.id > 0 $sql_filter

			GROUP BY c.id, u.id $sql_having $sql_order", $params);
    }
    function report123($params)
	{
    	$columns = array_merge(array(
    		"u.firstname",
    		"u.lastname",
    		"u.idnumber",
    		"u.email",
    		"courses",
    		"grade", ""), $this->get_filter_columns($params));

    	if (!$params->custom or !$params->custom2) {
    		return array();
    	}
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $sql_join = "";
        $sql_filter1 = "";
        $sql_filter2 = "";
        $sql_select1 = "'0' AS grade1,";
        $sql_select2 = "'0' AS grade2,";
        if ($params->custom) {
        	$date = explode(",", $params->custom);
        	$params->timestart = $date[0];
        	$params->timefinish = $date[1];
        	if ($params->timestart and $params->timefinish) {
	        	$sql = $this->get_filterdate_sql($params, "g1.timemodified");
	        	$grade = intelliboard_grade_sql(true, $params, 'g1.');
	        	$sql_select1 = "$grade AS grade1,";
	        	$sql_filter1 = "JOIN {grade_grades_history} g1 ON g1.userid = u.id AND g1.itemid = i.id AND g1.finalgrade IS NOT NULL $sql";
	        }
        }
        if ($params->custom2) {
        	$date = explode(",", $params->custom2);
        	$params->timestart = $date[0];
        	$params->timefinish = $date[1];
        	if ($params->timestart and $params->timefinish) {
	        	$sql = $this->get_filterdate_sql($params, "g2.timemodified");
	        	$grade = intelliboard_grade_sql(true, $params, 'g2.');
	        	$sql_select2 = "$grade AS grade2,";
	        	$sql_filter2 = "JOIN {grade_grades_history} g2 ON g2.userid = u.id AND g2.itemid = i.id AND g2.finalgrade IS NOT NULL $sql";
	        }
        }
        if ($sql_filter1 or $sql_filter2) {
        	$sql_join = "JOIN {grade_items} i ON itemtype = 'course' AND i.courseid = c.id";
        }

        return $this->get_report_data("
        	SELECT
				u.userid,
				u.firstname,
				u.lastname,
				u.idnumber,
				u.email,
				SUM(CASE WHEN (u.grade2 - u.grade1) < 50 THEN 1 ELSE 0 END) AS grade,
				COUNT(u.courseid) AS courses
				FROM (SELECT
			        	MAX(ue.id) AS id,
			        	$sql_select1
			        	$sql_select2
			        	u.firstname,
			        	u.lastname,
			        	u.idnumber,
			        	u.email,
			        	u.id AS userid,
                		c.id AS courseid
					FROM {user_enrolments} ue
						JOIN {enrol} e ON e.id = ue.enrolid
						JOIN {course} c ON c.id = e.courseid
						JOIN {user} u ON u.id = ue.userid
						$sql_join
						$sql_filter1
						$sql_filter2
					WHERE u.id > 0 $sql_filter
					GROUP BY c.id, u.id) u
            GROUP BY u.userid $sql_having $sql_order", $params);
    }
    function report124($params)
	{
    	$columns = array_merge(array(
    		"u.firstname",
    		"u.lastname",
    		"u.email",
    		"gr.groups",
    		"c.fullname",
    		"s.name"), $this->get_filter_columns($params));

    	$sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "s.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "a.time");

        return $this->get_report_data("
        	SELECT
				MAX(a.id) AS id,
				u.firstname,
				u.lastname,
				u.email,
				s.name AS survey,
				c.fullname,
				gr.groups,
				GROUP_CONCAT(CONCAT(a.question, '|', a.answer1)) AS answers
				$sql_columns
			FROM {survey_answers} a
			JOIN {survey} s ON s.id = a.survey
			JOIN {user} u ON u.id = a.userid
			JOIN {course} c ON c.id = s.course
			LEFT JOIN (SELECT m.userid, g.courseid, GROUP_CONCAT(DISTINCT g.name) AS groups FROM {groups} g, {groups_members} m WHERE m.groupid = g.id GROUP BY m.userid, g.courseid) gr ON gr.userid = u.id AND gr.courseid = c.id
			WHERE a.id > 0 $sql_filter
			GROUP BY u.id, s.id $sql_having $sql_order", $params);
    }
    function report125($params)
	{
		global $DB;

    	$columns = array_merge(array(
    		"u.firstname",
    		"u.lastname",
    		"u.email",
    		"gr.groups",
    		"c.fullname",
    		"q.questionnairename"), $this->get_filter_columns($params));

    	$sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "r.submitted");
        $sql = $this->get_filter_in_sql($params->custom, "q.id");

        //increase limit for this session
		$DB->execute("SET SESSION group_concat_max_len = 1000000");

        return $this->get_report_data("SELECT
        	r.id,
			u.firstname,
			u.lastname,
			u.email,
			c.fullname,
			q.questionnaire,
			q.questionnairename,
			gr.groups,
			GROUP_CONCAT(CONCAT ( q.question, 'intelli_sep_q', CASE WHEN q.response_table = 'response_text' THEN (SELECT a.response FROM {questionnaire_response_text} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
				CASE WHEN q.response_table = 'response_bool' THEN (SELECT a.choice_id FROM {questionnaire_response_bool} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
				CASE WHEN q.response_table = 'resp_single' THEN (SELECT h.content FROM {questionnaire_resp_single} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
					CASE WHEN q.response_table = 'response_rank' THEN (SELECT GROUP_CONCAT(CONCAT (h.content, ' - ', (a.rank + 1)) SEPARATOR 'intelli_sep_a') FROM {questionnaire_response_rank} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
					CASE WHEN q.response_table = 'resp_multiple' THEN (SELECT GROUP_CONCAT(h.content SEPARATOR 'intelli_sep_a') FROM {questionnaire_resp_multiple} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE '-' END
						END
					END
				END
			END) SEPARATOR 'intelli_sep_m') AS answers
			$sql_columns
			FROM (SELECT q.id AS questionnaire, q.name AS questionnairename, q.course, qq.id AS question, t.has_choices, t.response_table  FROM {questionnaire} q, {questionnaire_question} qq, {questionnaire_question_type} t WHERE q.id = qq.survey_id AND qq.deleted = 'n' AND qq.type_id = t.typeid $sql ORDER BY qq.position) q
				LEFT JOIN {questionnaire_response} r ON r.survey_id = q.questionnaire
				JOIN {user} u ON u.id = r.username
				JOIN {course} c ON c.id = q.course
				LEFT JOIN (SELECT m.userid, g.courseid, GROUP_CONCAT(DISTINCT g.name) AS groups FROM {groups} g, {groups_members} m WHERE m.groupid = g.id GROUP BY m.userid, g.courseid) gr ON gr.userid = u.id AND gr.courseid = c.id
				WHERE r.complete = 'y' $sql_filter
		GROUP BY u.id, r.id $sql_having $sql_order", $params);
    }
     public function report126($params)
    {
        $columns = array_merge( array(
        					"u.firstname",
        					"u.lastname",
        					"u.email",
        					"c.fullname",
        					"e.cost",
        					"e.currency",
        					"p.payment_status",
        					"p.timeupdated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "p.timeupdated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        return $this->get_report_data("
			SELECT p.id,
				c.fullname,
				p.timeupdated,
				p.payment_status,
				u.firstname,
				u.lastname,
				u.email,
				e.enrol,
				e.cost,
				e.currency
				$sql_columns
			FROM
				{enrol_paypal} p,
				{enrol} e,
				{user} u,
				{course} c
			WHERE e.courseid = c.id AND p.instanceid = e.id AND u.id = p.userid $sql_filter $sql_having $sql_order", $params);
    }
    public function report127($params)
    {
        $columns = array_merge( array(
        					"u.firstname",
        					"u.lastname",
        					"u.email",
        					"s.item_name",
        					"courses",
        					"s.amount",
        					"s.payment_status",
        					"s.timeupdated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "s.timeupdated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
        	SELECT s.id,
        	s.payment_status,
        	s.userid,
        	s.item_name,
        	s.amount,
        	s.timeupdated,
        	u.firstname,
        	u.lastname,
        	u.email,
        	GROUP_CONCAT(DISTINCT c.fullname) AS courses
        	$sql_columns
        	FROM {local_shoppingcart_checkout} s
			    JOIN {local_shoppingcart_relations} r ON r.type = 'course' AND r.productid IN (s.items)
			    JOIN {course} c ON c.id = r.instanceid
			    JOIN {user} u ON u.id = s.userid
			WHERE u.id > 0 $sql_filter $sql_having $sql_order
    		GROUP BY s.id", $params);
    }
    function report128($params)
    {
        global $CFG;
        require($CFG->libdir.'/gradelib.php');

        $columns = array("u.firstname","u.lastname","u.idnumber");
        $courses = explode(',', $params->courseid);
        $items = array();
        foreach($courses as $course){
            $items += (array)grade_item::fetch_all(array('itemtype' => 'category', 'courseid' => $course)) + (array)grade_item::fetch_all(array('itemtype' => 'course', 'courseid' => $course));
        }

        $items_sql = '';
        $unique_items = array();
        foreach($items as $item){
            if(!$item){
                continue;
            }
            $items_sql .= "(SELECT gg.finalgrade FROM {grade_grades} gg LEFT JOIN {grade_items} gi ON gi.id = gg.itemid WHERE gg.userid=u.id AND gi.courseid=c.id AND gg.itemid=$item->id) AS grade_".$item->itemtype."_$item->id,";
            $unique_items[$item->get_name(true)] = "grade_".$item->itemtype."_".$item->id;
        }
        foreach($unique_items as $unique_item){
            $columns[] = $unique_item;
        }
        $columns[] = 'c.fullname';

        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);


        $data = $this->get_report_data("SELECT CONCAT(u.id,c.id) AS uniqueid,
                              u.id,
                              u.firstname,
                              u.lastname,
                              u.idnumber,
                              c.id AS courseid,
                              $items_sql
                              c.fullname
                            FROM {course} c
                              JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
                              JOIN {role_assignments} ra ON ra.contextid = con.id
                              JOIN {user} u ON u.id=ra.userid
                            WHERE c.id>0 $sql_filter $sql_having $sql_order",$params,false);

        foreach ($data as &$record) {
            $array = $record;
            foreach($array as $item=>$value){
                $id = (int)str_replace ( array('grade_category_','grade_course_') , '' , $item );
                if ($id > 0 && !empty($value)) {
                    $grade_grade = grade_grade::fetch(array('itemid'=>$id,'userid'=>$record->id));

                    $name = $items[$id]->get_name(true);
                    $record->{$name} = grade_format_gradevalue($value,$items[$id],true,$CFG->grade_displaytype,$CFG->grade_decimalpoints);
                    $record->{$name} .= ' of '.grade_format_gradevalue($grade_grade->get_grade_max(), $grade_grade->grade_item, true,$CFG->grade_displaytype,$CFG->grade_decimalpoints);
                }
            }
        }

        return array('data'=>$data);
    }

    public function report129($params)
    {
        $columns = array_merge( array(
        					"registrar",
        					"course",
        					"activities_completed",
        					"total_activities",
        					"module_grade",
        					"module_max_grade",
        					"program_grade"), $this->get_filter_columns($params));

        if (!$params->custom or !$params->custom2) {
        	return [];
        }
        $sql_columns = $this->get_columns($params, "u.id");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, "c.id", "courses");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->custom,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'u.id');

        return $this->get_report_data("
        	SELECT u.id,
			  CONCAT(REG.firstname, ' ', REG.lastname) as registrar,
			  c.fullname AS course,
			  IFNULL((SELECT COUNT(gg.finalgrade) FROM {grade_grades} AS gg
			  JOIN {grade_items} AS gi ON gg.itemid = gi.id WHERE gi.courseid = c.id
			  AND gg.userid=REG.id AND gi.itemtype='mod' GROUP BY gg.userid,gi.courseid), 0) AS activities_completed,
			  IFNULL((SELECT COUNT(gi.itemname) FROM {grade_items} AS gi WHERE gi.courseid = c.id AND gi.itemtype='mod'), 0) AS total_activities,
			  IFNULL(gc.grade,0) AS module_grade,
			  ROUND(gc_category_max.grademax,0) as module_max_grade,
			  MIN(t_category.grade) as program_grade
			FROM {user} u
			  JOIN {role_assignments} ra ON ra.userid = u.id
			  JOIN {context} ctx ON ctx.id = ra.contextid
			  JOIN {role} R on ra.roleid = R.id
			  JOIN {user} REG on ctx.instanceid = REG.id
			  JOIN {user_enrolments} ue on ue.userid = REG.id
			  JOIN {enrol} e ON e.id = ue.enrolid
			  JOIN {course} c ON c.id = e.courseid
			  LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
			  LEFT JOIN {context} REGctx ON c.id = REGctx.instanceid
			  LEFT JOIN {role_assignments} AS REGlra ON REGlra.contextid = REGctx.id and u.id = REGlra.userid AND REGlra.roleid=5
			  LEFT JOIN (SELECT course, COUNT(id) AS cmnums FROM {course_modules} WHERE visible = 1 GROUP BY course) AS cm ON cm.course = c.id
			  LEFT JOIN (SELECT course, COUNT(id) AS cmnumx FROM {course_modules} WHERE visible = 1 AND completion = 1 GROUP BY course) cmx ON cmx.course = c.id
			  LEFT JOIN (SELECT  course, COUNT(id) AS cmnuma FROM {course_modules} WHERE visible = 1 AND module = 1 GROUP BY course) cma ON cma.course = c.id
			  LEFT JOIN (SELECT  cm.course,  cmc.userid,  COUNT(cmc.id) AS cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = REG.id
			  LEFT JOIN ( SELECT cm.course,  cmc.userid,  COUNT(cmc.id) AS cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE  cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY  cm.course, cmc.userid) AS cmca ON cmca.course = c.id AND cmca.userid = REG.id
			  LEFT JOIN (SELECT gi.courseid, g.userid, ROUND(((SUM(g.finalgrade) / SUM(g.rawgrademax)) * 100), 2) AS grade
			  FROM {grade_items} gi, {grade_grades} g
			  WHERE  gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
			  GROUP BY gi.courseid, g.userid) AS gc ON gc.courseid = c.id AND gc.userid = REG.id

			  LEFT JOIN (SELECT gi.courseid, SUM(gi.grademax) grademax FROM {grade_items} gi WHERE gi.itemtype = 'course' GROUP BY gi.courseid) AS gc_category_max ON gc_category_max.courseid = c.id

			  JOIN (SELECT px.category, g.userid, ROUND(((SUM(g.finalgrade))), 1) AS grade FROM {grade_items} gi, {grade_grades} g, {course} px
			  WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = px.id AND g.finalgrade IS NOT NULL GROUP BY px.category, g.userid) AS t_category ON c.category = t_category.category AND REG.id = t_category.userid

			WHERE ctx.contextlevel = 30 $sql_filter $sql_having $sql_order
			GROUP BY REGlra.roleid,REG.email, REG.ID , c.id, c.fullname,e.roleid , ue.id, ue.userid, u.email, cma.cmnuma, c.fullname, u.firstname, u.lastname,
			cmca.cmcnuma, c.id, c.category, gc_category_max.grademax", $params);
    }

    function get_course_grade_categories($params)
    {
        global $CFG;
        require($CFG->libdir.'/gradelib.php');
        $display_types = array(GRADE_DISPLAY_TYPE_REAL => new lang_string('real', 'grades'),
            GRADE_DISPLAY_TYPE_PERCENTAGE => new lang_string('percentage', 'grades'),
            GRADE_DISPLAY_TYPE_LETTER => new lang_string('letter', 'grades'),
            GRADE_DISPLAY_TYPE_REAL_PERCENTAGE => new lang_string('realpercentage', 'grades'),
            GRADE_DISPLAY_TYPE_REAL_LETTER => new lang_string('realletter', 'grades'),
            GRADE_DISPLAY_TYPE_LETTER_REAL => new lang_string('letterreal', 'grades'),
            GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => new lang_string('letterpercentage', 'grades'),
            GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => new lang_string('percentageletter', 'grades'),
            GRADE_DISPLAY_TYPE_PERCENTAGE_REAL => new lang_string('percentagereal', 'grades')
        );

        $courses = explode(',', $params->courseid);
        $items = array();
        foreach($courses as $course){
            $items += (array)grade_item::fetch_all(array('itemtype' => 'category', 'courseid' => $course));
        }
        foreach($courses as $course){
            $items += (array)grade_item::fetch_all(array('itemtype' => 'course', 'courseid' => $course));
        }


        $names = array();
        $ranges = array();
        foreach($items as $item){
            if(!$item){
                continue;
            }

            $column = new stdClass();
            $column->name = $item->get_name(true);
            $column->extra = $display_types[$CFG->grade_displaytype];

            $names[$column->name] = html_to_text(get_string('gradeexportcolumntype', 'grades', $column), 0, false);
            $ranges[$column->name] = $item->get_formatted_range($CFG->grade_displaytype, $CFG->grade_decimalpoints);
        }

        return array('data'=>$names,'ranges'=>$ranges);

    }

    function get_data_question_answers($params)
    {
        $sql_filter = $this->get_filter_in_sql($params->filter,'dc.fieldid', false);

        return $this->get_report_data("
            SELECT
              dc.id,
              dc.content,
              COUNT(1) AS count_answers

            FROM {data_content} dc
            WHERE $sql_filter
            GROUP BY dc.content", $params, false);

    }
    function get_course_databases($params)
    {
        global $DB;

        $sql = "";
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'d.course',false);
        }
        return $DB->get_records_sql("
                  SELECT
                      d.id,
                      d.name,
                      c.fullname as course,
                      c.id as courseid
                  FROM {data} d
                    LEFT JOIN {course} c ON c.id=d.course
                  $sql", $this->params);
    }
    function get_databases_question($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->custom3,'df.dataid');
        $sql .= $this->get_filter_in_sql($params->custom2,'df.id');
        return $DB->get_records_sql("
                SELECT
                  df.id,
                  df.name,
                  d.name as data_name,
                  d.id as data_id
                FROM {data_fields} df
                  LEFT JOIN {data} d ON d.id=df.dataid
                WHERE 1 $sql", $this->params);
    }
    function get_history_items($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->courseid, 'courseid');

        return $this->get_report_data("SELECT id, oldid, courseid, itemtype FROM {grade_items_history} WHERE id > 0 $sql", $params);
    }
    function get_history_grades($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->custom, 'itemid');

        return $this->get_report_data("SELECT id, oldid, timemodified, itemid, userid, finalgrade FROM {grade_grades_history} WHERE id > 0 $sql", $params);
    }
    function get_competency($params)
    {
        global $DB;
        return $DB->get_records('competency',array(),'sortorder ASC','id,shortname');
    }
    function get_competency_templates($params)
    {
        global $DB;
        return $DB->get_records('competency_template',array('visible'=>1),'shortname ASC','id,shortname');
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
                  WHERE log.id > 0 $where_sql
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

        if(empty($sql_enabled)){
            return array("data" => array(), 'user' => array());
        }

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
            $grade_avg_sql = intelliboard_grade_sql(true,$params);
            $enabled_tracking = false;
            foreach($fields as $field){
                if($field == 'average_grade'){
                    $join_sql .= " JOIN {grade_items} gi ON gi.itemtype = 'course'
                                      LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id ";
                    $select_sql .= " $grade_avg_sql AS average_grade, ";
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

            $grade_single_sql = intelliboard_grade_sql(false,$params);
            $grades = $DB->get_records_sql("
                        SELECT g.id,
                               q.id AS quiz_id,
                               q.name AS quiz_name,
                               ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0) AS gradepass,
                               COUNT(DISTINCT g.userid) AS users,
                               $grade_single_sql AS grade
                        FROM {quiz} q
                           LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                           LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid != 2 AND g.rawgrade IS NOT NULL
                        WHERE g.rawgrade IS NOT NULL $where
                        GROUP BY $grade_single_sql,quiz_id
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
                $grade_single_sql = intelliboard_grade_sql(false,$params);
                $sql = "SELECT ue.id,
                               $grade_single_sql AS grade,
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
        $size = $this->count_records($sql, 'questionid', $this->params);

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

        $grade_single = intelliboard_grade_sql(false, $params);
		$grade_avg = intelliboard_grade_sql(true, $params);

        $score = $DB->get_record_sql("
                    SELECT
                      (SELECT $grade_avg
                          FROM {grade_items} gi, {grade_grades} g
                          WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = :courseid
                      ) AS avg,
                      (SELECT $grade_single
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

        $grade_single_sql = intelliboard_grade_sql(false,$params);
        $grade_range = $DB->get_records_sql("
                         SELECT CONCAT(10*FLOOR($grade_single_sql/10),
                                         '-',
                                         10*FLOOR($grade_single_sql/10) + 10,
                                         '%'
                                  ) as `range`,
                                COUNT(DISTINCT g.userid) AS users
                         FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {grade_items} gi ON gi.courseid=c.instanceid AND gi.itemtype='course'
                            LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid=ra.userid
                         WHERE c.contextlevel=50 $sql_enabled_courseid AND g.rawgrademax IS NOT NULL
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
        $grade_single_sql = intelliboard_grade_sql(false,$params);
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
            $where[] = "$grade_single_sql BETWEEN :grade_min AND :grade_max";
            $this->params['grade_min'] = $grades[0];
            $this->params['grade_max'] = $grades[1]-0.001;
        }
        if(isset($custom['user_status']) && !empty($custom['user_status'])){
            $custom['user_status'] = clean_param($custom['user_status'],PARAM_INT);
            if($custom['user_status'] == 1){
                $where[] = "($grade_single_sql>0 AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
            }elseif($custom['user_status'] == 2){
                $where[] = "cc.timecompleted>0";
            }elseif($custom['user_status'] == 3){
                $where[] = "(cc.timestarted>0 AND ($grade_single_sql=0 OR g.finalgrade IS NULL) AND (cc.timecompleted=0 OR cc.timecompleted IS NULL))";
            }
        }
        if(!empty($where))
            $where_str = " AND ".implode(' AND ',$where);

        $where_sql = "WHERE u.id IS NOT NULL ".$where_str;

        $sql = "SELECT ue.id,
                       ue.timecreated AS enrolled,
                       $grade_single_sql AS grade,
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
        $grade_avg_sql = intelliboard_grade_sql(true,$params);

        $sql = "SELECT ue.id,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.cohortid,
                       coh.name AS cohortname,
                       $grade_avg_sql AS avg_grade,
                       COUNT(DISTINCT IF(cr.completion IS NOT NULL AND cc.timecompleted>0,cc.id,NULL )) AS learners_completed,
                       COUNT(DISTINCT IF(cr.completion IS NOT NULL AND (cc.timecompleted=0 OR cc.timecompleted IS NULL),cc.id,NULL)) AS learners_not_completed,
                       COUNT(DISTINCT IF(cr.completion IS NOT NULL AND cc.timecompleted>:custom ,cc.id,NULL)) AS learners_overdue,
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
                WHERE u.id>0 $sql_filter
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
        $grade_single_sql = intelliboard_grade_sql(false,$params);

        $sql = "SELECT ue.id,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.cohortid,
                       coh.name AS cohortname,
                       $grade_single_sql AS grade,
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
                WHERE cr.completion IS NOT NULL $sql_where $sql_filter
                GROUP BY u.id $sql_order $sql_limit";


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
                      LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
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
                      LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
                      LEFT JOIN {role_assignments} ra ON ra.userid=cmc.userid AND ra.contextid=:contextid $sql_enabled_learner_roles
                      LEFT JOIN (SELECT cm.section,cm.module,cmc.userid, COUNT(DISTINCT cmc.id) AS completed
                                 FROM {course_modules} cm
                                    LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
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
        $grade_avg = intelliboard_grade_sql(true, $params);

        $avg = $DB->get_record_sql('SELECT
                                  (SELECT '.$grade_avg.' AS grade
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
    public function get_course_questionnaire($params){
        global $DB;

        $sql = "";
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'course',false);
        }
        return $DB->get_records_sql("SELECT id, name FROM {questionnaire} $sql", $this->params);
    }
    public function get_course_survey($params){
        global $DB;

        $sql = "";
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'course',false);
        }
        return $DB->get_records_sql("SELECT id, name FROM {survey} $sql", $this->params);
    }
    public function get_course_survey_questions($params){
        global $DB;

        $sql =  $this->get_filter_in_sql($params->custom, 'a.survey');
        return $DB->get_records_sql("SELECT DISTINCT q.id, q.text, q.shorttext FROM {survey_answers} a, {survey_questions} q WHERE q.id = a.question $sql", $this->params);
    }
    public function get_course_questionnaire_questions($params){
        global $DB;

        $sql =  $this->get_filter_in_sql($params->custom, 'survey_id');
        return $DB->get_records_sql("SELECT id, name FROM {questionnaire_question} WHERE deleted = 'n' $sql ORDER BY position", $this->params);
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
            $grade_single = intelliboard_grade_sql(false, $params);

            $data['grades'] = $DB->get_records_sql("
                        SELECT g.id,
                               u.id AS uid,
                               CONCAT( u.firstname, ' ', u.lastname ) AS name,
                               u.email, u.username,
                               cx.id AS context,
                               $grade_single AS grade,
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

	public function get_live_info($params)
    {
        global $DB;


        for($i = 0; $i < 8; $i++){
        	$this->params["ts$i"] = strtotime('today');
        }
        $this->params["to1"] = strtotime('-10 minutes');
        $this->params["to2"] = strtotime('-10 minutes');
        $this->params["to3"] = strtotime('-10 minutes');
        $filter_completion = $this->get_completion($params, "");
        $filter_teacher = $this->get_filter_in_sql($params->teacher_roles, "roleid");
        $filter_learner = $this->get_filter_in_sql($params->learner_roles, 'roleid');

        $data = array();
        $data['activity'] = $DB->get_records_sql("
        	SELECT d.timepoint, SUM(d.visits) AS visits
        	FROM
        		{local_intelliboard_logs} l,
        		{local_intelliboard_details} d
        	WHERE d.logid = l.id AND l.timepoint >= :ts1
        	GROUP BY d.timepoint", $this->params);

        $data['info'] = $DB->get_record_sql("
        	SELECT
				(SELECT COUNT(id) FROM {course_completions} WHERE timecompleted >= :ts2) AS completions,
				(SELECT COUNT(id) FROM {course_modules_completion} WHERE timemodified >= :ts4 $filter_completion) AS completed_activities,
				(SELECT COUNT(id) FROM {user} WHERE timecreated >= :ts5) AS users,
				(SELECT COUNT(id) FROM {user_enrolments} WHERE timecreated >= :ts6) AS enrolments,
				(SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {local_intelliboard_tracking} t WHERE r.userid = t.userid AND t.lastaccess >= :to1 $filter_learner) AS learners,
				(SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {local_intelliboard_tracking} t WHERE r.userid = t.userid AND t.lastaccess >= :to2 $filter_teacher) AS teachers,
				(SELECT COUNT(DISTINCT userid) FROM {local_intelliboard_tracking} WHERE lastaccess >= :to3) AS online,
				t.sessions,
				t.timespend,
				t.visits
			FROM (SELECT SUM(sessions) AS sessions, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_totals} WHERE timepoint >= :ts7) t", $this->params);


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

        return $DB->get_record_sql("
        	SELECT
	            (SELECT COUNT(*) FROM {user} WHERE id > 0 $sql2) AS users,
	            (SELECT COUNT(*) FROM {course} WHERE category > 0 $sql3) AS courses,
	            (SELECT COUNT(*) FROM {course_modules} WHERE id > 0 $sql4) AS modules,
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
        $completion = $this->get_completion($params, "", false);

        $data = $DB->get_record_sql("
        	SELECT
				(SELECT COUNT(*) FROM {course_completions} WHERE timecompleted > 0 $sql1) AS graduates,
				(SELECT COUNT(*) FROM {course_modules} WHERE visible = 1 $sql1) AS modules,
				(SELECT COUNT(*) FROM {course} WHERE visible = 1 AND category > 0 $sql2) AS visible,
				(SELECT COUNT(*) FROM {course} WHERE visible = 0 AND category > 0 $sql2) AS hidden,
				(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE status = 1 $sql4) AS expired,
				(SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE $sql_learner_roles $sql4) AS students,
				(SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE $sql_teacher_roles $sql4) AS tutors,
				(SELECT COUNT(*) FROM {course_modules_completion} WHERE $completion $sql4) AS completed,
				(SELECT COUNT(DISTINCT (param)) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql4) AS reviewed,
				(SELECT COUNT(cm.id) FROM {course_modules} cm, {modules} m WHERE m.name = 'certificate' AND cm.module = m.id $sql3) AS certificates
				", $this->params);
        if ($data->certificates) {
        	$data->certificates_issued = $DB->count_records('certificate_issues');
        }
        return $data;
    }

    public function get_system_load($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        return $DB->get_record_sql("
        	SELECT
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) AS sitetimespend,
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) AS coursetimespend,
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) AS activitytimespend,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE id > 0 $sql) AS sitevisits,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql) AS coursevisits,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql) AS activityvisits", $this->params);
    }

    public function get_module_visits($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "lit.userid", "users");
        $sql .= $this->get_filter_module_sql($params, "cm.");

        return $DB->get_records_sql("
        	SELECT m.id, m.name, SUM(lit.visits) AS visits
			FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
			WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module $sql
			GROUP BY m.id", $this->params);
    }
    public function get_useragents($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        return $DB->get_records_sql("
        	SELECT useragent AS name, COUNT(id) AS amount
			FROM {local_intelliboard_tracking}
			WHERE useragent != '' $sql
			GROUP BY useragent", $this->params);
    }
    public function get_useros($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        return $DB->get_records_sql("
        	SELECT useros AS name, COUNT(id) AS amount
			FROM {local_intelliboard_tracking}
			WHERE useros != '' $sql
			GROUP BY useros", $this->params);
    }
    public function get_userlang($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "userid", "users");

        return $DB->get_records_sql("
        	SELECT userlang AS name, COUNT(id) AS amount
			FROM {local_intelliboard_tracking}
			WHERE userlang != '' $sql
			GROUP BY userlang", $this->params);
    }

    //update
    public function get_module_timespend($params){
        global $DB;

        $sql0 = $this->get_teacher_sql($params, "userid", "users");
        $sql = $this->get_teacher_sql($params, "lit.userid", "users");
        $sql .= $this->get_filter_module_sql($params, "cm.");

        return $DB->get_records_sql("
        	SELECT m.id, m.name,(SUM(lit.timespend) / (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql0)*100) AS timeval, SUM(lit.timespend) AS timespend
			FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
			WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module $sql
			GROUP BY m.id", $this->params);
    }

    public function get_users_count($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "id", "users");
        $sql .= $this->get_filter_user_sql($params, "");

        return $DB->get_records_sql("
        	SELECT auth, COUNT(*) AS users
			FROM {user}
			WHERE id > 0 $sql
			GROUP BY auth", $this->params);
    }
    public function get_most_visited_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "l.courseid", "courses");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql_cols = "";
        if ($params->filter) {
        	$grade_avg = intelliboard_grade_sql(true, $params);
        	$sql_cols = ", sum(l.timespend) AS timespend, (SELECT $grade_avg
					FROM {grade_items} gi, {grade_grades} g
					WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid = c.id) as grade";
        }

        return $DB->get_records_sql("
        	SELECT c.id,
                c.fullname,
                sum(l.visits) AS visits
                $sql_cols
            FROM {local_intelliboard_tracking} l
                LEFT JOIN {course} c ON c.id = l.courseid
            GROUP BY c.id $sql_order", $this->params, 0, 100);
    }
    public function get_no_visited_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql2 = $this->get_filterdate_sql($params, "lastaccess");

        return $DB->get_records_sql("
        	SELECT c.id,
                c.fullname,
                c.timecreated
	         FROM  {course} c
	         WHERE c.category > 0 AND c.id NOT IN (
	            SELECT courseid FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql2 GROUP BY courseid) $sql", $this->params, 0, 10);
    }
    public function get_active_users($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "u.id", "users");
        $sql .= $this->get_filter_user_sql($params, "u.");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql .= $this->get_filter_enrol_sql($params, "ue.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");

        return $DB->get_records_sql("
        	SELECT u.id,
				CONCAT(u.firstname, ' ', u.lastname) AS name,
				u.lastaccess,
				COUNT(DISTINCT e.courseid) AS courses,
				lit.timespend, lit.visits
			FROM {user} u
				LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
				LEFT JOIN {enrol} e ON e.id = ue.enrolid
				LEFT JOIN {course} c ON c.id = e.courseid
				LEFT JOIN (SELECT userid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY userid) lit ON lit.userid = u.id
			WHERE lit.visits > 0 $sql
			GROUP BY u.id, lit.timespend, lit.visits ORDER BY lit.visits DESC", $this->params, 0, 10);
    }

    public function get_visits_perweek($params){
    	global $DB;

        return $DB->get_records_sql("SELECT @x:=@x+1 as id, MONTH(FROM_UNIXTIME(l.timepoint)) AS monthpoint, FLOOR(((DAY(FROM_UNIXTIME(l.timepoint)) - 1) / 7) + 1) AS weekpoint, SUM(l.sessions) AS sessions, SUM(l.visits) AS visits
			FROM (SELECT @x:= 0) AS x, {local_intelliboard_totals} l
        	GROUP BY monthpoint, weekpoint", $this->params);
    }
    public function get_visits_perday($params){
    	global $DB;

        return $DB->get_records_sql("SELECT @x:=@x+1 as id, WEEKDAY(FROM_UNIXTIME(l.timepoint)) as daypoint, d.timepoint as hourpoint, SUM(d.visits) AS visits
        	FROM (SELECT @x:= 0) AS x, {local_intelliboard_logs} l, {local_intelliboard_details} d
        	WHERE d.logid = l.id
        	GROUP BY daypoint, hourpoint", $this->params);
    }


    public function get_enrollments_per_course($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");
        $sql_ue = $this->get_filter_enrol_sql($params, "ue.");
        $sql_ue .= $this->get_filterdate_sql($params, "ue.timemodified");
        $sql_cc = $this->get_filterdate_sql($params, "cc.timecompleted");

        return $DB->get_records_sql("
        	SELECT c.id,
				c.fullname,
				COUNT(DISTINCT ue.userid ) AS enrolled,
				COUNT(DISTINCT cc.userid ) AS completed
			FROM {course} c
				LEFT JOIN {enrol} e ON e.courseid = c.id
				LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id $sql_ue
				LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid AND cc.timecompleted > 0 $sql_cc
			WHERE c.id > 0 $sql
			GROUP BY c.id", $this->params, 0, 100); // maximum
    }
    public function get_size_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "c.id", "courses");

        if($params->sizemode){
            return null;
        }else{
            return $DB->get_records_sql("
            	SELECT c.id,
                        c.timecreated,
                        c.fullname,
                        fs.coursesize,
                        fm.modulessize
                FROM {course} c
                    LEFT JOIN (SELECT c.instanceid AS course, SUM( f.filesize ) AS coursesize FROM {files} f, {context} c WHERE c.id = f.contextid AND c.contextlevel = 50 GROUP BY c.instanceid) fs ON fs.course = c.id
                    LEFT JOIN (SELECT cm.course, SUM( f.filesize ) AS modulessize FROM {course_modules} cm, {files} f, {context} ctx WHERE ctx.id = f.contextid AND ctx.instanceid = cm.id AND ctx.contextlevel = 70 GROUP BY cm.course) fm ON fm.course = c.id
                WHERE c.category > 0 $sql", $this->params, 0, 20);
        }

    }
    public function get_active_ip_users($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, "u.id", "users");

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        return $DB->get_records_sql("
        	SELECT CONCAT(u.id, '', l.userip) AS id, u.id AS userid,
                l.userip,
                u.lastaccess AS time,
                SUM(l.visits) AS visits,
                CONCAT( u.firstname, ' ', u.lastname ) AS name
            FROM {local_intelliboard_tracking} l,  {user} u
            WHERE u.id = l.userid AND l.lastaccess BETWEEN :timestart AND :timefinish $sql
            GROUP BY u.id, l.userip
            ORDER BY visits DESC", $this->params, 0, 10);
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
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepointval, SUM(courses) AS courses
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY timepointval", $this->params);
        $response = array();
        foreach($data as $item){
            $response[] = $item->timepointval.'.'.$item->courses;
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
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepointval, SUM(sessions) AS users
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY timepointval", $this->params);
        $response = array();
        foreach($data as $item){
            $response[] = $item->timepointval.'.'.$item->users;
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
        $data = $DB->get_records_sql("SELECT FLOOR(timecreated / $ext) * $ext AS timeval, COUNT(id) AS courses
                                      FROM {course}
                                      WHERE category > 0 AND FLOOR(timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY timeval", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timeval.'.'.$item->courses;
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
                                      GROUP BY timepoint", $this->params);

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
        $data = $DB->get_records_sql("SELECT FLOOR(timepoint / $ext) * $ext AS timepointval, SUM(visits) AS users
                                      FROM {local_intelliboard_totals}
                                      WHERE FLOOR(timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
                                      GROUP BY timepointval", $this->params);



        $response = array();
        foreach($data as $item){
            $response[] = $item->timepointval.'.'.$item->users;
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
        $this->params['userid'] = (int) $params->userid;
        if($params->userid){
        	$sql = " AND b.userid = :userid";
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
        $sql_enabled = $this->get_filter_in_sql($params->courseid,'e.courseid');

        return $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname
                                     FROM {user_enrolments} ue, {enrol} e, {user} u
                                     WHERE ue.enrolid = e.id AND u.id = ue.userid $sql_enabled $sql_filter", $this->params);
    }

    public function get_info($params){
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');

        $data = array(
        	'version' => get_component_version('local_intelliboard'),
        	'moodle' => $CFG->version
        );
        if (get_capability_info('moodle/competency:competencyview')) {
	        $data['competency'] = 1;
	    } else {
	    	$data['competency'] = 0;
	    }

        return $data;
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
                                            c.shortname,
                                            c.fullname,
                                            c.idnumber,
                                            ca.id AS cid,
                                            ca.idnumber AS cidnumber,
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
    public function get_userids($params){
        global $DB;

        $emails = array();
        $usernames = array();
		$values = explode(",", $params->filter);
        foreach($values as $val){
            $emails[] = clean_param($val, PARAM_EMAIL);
            $usernames[] = clean_param($val, PARAM_USERNAME);
        }
        if(empty($emails) and empty($usernames)){
        	return array();
        }

        $filter = "";
        if ($emails) {
			list($sql1, $sqlparams) = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'username');
			$this->params = array_merge($this->params, $sqlparams);
			$filter .= " AND username $sql1";
		}

		if ($usernames) {
			list($sql2, $sqlparams) = $DB->get_in_or_equal($emails, SQL_PARAMS_NAMED, 'email');
			$this->params = array_merge($this->params, $sqlparams);
			$filter .= ($filter) ? " OR email $sql2" : " AND email $sql2";
		}


        return $DB->get_records_sql("SELECT id FROM {user} WHERE id > 0 $filter", $this->params);
    }
    public function get_outcomes($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, shortname, fullname FROM {grade_outcomes} WHERE courseid > 0");
    }
    public function get_roles($params){
        global $DB;

        if($params->filter){
            $sql = "'none'";
        }else{
            $sql = "'student', 'user', 'frontpage'";
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
            $this->params['userid1'] = $params->userid;
            $this->params['userid2'] = $params->userid;
            $this->params['userid3'] = $params->userid;
            $this->params['userid4'] = $params->userid;
            $this->params['userid5'] = $params->userid;
            $grade_avg = intelliboard_grade_sql(true, $params);

            $user = $DB->get_record_sql("
            	SELECT
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
                    LEFT JOIN (SELECT g.userid, $grade_avg AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid1) as gc ON gc.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_site, SUM(visits) AS visits_site FROM {local_intelliboard_tracking} WHERE userid = :userid2) lit ON lit.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_courses, SUM(visits) AS visits_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 AND userid = :userid3) lit2 ON lit2.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_modules, SUM(visits) AS visits_modules FROM {local_intelliboard_tracking} WHERE page = 'module' AND userid = :userid4) lit3 ON lit3.userid = u.id
                 WHERE u.id = :userid5
                ", $this->params);

            if($user->id){
                $filter1 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);
                $filter2 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);

                $this->params['userid1'] = $user->id;
                $this->params['userid2'] = $user->id;
                $grade_avg = intelliboard_grade_sql(true, $params);

                $user->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site
                                                  FROM
                                                    (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site,
                                                            ROUND(AVG(b.visits_site),0) as visits_site
                                                        FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
                                                            FROM {local_intelliboard_tracking}
                                                            WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $filter1) AND userid != :userid1
                                                            GROUP BY userid) AS b) a,
					                                (SELECT ROUND(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
                                                        FROM {grade_items} gi, {grade_grades} g
                                                        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid NOT IN (
                                                          SELECT distinct userid FROM {role_assignments} WHERE $filter2) AND g.userid != :userid2 GROUP BY g.userid) b) c
                                                ",$this->params);


                $this->params['userid'] = $user->id;
                $user->data = $DB->get_records_sql("SELECT uif.id, uif.name, uid.data
                                                    FROM {user_info_field} uif,
                                                         {user_info_data} uid
                                                    WHERE uif.id = uid.fieldid AND uid.userid = :userid
                                                    ORDER BY uif.name
                                                   ",$this->params);

                $user->grades = $DB->get_records_sql("SELECT g.id, gi.itemmodule, $grade_avg AS grade
                                                      FROM {grade_items} gi,
                                                             {grade_grades} g
                                                      WHERE  gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid
                                                      GROUP BY gi.itemmodule
                                                      ORDER BY g.timecreated DESC
                                                     ",$this->params);

                $completion = $this->get_completion($params, "cmc.");
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
                                                           LEFT JOIN (SELECT cm.course, cmc.userid, COUNT(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
                                                       WHERE ue.userid = :userid
                                                       GROUP BY e.courseid
                                                       ORDER BY c.fullname
                                                      ",$this->params, 0, 100);
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
        $grade_avg = intelliboard_grade_sql(true, $params);

        return $DB->get_records_sql("
           SELECT u.id,u.firstname,u.lastname,u.email,u.firstaccess,u.lastaccess,cx.id AS context, gc.average, ue.courses, c.completed, ROUND(((c.completed/ue.courses)*100), 0) as progress
           FROM {user} u
                LEFT JOIN (SELECT g.userid, $grade_avg AS average FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) AS gc ON gc.userid = u.id
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
        $grade_avg = intelliboard_grade_sql(true, $params);

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
				LEFT JOIN (SELECT gi.courseid, $grade_avg AS grade
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
						                            (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
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

        $grade_single = intelliboard_grade_sql(false, $params);

        $grades = $DB->get_records_sql("SELECT g.id, $grade_single AS grade, gi.courseid, gi.itemname, c.fullname AS course, g.timecreated
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
        $data = $DB->get_records_sql("SELECT FLOOR(l.timepoint / $ext) * $ext as timepointval, SUM(l.visits) as visits
                                      FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                      WHERE l.trackid = t.id AND floor(l.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_filter
                                      GROUP BY timepointval", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timepointval.'.'.$item->visits;
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function widget27($params){
    	global $DB;

        $ext = 2592000; //by month

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();

        $sql = $this->get_teacher_sql($params, "u.id", "users");
        if($params->cohortid){
        	$sql_join = $this->get_filter_in_sql($params->cohortid, "cohortid");
			$sql .= " AND u.id IN (SELECT userid FROM {cohort_members} WHERE id > 0 $sql_join)";

		}

        $data = $DB->get_records_sql("SELECT FLOOR(u.timecreated / $ext) * $ext AS timepoint, COUNT(u.id) AS users
                                      FROM {user} u
                                      WHERE FLOOR(u.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
                                      GROUP BY timepoint", $this->params);

        $response = array();
        foreach($data as $item){
        	if($item->timepoint and $item->users){
	            $response[] = $item->timepoint.'.'.$item->users;
	        }
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function widget28($params){
    	global $DB;

        $ext = 86400;
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();


        if ($params->custom2) {
	        $sql = $this->get_filter_in_sql($params->custom2, 'id');
	        $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

	        $params->custom2 = array();
	        foreach ($result as $item){
	        	$params->custom2[] = $item->data;
	        }
	    }

	    $sql = $this->get_teacher_sql($params, "u.id", "users");
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");

		$data = $DB->get_records_sql("
			SELECT FLOOR(l.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT l.id) AS logins
			FROM {logstore_standard_log} AS l
				JOIN {user} u ON l.userid = u.id
				JOIN {user_info_data} d ON u.id = d.userid
				JOIN {user_info_field} f ON d.fieldid = f.id
			WHERE l.action LIKE '%loggedin%' AND floor(l.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
			GROUP BY timepointval", $this->params);


        $response = array();
        foreach($data as $item){
        	if($item->timepointval and $item->logins){
	            $response[] = $item->timepointval.'.'.$item->logins;
	        }
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function widget29($params){
    	global $DB;

        $ext = 2592000; //by month
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();


        if ($params->custom2) {
	        $sql = $this->get_filter_in_sql($params->custom2, 'id');
	        $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

	        $params->custom2 = array();
	        foreach ($result as $item){
	        	$params->custom2[] = $item->data;
	        }
	    }
	    $sql = $this->get_teacher_sql($params, "u.id", "users");
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");

        $data = $DB->get_records_sql("
			SELECT FLOOR(l.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT u.id) as users
			FROM {user} AS u
				JOIN {user_info_data} d ON u.id = d.userid
				JOIN {user_info_field} f ON d.fieldid = f.id
				JOIN {logstore_standard_log} l on u.id = l.userid
			WHERE u.firstaccess <> 0 AND floor(l.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
			GROUP BY timepointval", $this->params);

        $response = array();
        foreach($data as $item){
        	if($item->timepointval and $item->users){
	            $response[] = $item->timepointval.'.'.$item->users;
	        }
        }
        $obj = new stdClass();
        $obj->id = 0;
        $obj->data = implode(',', $response);
        return $obj;
    }
    public function widget30($params){
    	global $DB;

        $ext = 2592000; //by month
        $this->params['timestart'] = strtotime('-4 month');
        $this->params['timefinish'] = time();

        if ($params->custom) {
	        $sql = $this->get_filter_in_sql($params->custom, 'id');
	        $result = $DB->get_records_sql("SELECT enrol FROM {enrol} WHERE enrol != '' $sql", $this->params);

	        $params->custom = array();
	        foreach ($result as $item){
	        	$params->custom[] = $item->enrol;
	        }
	    }
	    $sql_user = $this->get_teacher_sql($params, "ue.userid", "users");
        $sql = $this->get_filter_in_sql($params->custom, "en.enrol");


        $sql_join = "";
        if ($params->custom2) {
        	if($totara =  $DB->get_record("config", array('name'=>'totara_build'))){
        		$sql_join = "JOIN {prog_courseset_course} d ON d.courseid = c.id
				JOIN {prog_courseset} courseset ON courseset.id = d.coursesetid";
        	} else {
        		return null;
        	}
        }

        return $DB->get_records_sql("
        	SELECT @x:=@x+1 as id, c.id as courseid, c.fullname, FLOOR(ue.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT ue.id) AS enrolled
			FROM (SELECT @x:= 0) AS x, {course} c
				$sql_join
				JOIN {enrol} en ON en.courseid = c.id $sql
				JOIN {user_enrolments} ue ON ue.enrolid = en.id
			WHERE floor(ue.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_user
			GROUP BY c.id, timepointval", $this->params);
    }
    public function widget31($params){
    	global $DB;

    	$ext = 2592000; //by month

    	if (!$params->custom or !$params->custom2) {
    		return null;
    	}

    	if ($params->custom2) {
	        $sql = $this->get_filter_in_sql($params->custom2, 'id');
	        $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

	        $params->custom2 = array();
	        foreach ($result as $item){
	        	$params->custom2[] = $item->data;
	        }
	    }

	    $sql = $this->get_teacher_sql($params, "u.id", "users");
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");


        return $DB->get_records_sql("
        	SELECT @x:=@x+1 as id, FLOOR(u.timecreated / $ext) * $ext AS timepointval, d.data, COUNT(DISTINCT u.id) users
			FROM (SELECT @x:= 0) AS x, {user} u
				JOIN {user_info_data} AS d ON u.id = d.userid
				JOIN {user_info_field} AS f ON d.fieldid = f.id
			WHERE d.data <> 'null' AND d.data <> '' $sql
			GROUP BY timepointval, d.data", $this->params);
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

        $data = $DB->get_records_sql("SELECT FLOOR(l.timepoint / $ext) * $ext AS timepointval, SUM(l.visits) AS visits
                                      FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                      WHERE l.trackid = t.id $sql_user AND floor(l.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_enabled
                                      GROUP BY timepointval", $this->params);

        $response = array();
        foreach($data as $item){
            $response[] = $item->timepointval.'.'.$item->visits;
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

        return $DB->get_records_sql("SELECT uif.id, uif.name, uif.shortname, uic.name AS category
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
        $grade_single = intelliboard_grade_sql(false, $params);

        $data['courses'] = $DB->get_records_sql("SELECT c.id, c.fullname, a.assignments, b.missing, t.quizes, cc.timecompleted, g.grade
                                                 FROM {user_enrolments} ue
                                                    LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                                    LEFT JOIN {course} c ON c.id = e.courseid
                                                    LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = :userid1
                                                    LEFT JOIN (SELECT gi.courseid, $grade_single AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid2 GROUP BY gi.courseid) g ON g.courseid = c.id
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
        $grade_avg = intelliboard_grade_sql(true, $params);

        return $DB->get_record_sql("
        	SELECT a.timespend_site, a.visits_site, c.grade_site
        	FROM
	            (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site, ROUND(AVG(b.visits_site),0) as visits_site
	                FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
	                    FROM {local_intelliboard_tracking}
	                    WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $sql_enabled) AND userid != 2 GROUP BY userid) AS b) a,
	            (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
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

        return $DB->get_records_sql("SELECT e.enrol, COUNT(ue.id) AS enrols FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql GROUP BY e.enrol", $this->params);
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
        $data[] = 'timepointval';

        $sql = $this->get_teacher_sql($params, "userid", "users");

        $this->params['timestart'] = $timestart;
        $this->params['timefinish'] = $timefinish;

        $data[] = $DB->get_records_sql("
			SELECT floor(timepoint / $ext) * $ext AS timepointval, SUM(sessions) AS visits
			FROM {local_intelliboard_totals}
			WHERE timepoint BETWEEN :timestart AND :timefinish
			GROUP BY timepointval", $this->params);

        $data[] = $DB->get_records_sql("
			SELECT floor(timecreated / $ext) * $ext AS timecreatedval, COUNT(DISTINCT (userid)) AS users
			FROM {user_enrolments}
			WHERE timecreated BETWEEN :timestart AND :timefinish $sql
			GROUP BY timecreatedval", $this->params);

        $data[] = $DB->get_records_sql("
			SELECT floor(timecompleted / $ext) * $ext AS timecreatedval, COUNT(DISTINCT (userid)) AS users
			FROM {course_completions}
			WHERE timecompleted BETWEEN :timestart AND :timefinish $sql
			GROUP BY timecreatedval", $this->params);

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

        $sql = "SELECT COUNT( DISTINCT cou.$unique_id) FROM (".$sql.") cou";
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
