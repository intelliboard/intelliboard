<?php
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/gradelib.php');

class intelliboard_courses_grades_table extends table_sql {

    function __construct($uniqueid, $userid = 0, $search = '') {
        global $CFG, $PAGE;

        parent::__construct($uniqueid);

        $headers = array('Course Name');
        $columns = array('course');
        if(get_config('local_intelliboard', 't23')){
            $columns[] =  'timemodified';
            $headers[] =  'Course start date';
        }if(get_config('local_intelliboard', 't24')){
           $columns[] =  'startdate';
           $headers[] =  'Enrolled date';
        }if(get_config('local_intelliboard', 't25')){
           $columns[] =  'average';
           $headers[] =  'Progress';
        }if(get_config('local_intelliboard', 't26')){
           $columns[] =  'letter';
           $headers[] =  'Letter';
        }if(get_config('local_intelliboard', 't27')){
           $columns[] =  'completedmodules';
           $headers[] =  'Completed Activities';
        }if(get_config('local_intelliboard', 't28')){
           $columns[] =  'grade';
           $headers[] =  'Score';
        }if(get_config('local_intelliboard', 't28')){
           $columns[] =  'timecompleted';
           $headers[] =  'Course Completion Status';
        }
        $columns[] =  'actions';
        $headers[] =  'Activity Grades';

        $this->define_headers($headers);
        $this->define_columns($columns);

        $sql = ($search) ? "AND c.fullname LIKE '%$search%'":"";

        $fields = "c.id, c.fullname as course, c.timemodified, c.startdate, c.enablecompletion, cri.gradepass, (g.finalgrade/g.rawgrademax)*100 AS grade, gc.average, cc.timecompleted, m.modules, cm.completedmodules, '' as actions, '' as letter";

        $from = "(SELECT DISTINCT c.id, c.fullname, c.startdate, c.enablecompletion, ue.timemodified, ue.userid FROM {$CFG->prefix}user_enrolments ue, {$CFG->prefix}enrol e, {$CFG->prefix}course c WHERE ue.userid = $userid AND ue.status = 0 AND e.id = ue.enrolid AND e.status = 0 AND c.id = e.courseid AND c.visible = 1) c

            LEFT JOIN {$CFG->prefix}course_completions cc ON cc.course = c.id AND cc.userid = c.userid
            LEFT JOIN (SELECT course, count(id) as modules FROM {$CFG->prefix}course_modules WHERE visible = 1 AND completion = 1 GROUP BY course) m ON m.course = c.id
            LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completedmodules FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cm.id = cmc.coursemoduleid AND cmc.completionstate > 0 AND cm.visible = 1 AND cm.completion = 1 GROUP BY cm.course, cmc.userid) cm ON cm.course = c.id AND cm.userid = c.userid
            LEFT JOIN {$CFG->prefix}course_completion_criteria as cri ON cri.course = c.id AND cri.criteriatype = 6
            LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = c.userid
            LEFT JOIN (SELECT gi.courseid, AVG( (g.finalgrade/g.rawgrademax)*100) AS average FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id";

        $where = "c.id > 0 $sql";

        $this->set_sql($fields, $from, $where, array());
        $this->define_baseurl($PAGE->url);
    }
    function col_average($values) {
        $gade = intval($values->grade);
        $average = intval($values->average);
        $goal = intval($values->gradepass);

        $html = html_writer::start_tag("div",array("class"=>"info-progress","title"=>"Current grade:$gade | Class avg:$average | Goal Grade:$goal"));
        $html .= html_writer::tag("span", "Current grade:$gade |", array("class"=>"current","style"=>"width:$gade%"));
        if($average and get_config('local_intelliboard', 't40')){
            $html .= html_writer::tag("span", "Class avg:$average |", array("class"=>"average","style"=>"width:$average%"));
        }
        if($goal and get_config('local_intelliboard', 't40')){
            $html .= html_writer::tag("span", "Goal Grade:$goal", array("class"=>"goal","style"=>"width:$goal%"));
        }
        $html .= html_writer::end_tag("div");
        return $html;
    }

    function col_startdate($values) {
        return  ($values->startdate) ? date('m/d/Y', $values->startdate) : "";
    }
    function col_timecompleted($values) {
        if(!$values->enablecompletion){
            return "Completion is not enabled for this course";
        }
        return  ($values->timecompleted) ? "Completed on ".date('m/d/Y', $values->timemodified) : "Incomplete";
    }
    function col_grade($values) {
        $html = html_writer::start_tag("div",array("class"=>"grade"));
        $html .= html_writer::tag("div", "", array("class"=>"circle-progress", "data-percent"=>(int)$values->grade));
        $html .= html_writer::end_tag("div");
        return $html;
    }
    function col_completedmodules($values) {
        return intval($values->completedmodules)."/".intval($values->modules);
    }
    function col_letter($values) {
        $letter = '';
        $context = context_course::instance($values->id,IGNORE_MISSING);
        $letters = grade_get_letters($context);
        foreach($letters as $lowerboundary=>$value){
            if($values->grade >= $lowerboundary){
                $letter = $value;
                break;
            }
        }
        return $letter;
    }
    function col_timemodified($values) {
      return ($values->timemodified) ? date('m/d/Y', $values->timemodified) : '';
    }
    function col_course($values) {
        global $CFG, $PAGE;

        return html_writer::link(new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$values->id)), $values->course, array("target"=>"_blank"));
    }
    function col_actions($values) {
        global $CFG, $PAGE;

        return html_writer::link(new moodle_url($PAGE->url, array('id'=>$values->id)), 'Activities', array('class' =>'btn'));
    }
}

class intelliboard_activities_grades_table extends table_sql {

    function __construct($uniqueid, $userid = 0, $courseid = 0, $search = '') {
        global $CFG, $PAGE;

        parent::__construct($uniqueid);

        $columns = array('itemname');
        $headers = array('Activity name');

        if(get_config('local_intelliboard', 't43')){
            $columns[] =  'itemmodule';
            $headers[] =  'Type';
        }if(get_config('local_intelliboard', 't44')){
            $columns[] =  'grade';
            $headers[] =  'Score';
        }if(get_config('local_intelliboard', 't45')){
            $columns[] =  'timepoint';
            $headers[] =  'Graded';
        }if(get_config('local_intelliboard', 't46')){
            $columns[] =  'timecompleted';
            $headers[] =  'Type';
        }

        $this->define_headers($headers);
        $this->define_columns($columns);

        $sql = ($search) ? "AND gi.itemname LIKE '%$search%'":"";

        $fields = "gi.id, gi.itemname, cm.id as cmid, gi.itemmodule, cmc.timemodified as timecompleted, (g.finalgrade/g.rawgrademax)*100 AS grade, IFNULL(g.timemodified, g.timecreated)  as timepoint";
        $from = "{$CFG->prefix}grade_items gi
            LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid = $userid
            LEFT JOIN {$CFG->prefix}modules m ON m.name = gi.itemmodule
            LEFT JOIN {$CFG->prefix}course_modules cm ON cm.instance = gi.iteminstance AND cm.module = m.id
            LEFT JOIN {$CFG->prefix}course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.completionstate = 1 AND cmc.userid = $userid";
        $where = "gi.courseid = $courseid AND gi.itemtype = 'mod' AND cm.visible = 1 $sql";

        $this->set_sql($fields, $from, $where, array());
        $this->define_baseurl($PAGE->url);
    }

    function col_grade($values) {
        $html = html_writer::start_tag("div",array("class"=>"grade"));
        $html .= html_writer::tag("div", "", array("class"=>"circle-progress", "data-percent"=>(int)$values->grade));
        $html .= html_writer::end_tag("div");
        return $html;
    }


    function col_timecompleted($values) {
      return ($values->timecompleted) ? "Completed on ".date('m/d/Y', $values->timecompleted) : "Incomplete";
    }
    function col_timepoint($values) {
      return ($values->timepoint) ? date('m/d/Y', $values->timepoint) : '';
    }
    function col_itemname($values) {
        global $CFG, $PAGE;

        return html_writer::link(new moodle_url("$CFG->wwwroot/mod/$values->itemmodule/view.php", array('id'=>$values->cmid)), $values->itemname, array("target"=>"_blank"));
    }
}
