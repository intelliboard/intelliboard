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

require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/instructor/lib.php');
require_once($CFG->dirroot .'/course/lib.php');

$action = optional_param('action', '', PARAM_TEXT);
$view = optional_param('view', '', PARAM_TEXT);
$daterange = clean_raw(optional_param('daterange', '', PARAM_RAW));
$course = optional_param('course', 0, PARAM_INT);

if (!isloggedin() or isguestuser()) {
	return false;
}
require_login();

$PAGE->set_context(context_system::instance());

if($action == 'get_total_students'){

    if (!$daterange) {
        $timestart = strtotime('-7 days');
        $timefinish = time();
    } else {
        $range = preg_split("/ (.)+ /", $daterange);

        if(isset($range[0]) && $range[0]) {
            $timestart = date_create_from_format(
                intelli_date_format(), trim($range[0])
            )->getTimestamp();
        } else {
            $timestart = strtotime('-7 days');
        }

        if(isset($range[1]) && $range[1]) {
            $timefinish = date_create_from_format(
                intelli_date_format(), trim($range[1])
            )->getTimestamp();
        } else {
            $timefinish = time();
        }
    }

    $learner_roles = get_config('local_intelliboard', 'filter11');
    $params = array('userid1'=>$USER->id,'userid2'=>$USER->id,'userid3'=>$USER->id,'timestart1'=>$timestart, 'timefinish1'=>$timefinish,'timestart2'=>$timestart, 'timefinish2'=>$timefinish,'timestart3'=>$timestart, 'timefinish3'=>$timefinish);

    $sql1 = $sql2 = intelliboard_instructor_getcourses("lit.courseid", false, 'ra.userid');
    $sql3 = intelliboard_instructor_getcourses("e.courseid", false, 'ue.userid');
    $sql7 = intelliboard_instructor_getcourses("ctx.instanceid", false, 'ra.userid');

    list($sql4, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
    list($sql5, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
    list($sql6, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
    $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');

    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $sql3 .= ' AND ue.status = 0';
        $sql7 .= ' AND enr.status = 0';
    }

    $data = $DB->get_record_sql(
        "SELECT COUNT(DISTINCT CASE WHEN ue.timecreated BETWEEN :timestart1 AND :timefinish1
                                    THEN ue.userid
                                    ELSE NULL
                                END
                ) AS enrolled_users,
                COUNT(DISTINCT ue.userid) AS total_users,
                (SELECT AVG(t.sum_timespent)
                   FROM (SELECT SUM(lil.timespend) AS sum_timespent
                           FROM {local_intelliboard_tracking} lit
                      LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                      LEFT JOIN {context} ctx ON ctx.contextlevel=50 AND ctx.instanceid=lit.courseid
                      LEFT JOIN {role_assignments} ra ON ra.contextid=ctx.id AND ra.userid=lit.userid {$sql4}
                                {$join_sql1}
                          WHERE lil.timepoint BETWEEN :timestart2 AND :timefinish2 AND
                                ra.userid IS NOT NULL {$sql1}
                       GROUP BY lit.userid
                        ) AS t
                ) AS avg_timespend,
                (SELECT COUNT(DISTINCT lit.userid)
                   FROM {local_intelliboard_tracking} lit
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid = lit.id
              LEFT JOIN {context} ctx ON ctx.contextlevel = 50 AND ctx.instanceid = lit.courseid
              LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = lit.userid {$sql5}
                        {$join_sql1}
                  WHERE lil.timepoint BETWEEN :timestart3 AND :timefinish3 AND ra.userid IS NOT NULL {$sql2}
                ) AS active_users
           FROM {enrol} e
      LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
      LEFT JOIN {context} ctx ON ctx.contextlevel = 50 AND ctx.instanceid=e.courseid
      LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid {$sql6}
                {$join_sql1}
          WHERE ra.userid IS NOT NULL {$sql3}",
        $params
    );

    $data->avg_timespend = seconds_to_time($data->avg_timespend);

    $users_list = $DB->get_records_sql(
        "SELECT u.*,
                CASE WHEN MAX(lil.timepoint) BETWEEN :timestart3 AND :timefinish3 THEN 1 ELSE 0 END AS active
           FROM {context} ctx
      LEFT JOIN {role_assignments} ra ON ra.contextid=ctx.id {$sql5}
      LEFT JOIN {local_intelliboard_tracking} lit ON ctx.instanceid = lit.courseid AND ra.userid = lit.userid
      LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
           JOIN {user} u ON u.id = ra.userid
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = ra.userid AND enr.courseid = ctx.instanceid
                {$join_sql1}
          WHERE ctx.contextlevel = 50 AND ra.userid IS NOT NULL {$sql7}
       GROUP BY u.id",
        $params
    );

    $data->users = array();
    foreach($users_list as $user){
        $key = ($user->active == 1)?'active':'not_active';
        $data->users[$key] .= "<li><a href='".$CFG->wwwroot."/user/view.php?id=".$user->id."'>".fullname($user)."</a></li>";
    }

	die(json_encode($data));
}elseif($action == 'get_learner_engagement'){
    if(!$course){
        die(json_encode(array()));
    }
    if (!$daterange) {
        $timestart = strtotime('-7 days');
        $timefinish = time();
    } else {
        $range = preg_split("/ (.)+ /", $daterange);

        if(isset($range[0]) && $range[0]) {
            $timestart = date_create_from_format(
                intelli_date_format(), trim($range[0])
            )->getTimestamp();
        } else {
            $timestart = strtotime('-7 days');
        }

        if(isset($range[1]) && $range[1]) {
            $timefinish = date_create_from_format(
                intelli_date_format(), trim($range[1])
            )->getTimestamp();
        } else {
            $timefinish = time();
        }
    }

    $learner_roles = get_config('local_intelliboard', 'filter11');

    $params = array('course'=>$course,'timestart'=>$timestart, 'timefinish'=>$timefinish);
    list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    $enrfilter = '';
    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $enrfilter = ' AND enr.status = 0';
    }

    $join_sql1 = '';
    $join_sql2 = '';
    $select_sql2 = '';
    if(get_config('local_intelliboard', 'group_aggregation')){
        $join_sql1 = "JOIN (SELECT gm.userid,c.id AS courseid
                        FROM {course} c
                          LEFT JOIN {groups} g ON g.courseid=c.id
                          LEFT JOIN {groups_members} gm ON g.id=gm.groupid AND gm.groupid IN (SELECT groupid FROM {groups_members} WHERE userid = $USER->id)
                        GROUP BY gm.userid,c.id
                       ) group_user ON (group_user.userid=ra.userid AND group_user.courseid=ctx.instanceid) OR (group_user.userid IS NULL AND group_user.courseid=ctx.instanceid)";
        $join_sql2 = "LEFT JOIN (SELECT gm.userid,c.id AS courseid
                        FROM {course} c
                          LEFT JOIN {groups} g ON g.courseid=c.id
                          LEFT JOIN {groups_members} gm ON g.id=gm.groupid AND gm.groupid IN (SELECT groupid FROM {groups_members} WHERE userid = $USER->id)
                        GROUP BY gm.userid,c.id
                       ) group_user ON group_user.courseid=ctx.instanceid";
        $select_sql2 = "AND ((group_user.userid=ra.userid AND group_user.courseid=ctx.instanceid) OR (group_user.userid IS NULL AND group_user.courseid=ctx.instanceid))";
    }

    $enrolled_users = $DB->get_record_sql(
        "SELECT COUNT(DISTINCT ra.userid) AS users
          FROM {role_assignments} ra
     LEFT JOIN {context} ctx ON ctx.instanceid=:course AND ctx.contextlevel=50 AND ra.contextid=ctx.id
     LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
               ) enr ON enr.userid = ra.userid AND enr.courseid = ctx.instanceid
               $join_sql1
         WHERE ctx.contextlevel=50 $sql1 {$enrfilter}",
        $params
    );

    $sql_columns = "";
    $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1");
    foreach($modules as $module){
        $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
    }
    $sql_columns =  ($sql_columns) ? ", CASE $sql_columns ELSE 'none' END AS activity" : "'' AS activity";

    $data = $DB->get_records_sql(
        "SELECT cm.id,
                COUNT(DISTINCT CASE WHEN lil.id IS NOT NULL $select_sql2 THEN ra.userid ELSE NULL END) AS students_attempt
                $sql_columns
           FROM {course_modules} cm
      LEFT JOIN {modules} m ON m.id = cm.module
      LEFT JOIN {local_intelliboard_tracking} lit ON lit.courseid=cm.course AND lit.param=cm.id AND lit.page='module'
      LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id AND lil.timepoint BETWEEN :timestart AND :timefinish
      LEFT JOIN {context} ctx ON ctx.contextlevel=50 AND ctx.instanceid=lit.courseid
      LEFT JOIN {role_assignments} ra ON ra.contextid=ctx.id AND ra.userid=lit.userid $sql1
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = ra.userid AND enr.courseid = ctx.instanceid
                $join_sql2
          WHERE cm.course=:course {$enrfilter}
       GROUP BY cm.id,m.name",
        $params
    );

    $tooltip = new stdClass();
    $tooltip->type = 'string';
    $tooltip->role = 'tooltip';
    $tooltip->p = new stdClass();
    $tooltip->p->html = true;

    $modules = array([get_string('s45', 'local_intelliboard'), get_string('s46', 'local_intelliboard'),$tooltip]);
    foreach($data as $item){
        $tooltip = '<strong>'.$item->activity.'</strong><br>'.get_string('s46', 'local_intelliboard').': <strong>'.round(($item->students_attempt*100)/$enrolled_users->users,2).'%</strong>';
        $modules[] = array($item->activity, $item->students_attempt/$enrolled_users->users, $tooltip);
    }

    if (!$data) {
        $modules[] = ['', 0, ''];
    }

    die(json_encode($modules));
}elseif($action == 'get_module_utilization'){
    if(!$course){
        die(json_encode(array()));
    }

    if (!$daterange) {
        $timestart = strtotime('-7 days');
        $timefinish = time();
    } else {
        $range = preg_split("/ (.)+ /", $daterange);

        if(isset($range[0]) && $range[0]) {
            $timestart = date_create_from_format(
                intelli_date_format(), trim($range[0])
            )->getTimestamp();
        } else {
            $timestart = strtotime('-7 days');
        }

        if(isset($range[1]) && $range[1]) {
            $timefinish = date_create_from_format(
                intelli_date_format(), trim($range[1])
            )->getTimestamp();
        } else {
            $timefinish = time();
        }
    }

    $learner_roles = get_config('local_intelliboard', 'filter11');
    $params = array('course'=>$course,'timestart'=>$timestart, 'timefinish'=>$timefinish);
    list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $sql1 .= ' AND enr.status = 0';
    }

    $sql_columns = "";
    $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1");
    foreach($modules as $module){
        $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
    }
    $sql_columns =  ($sql_columns) ? ", CASE $sql_columns ELSE 'none' END AS activity" : "'' AS activity";
    $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');

    $data = $DB->get_records_sql("
                SELECT
                  cm.id,
                  sum(lil.timespend) as timespend
                  $sql_columns

                FROM {role_assignments} ra
                    LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {course_modules} cm ON cm.course = c.id
                    LEFT JOIN {modules} m ON m.id = cm.module
                    LEFT JOIN {local_intelliboard_tracking} l ON l.page = 'module' AND l.userid = ra.userid AND l.param = cm.id
                    LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=l.id AND lil.timepoint BETWEEN :timestart AND :timefinish
                    LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                                 FROM {user_enrolments} ue
                                 JOIN {enrol} e ON ue.enrolid = e.id
                             GROUP BY ue.userid, e.courseid
                              ) enr ON enr.userid = ra.userid AND enr.courseid = c.id
                    $join_sql1
                WHERE c.id = :course $sql1
                GROUP BY cm.id,m.name",$params);

    $tooltip = new stdClass();
    $tooltip->type = 'string';
    $tooltip->role = 'tooltip';
    $tooltip->p = new stdClass();
    $tooltip->p->html = true;

    $modules = array([get_string('s45', 'local_intelliboard'), get_string('time_spent', 'local_intelliboard'), $tooltip]);
    $empty_data = true;
    foreach($data as $item){
        if($item->timespend>0){
            $empty_data = false;
        }
        $tooltip = '<strong>'.$item->activity.'</strong><br>'.get_string('time_spent', 'local_intelliboard').': <strong>'.seconds_to_time($item->timespend).'</strong>';
        $inner = new stdClass();
        $inner->v = (int)$item->timespend;
        $inner->f = seconds_to_time(intval($item->timespend));

        $modules[] = array($item->activity, $inner,$tooltip);
    }

    if($empty_data){
        die(json_encode(array()));
    }else{
        die(json_encode($modules));
    }
}elseif($action == 'get_topic_utilization'){
    if(!$course){
        die(json_encode(array()));
    }

    if (!$daterange) {
        $timestart = strtotime('-7 days');
        $timefinish = time();
    } else {
        $range = preg_split("/ (.)+ /", $daterange);

        if(isset($range[0]) && $range[0]) {
            $timestart = date_create_from_format(
                intelli_date_format(), trim($range[0])
            )->getTimestamp();
        } else {
            $timestart = strtotime('-7 days');
        }

        if(isset($range[1]) && $range[1]) {
            $timefinish = date_create_from_format(
                intelli_date_format(), trim($range[1])
            )->getTimestamp();
        } else {
            $timefinish = time();
        }
    }

    $params = array('course' => $course, 'course1' => $course, 'timestart' => $timestart, 'timefinish' => $timefinish);
    $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');
    $learner_roles = get_config('local_intelliboard', 'filter11');
    list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    $enrolfilter = '';
    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $enrolfilter = ' AND enr.status = 0';
    }

    $data = $DB->get_records_sql(
        "SELECT cs.id,
                MAX(cs.section) AS section,
                SUM(lil.timespend) as timespend
           FROM {course_modules} cm
           JOIN {course_sections} cs ON cs.id = cm.section
           JOIN {modules} m ON m.id = cm.module
      LEFT JOIN (SELECT ctx.instanceid AS course_id, ra.userid
                       FROM {role_assignments} ra
                       JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.instanceid = :course AND ctx.contextlevel = 50
                            {$join_sql1}
                      WHERE ctx.contextlevel = 50 {$sql1}
                   GROUP BY ctx.instanceid, ra.userid
                ) stud ON cm.course = stud.course_id
      LEFT JOIN {local_intelliboard_tracking} l ON l.page = 'module' AND l.userid = stud.userid AND l.param = cm.id
      LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid = l.id AND lil.timepoint BETWEEN :timestart AND :timefinish
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = stud.userid AND enr.courseid = stud.course_id
          WHERE cm.course = :course1 {$enrolfilter}
       GROUP BY cs.id",
        $params
    );

    $tooltip = new stdClass();
    $tooltip->type = 'string';
    $tooltip->role = 'tooltip';
    $tooltip->p = new stdClass();
    $tooltip->p->html = true;

    $modules = array([get_string('s45', 'local_intelliboard'), get_string('time_spent', 'local_intelliboard'), $tooltip]);
    $empty_data = true;
    foreach($data as $item){
        if($item->timespend>0){
            $empty_data = false;
        }
        $name = addslashes(get_section_name($course,$item->section));
        $tooltip = '<strong>'.$name.'</strong><br>'.get_string('time_spent', 'local_intelliboard').': <strong>'.seconds_to_time($item->timespend).'</strong>';
        $inner = new stdClass();
        $inner->v = (int)$item->timespend;
        $inner->f = seconds_to_time(intval($item->timespend));

        $modules[] = array($name, $inner,$tooltip);
    }

    if($empty_data){
        die(json_encode(array()));
    }else{
        die(json_encode($modules));
    }
}elseif($action == 'get_course_overview'){

    if ($view == 'topic') {
        $params = array(
            'courseid2' => $course,
            'courseid3' => $course
        );
        $learner_roles = get_config('local_intelliboard', 'filter11');
        list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
        $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');

        $courses = $DB->get_records_sql(
            "SELECT cs.id,
                    MAX(cs.section) AS section,
                    SUM(lit.timespend) AS timespend
               FROM {course_modules} cm
          LEFT JOIN {modules} m ON m.id = cm.module
          LEFT JOIN {course_sections} cs ON cs.id = cm.section
          LEFT JOIN (SELECT ctx.instanceid AS course_id, ra.userid
                       FROM {role_assignments} ra
                       JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.instanceid = :courseid3 AND ctx.contextlevel = 50
                            {$join_sql1}
                      WHERE ctx.contextlevel = 50 {$sql1}
                   GROUP BY ctx.instanceid, ra.userid
                    ) stud ON stud.course_id = cm.course
          LEFT JOIN {local_intelliboard_tracking} lit ON lit.param = cm.id AND lit.page = 'module' AND lit.userid = stud.userid
              WHERE cm.course = :courseid2
           GROUP BY cs.id",
            $params
        );

        $tooltip = new stdClass();
        $tooltip->type = 'string';
        $tooltip->role = 'tooltip';
        $tooltip->p = new stdClass();
        $tooltip->p->html = true;

        $data = array([get_string('s47', 'local_intelliboard'), get_string('s48', 'local_intelliboard'), $tooltip]);

        foreach($courses as $value){
            $value->timespend_str = seconds_to_time($value->timespend);

            $inner = new stdClass();
            $inner->v = (int)$value->timespend;
            $inner->f = seconds_to_time(intval($value->timespend));

            $section = addslashes(get_section_name($course,$value->section));

            $tooltip = "<strong>$section</strong><br>".get_string('s48', 'local_intelliboard')." <strong>$value->timespend_str</strong>";

            $data[] = array($section,$inner,$tooltip);
        }

    }else{
        $sql_columns = "";
        $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1");
        foreach($modules as $module){
            $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {" . $module->name . "} WHERE id = cm.instance)";
        }
        $sql_columns = ($sql_columns)?", CASE $sql_columns ELSE 'none' END AS activity":"'' AS activity";

        $params = array(
            'courseid1' => $course,
            'courseid2' => $course,
            'courseid3' => $course
        );
        $learner_roles = get_config('local_intelliboard', 'filter11');
        list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
        $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');

        $courses = $DB->get_records_sql("
                SELECT
                  cm.id,
                  (SELECT SUM(timespend)
                    FROM {local_intelliboard_tracking}
                    WHERE courseid=:courseid1 AND param=cm.id AND page='module' AND userid IN (SELECT DISTINCT ra.userid
                                                                                                  FROM {role_assignments} ra
                                                                                                    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.instanceid = :courseid3 AND ctx.contextlevel = 50
                                                                                                    $join_sql1
                                                                                                  WHERE  ctx.contextlevel = 50 $sql1)) AS timespend
                  $sql_columns
                FROM {course_modules} cm
                  LEFT JOIN {modules} m ON m.id = cm.module
                WHERE cm.course=:courseid2", $params);

        $tooltip = new stdClass();
        $tooltip->type = 'string';
        $tooltip->role = 'tooltip';
        $tooltip->p = new stdClass();
        $tooltip->p->html = true;

        $data = array([get_string('s45', 'local_intelliboard'), get_string('s25', 'local_intelliboard'), $tooltip]);

        foreach($courses as $value){
            $value->timespend_str = seconds_to_time($value->timespend);
            $value->activity = addslashes($value->activity);

            $inner = new stdClass();
            $inner->v = (int)$value->timespend;
            $inner->f = seconds_to_time(intval($value->timespend));

            $tooltip = "<strong>$value->activity</strong><br>".get_string('s25', 'local_intelliboard')." <strong>$value->timespend_str</strong>";

            $data[] = array($value->activity,$inner,$tooltip);
        }
    }

    die(json_encode($data));
}elseif($action == 'get_course_users'){
    $params = array(
        'course' => $course
    );
    $learner_roles = get_config('local_intelliboard', 'filter11');
    list($sql1, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);
    $join_sql1 = intelliboard_group_aggregation_sql('ra.userid', $USER->id, 'ctx.instanceid');

    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $sql1 .= ' AND enr.status = 0';
    }

    $enrolled_users = $DB->get_records_sql(
        "SELECT u.*
           FROM {role_assignments} ra
      LEFT JOIN {context} ctx ON ctx.instanceid = :course AND ctx.contextlevel = 50 AND ra.contextid = ctx.id
      LEFT JOIN {user} u ON u.id = ra.userid
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = ra.userid AND enr.courseid = ctx.instanceid
                {$join_sql1}
          WHERE ctx.contextlevel = 50 {$sql1}",
        $params
    );

    $html = '';
    foreach($enrolled_users as $user){
        $html .= '<li><a href="#" data-value="'.$user->id.'">'.fullname($user).'</a></li>';
    }

    die(json_encode(array('items'=>$html)));
}elseif($action == 'get_student_grade_progression'){
    $user = required_param('user', PARAM_INT);
    $grade_sql = intelliboard_grade_sql(false,null,'gh.');
    $grade_percent = intelliboard_grade_sql(false,null, 'gh.',0, 'gi.',true);
    $raw = get_config('local_intelliboard', 'scale_raw');

    $enrfilter = '';
    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $enrfilter = ' AND enr.status = 0';
    }

    $data = $DB->get_records_sql(
        "SELECT gh.timemodified,
                $grade_percent AS finalgrade,
                $grade_sql AS grade_real,
                gh.rawgrademax
           FROM {grade_items} gi
           JOIN {grade_grades_history} gh ON gh.itemid = gi.id AND gh.userid = :user AND gh.finalgrade IS NOT NULL
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = gh.userid AND enr.courseid = gi.courseid
          WHERE gi.courseid = :course AND gi.itemtype = 'course' {$enrfilter}",
        array('user' => $user, 'course' => $course)
    );

    $tooltip = new stdClass();
    $tooltip->type = 'string';
    $tooltip->role = 'tooltip';
    $tooltip->p = new stdClass();
    $tooltip->p->html = true;

    $grades = array([array('type'=>'datetime','label'=>get_string('time', 'local_intelliboard')), get_string('course_grade', 'local_intelliboard'), $tooltip]);
    foreach($data as $item){

        $tooltip = "<div class=\"chart-tooltip\">";
        $tooltip .= "<div class=\"chart-tooltip-header\">". userdate($item->timemodified) ."</div>";
        $tooltip .= "<div class=\"chart-tooltip-body clearfix\">";
        $tooltip .= "<div class=\"chart-tooltip-left\">".get_string('grade','local_intelliboard').": <span>". $item->grade_real."</span></div>";
        $tooltip .= "<div class=\"chart-tooltip-right\">".get_string('course_max_grade','local_intelliboard').": <span>". round($item->rawgrademax, 2)."</span></div>";
        $tooltip .= "</div>";
        $tooltip .= "</div>";

        //$tooltip = "<strong>".userdate($item->timemodified)."</strong><br>".get_string('course_grade', 'local_intelliboard').": <strong>".round($item->finalgrade,2).((!$raw)?'%':'')."</strong>";

        $grades[] = array((int)$item->timemodified, round($item->finalgrade,2), $tooltip);
    }

    die(json_encode($grades));
} elseif($action === 'graded_activities_overview') {
    $courseid = required_param('course', PARAM_INT);
    $export = optional_param('export', false, PARAM_BOOL);

    if($export) {
        $format = required_param('format', PARAM_TEXT);
    }

    $data = [[
        get_string('activity', 'local_intelliboard'),
        get_string('grade', 'local_intelliboard'),
    ]];

    $enrfilter = '';
    if (!get_config('local_intelliboard', 'instructor_show_suspended_enrollments')) {
        $enrfilter = ' AND enr.status = 0';
    }

    $activities = $DB->get_records_sql(
        "SELECT ga.id, ga.itemname, AVG(gg.finalgrade) as grade
           FROM {grade_items} ga
      LEFT JOIN {grade_grades} gg ON gg.itemid = ga.id
      LEFT JOIN (SELECT ue.userid, MIN(ue.status) AS status, e.courseid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
               GROUP BY ue.userid, e.courseid
                ) enr ON enr.userid = gg.userid AND enr.courseid = ga.courseid
          WHERE ga.courseid = :course AND ga.itemtype = 'mod' {$enrfilter}
       GROUP BY ga.id, ga.itemname",
        ['course' => $courseid]
    );

    if(!$activities && !$export) {
        $data[] = ['', 0];
        exit(json_encode($data));
    }

    foreach($activities as $activity) {
        $data[] = [
            intellitext($activity->itemname),
            round($activity->grade, 2)
        ];
    }

    if($export) {
        $header = [];
        $head1 = new \stdClass();
        $head1->name = get_string('activity', 'local_intelliboard');
        $header[] = $head1;

        $head2 = new \stdClass();
        $head2->name = get_string('grade', 'local_intelliboard');
        $header[] = $head2;

        $json = new \stdClass();
        $json->header = $header;
        $json->body = array_slice($data, 1);
        return intelliboard_export_report(
            $json, get_string('grade_activities_overview', 'local_intelliboard'), $format
        );
    }

    die(json_encode($data));
}
