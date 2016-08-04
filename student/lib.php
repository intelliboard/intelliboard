<?php
defined('MOODLE_INTERNAL') || die();

function intelliboard_data($type, $userid) {
    global $PAGE, $DB, $CFG, $USER;

    $data = array();
    $page = optional_param($type.'_page', 0, PARAM_INT);
    $search = optional_param('search', '', PARAM_TEXT);
    $t = optional_param('type', '', PARAM_RAW);
    $perpage = 10;
    $start = $page * $perpage;

    if($type == 'assignment'){
        $sql = ($search and $t == 'assignment') ? "AND (a.name LIKE '%$search%' OR c.fullname LIKE '%$search%')":"";
        if($USER->activity_courses){
             $sql .= " AND c.id = ".intval($USER->activity_courses);
        }if($USER->activity_time !== -1){
            list($timestart, $timefinish) = get_timerange($USER->activity_time);
            $sql .= " AND a.duedate BETWEEN $timestart AND $timefinish";
        }

        $data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS a.id, a.name, a.duedate, c.fullname, (g.finalgrade/g.rawgrademax)*100 as grade, cmc.completionstate FROM {$CFG->prefix}course c, {$CFG->prefix}assign a
            LEFT JOIN {$CFG->prefix}modules m ON m.name = 'assign'
            LEFT JOIN {$CFG->prefix}course_modules cm ON cm.module = m.id AND cm.instance = a.id
            LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $userid
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.itemmodule = m.name AND gi.iteminstance = a.id
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = $userid
        WHERE c.id = a.course AND cm.visible = 1 AND c.visible = 1 $sql ORDER BY a.duedate DESC LIMIT $start, $perpage");
    }elseif ($type == 'quiz') {
        $sql = ($search and $t == 'quiz') ? "AND (a.name LIKE '%$search%' OR c.fullname LIKE '%$search%')":"";
        if($USER->activity_courses){
             $sql .= " AND c.id = ".intval($USER->activity_courses);
        }if($USER->activity_time !== -1){
            list($timestart, $timefinish) = get_timerange($USER->activity_time);
            $sql .= " AND a.timeclose BETWEEN $timestart AND $timefinish";
        }
        $data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS a.id, a.name, a.timeclose, c.fullname, (g.finalgrade/g.rawgrademax)*100 as grade, cmc.completionstate FROM {course} c, {quiz} a
                LEFT JOIN {$CFG->prefix}modules m ON m.name = 'quiz'
                LEFT JOIN {$CFG->prefix}course_modules cm ON cm.module = m.id AND cm.instance = a.id
                LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $userid
                LEFT JOIN {$CFG->prefix}grade_items gi ON gi.itemmodule = m.name AND gi.iteminstance = a.id
                LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = $userid
                 WHERE c.id = a.course AND cm.visible = 1 AND c.visible = 1 $sql ORDER BY a.timeclose DESC LIMIT $start, $perpage");
    }elseif ($type == 'course') {
        $sql = ($search) ? "AND c.fullname LIKE '%$search%'":"";

        $data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS c.id, (g.finalgrade/g.rawgrademax)*100 AS grade, c.fullname, ue.timemodified, cc.timecompleted, m.modules, cm.completedmodules FROM
            {$CFG->prefix}user_enrolments ue
            LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
            LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
            LEFT JOIN {$CFG->prefix}course_completions cc ON cc.course = c.id AND cc.userid = ue.userid
            LEFT JOIN (SELECT course, count(id) as modules FROM {$CFG->prefix}course_modules WHERE visible = 1 AND completion = 1 GROUP BY course) m ON m.course = c.id
            LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completedmodules FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cm.id = cmc.coursemoduleid AND cmc.completionstate > 0 AND cm.visible = 1 AND cm.completion = 1 GROUP BY cm.course, cmc.userid) cm ON cm.course = c.id AND cm.userid = ue.userid
            LEFT JOIN (SELECT courseid, sum(timespend) AS duration FROM {$CFG->prefix}local_intelliboard_tracking WHERE userid = $userid AND courseid > 0 GROUP BY courseid ) l ON l.courseid = c.id
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = ue.userid
         WHERE ue.userid = $userid AND c.visible = 1 GROUP BY c.fullname $sql ORDER BY c.fullname LIMIT $start, $perpage");
    }elseif ($type == 'courses') {
        $sql = ($search) ? "AND c.fullname LIKE '%$search%'":"";

        $res = $DB->get_record_sql("SELECT COUNT(cm.id) as certificates FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m WHERE m.name = 'certificate' AND cm.module = m.id AND cm.visible = 1");
        $sql_select = "";
        $sql_join = "";
        if($res->certificates){
            $sql_select = ", ce.certificates";
            $sql_join = "LEFT JOIN (SELECT c.course, COUNT(ci.id) AS certificates FROM {$CFG->prefix}certificate c, {$CFG->prefix}certificate_issues ci WHERE c.id = ci.certificateid AND ci.userid = $userid GROUP BY c.course) ce ON ce.course = c.id";
        }else{
            $sql_select = ",'' as certificates";
        }

        $data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS c.id, (g.finalgrade/g.rawgrademax)*100 AS grade, gc.average, c.fullname, ca.name as category, ue.timemodified, cc.timecompleted, m.modules, cm.completedmodules, l.duration $sql_select FROM
            {$CFG->prefix}user_enrolments ue
            LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
            LEFT JOIN {$CFG->prefix}course_completions cc ON cc.course = c.id AND cc.userid = ue.userid
            LEFT JOIN (SELECT course, count(id) as modules FROM {$CFG->prefix}course_modules WHERE visible = 1 AND completion = 1 GROUP BY course) m ON m.course = c.id
            LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completedmodules FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cm.id = cmc.coursemoduleid AND cmc.completionstate > 0 AND cm.visible = 1 AND cm.completion = 1 GROUP BY cm.course, cmc.userid) cm ON cm.course = c.id AND cm.userid = ue.userid
            LEFT JOIN (SELECT courseid, sum(timespend) AS duration FROM {$CFG->prefix}local_intelliboard_tracking WHERE userid = $userid AND courseid > 0 GROUP BY courseid ) l ON l.courseid = c.id
            LEFT JOIN {course_categories} ca ON ca.id = c.category
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = ue.userid
            LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id
            $sql_join
         WHERE ue.userid = $userid AND c.visible = 1 $sql GROUP BY c.id ORDER BY c.fullname LIMIT $start, $perpage");
    }

    $size = $DB->get_records_sql("SELECT FOUND_ROWS()");
    $count = key($size);
    $pagination = get_pagination($count, $page, $perpage, $type);

    return array("pagination"=>$pagination, "data"=>$data);
}

function get_timerange($time){
    if($time == 0){
        $timestart = strtotime('-1 week');
        $timefinish = time();
    }elseif($time == 1){
        $timestart = strtotime('-1 month');
        $timefinish = time();
    }elseif($time == 2){
        $timestart = strtotime('-4 month');
        $timefinish = time();
    }elseif($time == 3){
        $timestart = strtotime('-6 month');
        $timefinish = time();
    }elseif($time == 4){
        $timestart = strtotime('-1 year');
        $timefinish = time();
    }else{
        $timestart = strtotime('-14 days');
        $timefinish = time();
    }
    return array($timestart,$timefinish);
}
function get_pagination($count = 0, $page = 0, $perpage = 15, $type = 'intelliboard') {
    global $CFG, $OUTPUT, $PAGE;

    $pages = (int)ceil($count/$perpage);
    if ($pages == 1 || $pages == 0) {
        return '';
    }
    $link = new moodle_url($PAGE->url, array());
    return $OUTPUT->paging_bar($count, $page, $perpage, $link, $type.'_page');
}

function intelliboard_learner_course_progress($courseid, $userid){
    global $CFG, $DB;

    $timestart = strtotime('-30 days');
    $timefinish = time();

    $data = array();
    $data[] = $DB->get_records_sql("SELECT floor(g.timemodified / 86400) * 86400 as timepoint, AVG((g.finalgrade/g.rawgrademax)*100) as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.id = g.itemid AND g.userid = $userid AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL AND g.timemodified BETWEEN $timestart AND $timefinish AND gi.courseid = $courseid GROUP BY floor(g.timemodified / 86400) * 86400 ORDER BY g.timemodified");

    $data[] = $DB->get_records_sql("SELECT floor(g.timemodified / 86400) * 86400 as timepoint, AVG((g.finalgrade/g.rawgrademax)*100) as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.id = g.itemid AND g.userid != $userid AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL AND g.timemodified BETWEEN $timestart AND $timefinish AND gi.courseid = $courseid GROUP BY floor(g.timemodified / 86400) * 86400 ORDER BY g.timemodified");
    return $data;
}
function intelliboard_learner_progress($time, $userid){
    global $CFG, $DB;

    list($timestart, $timefinish) = get_timerange($time);

    $data = array();
    $data[] = $DB->get_records_sql("SELECT floor(g.timemodified / 86400) * 86400 as timepoint, AVG((g.finalgrade/g.rawgrademax)*100) as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.id = g.itemid AND g.userid = $userid AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL AND g.timemodified BETWEEN $timestart AND $timefinish GROUP BY floor(g.timemodified / 86400) * 86400 ORDER BY g.timemodified");

    $data[] = $DB->get_records_sql("SELECT floor(g.timemodified / 86400) * 86400 as timepoint, AVG((g.finalgrade/g.rawgrademax)*100) as grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.id = g.itemid AND g.userid != $userid AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL AND g.timemodified BETWEEN $timestart AND $timefinish GROUP BY floor(g.timemodified / 86400) * 86400 ORDER BY g.timemodified");
    return $data;
}
function intelliboard_learner_courses($userid){
     global $CFG, $DB;

    $data = $DB->get_records_sql("SELECT c.id, (g.finalgrade/g.rawgrademax)*100 AS grade, gc.average, c.fullname, l.duration, '0' AS duration_calc FROM
            {$CFG->prefix}user_enrolments ue
            LEFT JOIN {$CFG->prefix}enrol e ON e.id = ue.enrolid
            LEFT JOIN {$CFG->prefix}course c ON c.id = e.courseid
            LEFT JOIN (SELECT courseid, sum(timespend) AS duration FROM {$CFG->prefix}local_intelliboard_tracking WHERE userid = $userid AND courseid > 0 GROUP BY courseid ) l ON l.courseid = c.id
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = ue.userid
            LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id
         WHERE ue.userid = $userid AND c.visible = 1 GROUP BY c.id");
        $d = 0;
        foreach($data as $c){
            $d = ($c->duration > $d)?$c->duration:$d;
        }
        if($d){
            foreach($data as $c){
                $c->duration_calc =  (intval($c->duration)/$d)*100;
            }
        }
    return $data;
}

function intelliboard_learner_totals($userid){
    global $CFG, $DB;

    return $DB->get_record_sql("SELECT
    (SELECT count(distinct e.courseid) FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE e.status = 0 AND ue.status = 0 AND ue.userid = $userid AND e.id = ue.enrolid) AS enrolled,
    (SELECT count(id) FROM {$CFG->prefix}message WHERE useridto = $userid) AS messages,
    (SELECT count(distinct course) FROM {$CFG->prefix}course_completions WHERE userid = $userid AND timecompleted > 0) AS completed,
    (SELECT count(distinct e.courseid) FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE e.status = 0 AND ue.status = 0 AND ue.userid = $userid AND e.id = ue.enrolid AND (e.courseid IN (SELECT distinct cm.course FROM {$CFG->prefix}course_modules_completion cmc, {$CFG->prefix}course_modules cm WHERE cmc.coursemoduleid = cm.id and cmc.userid =$userid) OR e.courseid IN (SELECT distinct gi.courseid FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE g.userid = $userid AND g.finalgrade IS NOT NULL AND gi.id = g.itemid)) AND e.courseid NOT IN (SELECT distinct course FROM {$CFG->prefix}course_completions WHERE userid = $userid AND timecompleted > 0)) as inprogress,
    (SELECT AVG((g.finalgrade/g.rawgrademax)*100) FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.userid != $userid AND g.finalgrade IS NOT NULL AND gi.id = g.itemid AND gi.courseid IN (SELECT count(distinct e.courseid) FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e WHERE e.status = 0 AND ue.status = 0 AND ue.userid = $userid AND e.id = ue.enrolid)) as average,
    (SELECT AVG((g.finalgrade/g.rawgrademax)*100) FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.userid = $userid AND g.finalgrade IS NOT NULL AND gi.id = g.itemid) as grade");
}
function intelliboard_learner_course($userid, $courseid){
    global $CFG, $DB;

    return $DB->get_record_sql("SELECT c.id, c.fullname, ul.timeaccess, c.enablecompletion, cc.timecompleted, (g.finalgrade/g.rawgrademax)*100 AS grade
        FROM {$CFG->prefix}course c
            LEFT JOIN {$CFG->prefix}user_lastaccess ul ON ul.courseid = c.id AND ul.userid = $userid
            LEFT JOIN {$CFG->prefix}course_completions cc ON cc.course = c.id AND cc.userid = $userid
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = $userid
     WHERE c.id = $courseid");
}
function intelliboard_learner_modules($userid){
    global $CFG, $DB;

    return $DB->get_records_sql("SELECT m.id, m.name, count(distinct cm.id) as modules , count(distinct cmc.id) as completed_modules, count(distinct l.id) as start_modules, sum(l.timespend) as duration
        FROM {$CFG->prefix}modules m, {$CFG->prefix}course_modules cm
        LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = $userid AND cmc.completionstate > 0
        LEFT JOIN {$CFG->prefix}local_intelliboard_tracking l ON l.page = 'module' AND l.userid = $userid AND l.param = cm.id
        WHERE cm.visible = 1 AND cm.module = m.id and cm.course IN (SELECT distinct e.courseid FROM {$CFG->prefix}enrol e, {$CFG->prefix}user_enrolments ue WHERE ue.userid = $userid AND e.id = ue.enrolid) GROUP BY m.id");
}
