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

defined('MOODLE_INTERNAL') || die();

function intelliboard_instructor_access()
{
    global $USER;

    if(!get_config('local_intelliboard', 'n10')){
        throw new moodle_exception('invalidaccess', 'error');
    }
    $access = false;
    $instructor_roles = get_config('local_intelliboard', 'filter10');
    if (!empty($instructor_roles)) {
        $roles = explode(',', $instructor_roles);
        if (!empty($roles)) {
            foreach ($roles as $role) {
                if ($role and user_has_role_assignment($USER->id, $role)){
                    $access = true;
                    break;
                }
            }
        }
    }
    if (!$access) {
        throw new moodle_exception('invalidaccess', 'error');
    }
}
function intelliboard_course_learners_total($courseid)
{
    global $DB;

    $params = array('courseid' => $courseid);
    list($sql_roles, $sql_params) = $DB->get_in_or_equal(explode(',', get_config('local_intelliboard', 'filter11')), SQL_PARAMS_NAMED, 'r');
    $params = array_merge($params,$sql_params);

    return $DB->get_record_sql("
        SELECT c.id,c.fullname, c.startdate, c.enablecompletion, ca.name AS category, cs.sections,
            COUNT(DISTINCT ra.userid) as learners,
            COUNT(DISTINCT g.userid) as learners_graduated,
            COUNT(DISTINCT cc.id) as learners_completed,
            AVG((g.finalgrade/g.rawgrademax)*100) AS grade,
            cc.timecompleted, SUM(l.timespend) as timespend, SUM(l.visits) as visits
        FROM {role_assignments} ra
        LEFT JOIN {context} e ON e.id = ra.contextid AND e.contextlevel = 50
        LEFT JOIN {course} c ON c.id = e.instanceid
        LEFT JOIN {course_categories} ca ON ca.id = c.category
        LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ra.userid AND cc.timecompleted > 0
        LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
        LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
        LEFT JOIN (SELECT course, COUNT(id) as sections FROM {course_sections} WHERE visible = 1 GROUP BY course) cs ON cs.course = c.id
        LEFT JOIN (SELECT t.userid,t.courseid, SUM(t.timespend) as timespend, SUM(t.visits) as visits FROM
            {local_intelliboard_tracking} t GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = ra.userid
        WHERE ra.roleid $sql_roles AND e.instanceid = :courseid LIMIT 1", $params);
}

function intelliboard_learner_data($userid, $courseid)
{
    global $DB;

    $params = array(
        'c1' => $courseid,
        'c2' => $courseid,
        'c3' => $courseid,
        'u1' => $userid,
        'u2' => $userid,
        'u3' => $userid
    );

    return $DB->get_record_sql("SELECT
        DISTINCT(u.id) as userid,
        u.email,
        ul.timeaccess,
        ue.timemodified as enrolled,
        CONCAT(u.firstname, ' ', u.lastname) as learner,
        e.courseid,
        c.fullname as course,
        cc.timecompleted,
        (g.finalgrade/g.rawgrademax)*100 as grade, cmc.progress,
        l.timespend, l.visits
     FROM
        {user} u
        LEFT JOIN {course} c ON c.id = :c3
        LEFT JOIN {enrol} e ON e.courseid = c.id
        LEFT JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.enrolid = e.id
        LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
        LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
        LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
        LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
        LEFT JOIN (SELECT courseid, userid, SUM(timespend) as timespend, SUM(visits) as visits FROM
            {local_intelliboard_tracking} WHERE courseid = :c1 AND userid = :u1) l ON l.courseid = c.id AND l.userid = ue.userid
        LEFT JOIN (SELECT cmc.userid, COUNT(DISTINCT cmc.id) as progress FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 AND cm.completion = 1 AND cm.course = :c2 AND cmc.userid = :u2) cmc ON cmc.userid = u.id
    WHERE u.id = :u3 LIMIT 1", $params);
}
function intelliboard_activities_data($courseid)
{
    global $DB;

    $params = array(
        'courseid' => $courseid,
        'courseid2' => $courseid,
        'courseid3' => $courseid,
        'courseid4' => $courseid
    );

    list($sql1, $sql_params) = $DB->get_in_or_equal(explode(',', get_config('local_intelliboard', 'filter11')), SQL_PARAMS_NAMED, 'r');
    $params = array_merge($params,$sql_params);

    return $DB->get_record_sql("
        SELECT
            cm.id,
            c.id AS courseid,
            c.fullname,
            c.startdate,
            ca.name AS category,
            l.visits,
            l.timespend,
            g.grade,
            COUNT(DISTINCT cs.id) as sections,
            COUNT(DISTINCT cm.id) as modules,
            COUNT(DISTINCT cmc.id) AS completed
        FROM {course} c
            LEFT JOIN {course_categories} ca ON ca.id = c.category
            LEFT JOIN {course_modules} cm ON cm.course = c.id
            LEFT JOIN {course_sections} cs ON cs.visible=1 AND cs.course = c.id
            LEFT JOIN {course_modules_completion} cmc ON cmc.completionstate=1 AND cmc.coursemoduleid = cm.id
            LEFT JOIN (
                SELECT gi.courseid, AVG((g.finalgrade/g.rawgrademax)*100) AS grade
                FROM {grade_items} gi, {grade_grades} g
                WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid = :courseid3
                GROUP BY gi.id) g ON g.courseid = c.id
            LEFT JOIN (
                SELECT l.courseid, SUM(l.visits) AS visits, SUM(l.timespend) AS timespend
                FROM {local_intelliboard_tracking} l WHERE l.courseid = :courseid2 AND l.userid IN (SELECT DISTINCT ra.userid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.instanceid = :courseid4 AND ctx.contextlevel = 50 AND ra.roleid $sql1)
                GROUP BY l.courseid) l ON l.courseid=c.id
        WHERE c.id = :courseid", $params);
}

function intelliboard_activity_data($cmid, $courseid)
{
    global $DB;

    $params = array('cmid' => $cmid);
    $cm = $DB->get_record_sql("SELECT cm.id, cm.instance, m.name FROM {course_modules} cm, {modules} m WHERE cm.id = :cmid AND m.id = cm.module", $params);

    list($sql1, $sql_params) = $DB->get_in_or_equal(explode(',', get_config('local_intelliboard', 'filter11')), SQL_PARAMS_NAMED, 'r');
    $params = array_merge($params,$sql_params);

    $params['instance'] = $cm->instance;
    $params['instance2'] = $cm->instance;
    $params['module'] = $cm->name;
    $params['courseid'] = $courseid;
    $params['cmid2'] = $cmid;
    $params['cmid3'] = $cmid;

    return $DB->get_record_sql("
        SELECT
            cm.id, c.id AS courseid,
            c.fullname as course,
            i.name,
            ca.name AS category,
            cs.section,
            m.name as module, l.visits, l.timespend,
            (SELECT COUNT(id) FROM {course_modules_completion} WHERE completionstate=1 AND coursemoduleid=:cmid) AS completed,
            (SELECT AVG((g.finalgrade/g.rawgrademax)*100) FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.itemmodule = :module AND gi.iteminstance = :instance2) AS grade
        FROM {course_modules} cm
            LEFT JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {course} c ON c.id = cm.course
            LEFT JOIN {course_sections} cs ON cs.id = cm.section AND cs.course = c.id
            LEFT JOIN {course_categories} ca ON ca.id = c.category
            LEFT JOIN {".$cm->name."} i ON i.id = :instance
            LEFT JOIN (SELECT l.param, SUM(l.visits) AS visits, SUM(l.timespend) AS timespend FROM {local_intelliboard_tracking} l WHERE l.page='module' AND l.param = :cmid2 AND l.userid IN (SELECT DISTINCT ra.userid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.instanceid = :courseid AND ctx.contextlevel = 50 AND ra.roleid $sql1)) l ON l.param=cm.id
        WHERE cm.id = :cmid3", $params);
}


function intelliboard_instructor_correlations($page, $length)
{
    global $DB, $USER;

    $teacher_roles = get_config('local_intelliboard', 'filter10');
    $learner_roles = get_config('local_intelliboard', 'filter11');

    $params = array(
        'userid'=>$USER->id,
        'userid2'=>$USER->id
    );
    list($sql1, $params) = intelliboard_filter_in_sql($teacher_roles, "ra.roleid", $params);
    list($sql2, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    $items = $DB->get_records_sql("
            SELECT
                c.id,
                c.fullname,
                AVG((g.finalgrade/g.rawgrademax)*100) AS grade,
                SUM(l.duration) as duration, '0' AS duration_calc
            FROM {course} c
                LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.finalgrade IS NOT NULL
                LEFT JOIN (SELECT courseid, userid, sum(timespend) AS duration FROM {local_intelliboard_tracking} WHERE courseid > 0 GROUP BY courseid, userid) l ON l.courseid = c.id AND l.userid = g.userid
            WHERE  c.id IN (
                SELECT DISTINCT ctx.instanceid
                FROM {role_assignments} ra, {context} ctx
                WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1)
            GROUP BY c.id", $params, $page, $length);

     $d = 0;
    foreach($items as $c){
        $d = ($c->duration > $d)?$c->duration:$d;
    }
    if($d){
        foreach($items as $c){
            $c->duration_calc =  (intval($c->duration)/$d)*100;
        }
    }
    $data = array();
    foreach($items as $item){
        $l = intval($item->duration_calc);
        $d = seconds_to_time(intval($item->duration));

        $tooltip = "<div class=\"chart-tooltip\">";
        $tooltip .= "<div class=\"chart-tooltip-header\">". format_string($item->fullname) ."</div>";
        $tooltip .= "<div class=\"chart-tooltip-body clearfix\">";
        $tooltip .= "<div class=\"chart-tooltip-left\">".get_string('grade','local_intelliboard').": <span>". round($item->grade, 2)."</span></div>";
        $tooltip .= "<div class=\"chart-tooltip-right\">".get_string('time_spent','local_intelliboard').": <span>". $d."</span></div>";
        $tooltip .= "</div>";
        $tooltip .= "</div>";
        $data[] = array($l, round($item->grade, 2), $tooltip);
    }
    return $data;
}
function intelliboard_instructor_modules()
{
    global $DB, $USER;

    $teacher_roles = get_config('local_intelliboard', 'filter10');
    $learner_roles = get_config('local_intelliboard', 'filter11');

    $params = array(
        'userid'=>$USER->id,
        'userid2'=>$USER->id
    );
    list($sql1, $params) = intelliboard_filter_in_sql($teacher_roles, "ra.roleid", $params);
    list($sql2, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    $items = $DB->get_records_sql("
        SELECT
            cm.id,
            m.name,
            sum(l.timespend) as visits,
            sum(l.timespend) as timespend
        FROM {role_assignments} ra
            LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
            LEFT JOIN {course} c ON c.id = ctx.instanceid
            LEFT JOIN {course_modules} cm ON cm.course = c.id
            LEFT JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {local_intelliboard_tracking} l ON l.page = 'module' AND l.userid = ra.userid AND l.param = cm.id
        WHERE c.id IN (
            SELECT DISTINCT ctx.instanceid
            FROM {role_assignments} ra, {context} ctx
            WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1) $sql2
        GROUP BY m.id", $params);

    $data = array(array(get_string('in6', 'local_intelliboard'), get_string('time_spent', 'local_intelliboard')));
    foreach($items as $item){
        $inner = new stdClass();
        $inner->v = (int)$item->timespend;
        $inner->f = seconds_to_time(intval($item->timespend));
        $data[] = array(format_string(ucfirst($item->name)), $inner);
    }
    return $data;
}
function intelliboard_instructor_stats()
{
    global $DB, $USER;

    $teacher_roles = get_config('local_intelliboard', 'filter10');
    $learner_roles = get_config('local_intelliboard', 'filter11');

    $params = array(
        'userid'=>$USER->id,
        'userid2'=>$USER->id
    );
    list($sql1, $params) = intelliboard_filter_in_sql($teacher_roles, "ra.roleid", $params);
    list($sql2, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    return $DB->get_record_sql("
        SELECT
        SUM(x.enrolled) AS enrolled,
        SUM(x.completed) AS completed,
        SUM(x.grades) AS grades,
        COUNT(DISTINCT x.courseid) AS courses,
        AVG(x.grade) AS grade
        FROM
            (SELECT u.id,
                c.id AS courseid,
                COUNT(DISTINCT ra.userid) as enrolled,
                COUNT(DISTINCT cc.userid) as completed,
                COUNT(DISTINCT g.id) as grades,
                AVG((g.finalgrade/g.rawgrademax)*100) as grade
            FROM {role_assignments} ra
                LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                LEFT JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {user} u ON u.id = :userid2
                LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted > 0 AND cc.userid = ra.userid
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
                LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
            WHERE c.id IN (
                SELECT DISTINCT ctx.instanceid
                FROM {role_assignments} ra, {context} ctx
                WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1) $sql2
            GROUP BY c.id) x
        GROUP BY x.id", $params);
}
function intelliboard_instructor_courses($view, $page, $length)
{
    global $DB, $USER;

    $teacher_roles = get_config('local_intelliboard', 'filter10');
    $learner_roles = get_config('local_intelliboard', 'filter11');

    $params = array(
        'userid'=>$USER->id,
        'userid2'=>$USER->id
    );
    list($sql1, $params) = intelliboard_filter_in_sql($teacher_roles, "ra.roleid", $params);
    list($sql2, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    if($view == 'grades'){
        $courses = $DB->get_records_sql("
            SELECT
                c.id,
                c.fullname,
                AVG((g.finalgrade/g.rawgrademax)*100) AS data1,
                cc.gradepass as data2
            FROM {course} c
                LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.finalgrade IS NOT NULL
                LEFT JOIN {course_completion_criteria} cc ON cc.course = c.id AND cc.criteriatype = 6
            WHERE  c.id IN (
                SELECT DISTINCT ctx.instanceid
                FROM {role_assignments} ra, {context} ctx
                WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1)
            GROUP BY c.id HAVING data1 > 0", $params, $page, $length);
    }elseif($view == 'activities'){
        $courses = $DB->get_records_sql("
            SELECT
                c.id,
                c.fullname,
                (SELECT count(distinct ra.userid) as learners FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql2 AND ctx.instanceid = c.id) AS learners,
                COUNT(DISTINCT cmc.id) as data1,
                COUNT(DISTINCT cm.id) as data2
            FROM {course} c
                LEFT JOIN {course_modules} cm ON cm.course = c.id AND cm.visible = 1 AND cm.completion = 1
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate = 1
            WHERE c.id IN (
                SELECT DISTINCT ctx.instanceid
                FROM {role_assignments} ra, {context} ctx
                WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1)
            GROUP BY c.id HAVING data1 > 0", $params, $page, $length);

        foreach($courses as $course){
            $course->data1 = ($course->data2) ? ($course->data1 / ($course->learners * $course->data2)) * 100 : 0;
        }
    }else{
        $courses = $DB->get_records_sql("
            SELECT
                c.id,
                c.fullname,
                COUNT(DISTINCT ra.userid) as data1,
                COUNT(distinct cc.userid) as data2
            FROM {role_assignments} ra
                LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                LEFT JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.timecompleted > 0 AND cc.userid = ra.userid
            WHERE c.id IN (
                SELECT DISTINCT ctx.instanceid
                FROM {role_assignments} ra, {context} ctx
                WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid = :userid $sql1) $sql2
            GROUP BY ctx.instanceid HAVING data1 > 0", $params, $page, $length);
    }
    return $courses;
}
