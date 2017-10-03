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
 * @website    http://intelliboard.net/
 */

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/gradelib.php');

class intelliboard_courses_grades_table extends table_sql {

    function __construct($uniqueid, $userid = 0, $search = '') {
        global $PAGE, $DB;

        parent::__construct($uniqueid);

        $headers = array('Course Name');
        $columns = array('course');
        if(get_config('local_intelliboard', 't23')){
            $columns[] =  'startdate';
            $headers[] =  get_string('course_start_date', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't24')){
           $columns[] =  'timemodified';
           $headers[] =  get_string('enrolled_date', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't25')){
           $columns[] =  'average';
           $headers[] =  get_string('progress', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't26')){
           $columns[] =  'letter';
           $headers[] =  get_string('letter', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't27')){
           $columns[] =  'completedmodules';
           $headers[] =  get_string('completed_activities', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't28')){
           $columns[] =  'grade';
           $headers[] =  get_string('score', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't28')){
           $columns[] =  'timecompleted';
           $headers[] =  get_string('course_completion_status', 'local_intelliboard');
        }
        $columns[] =  'actions';
        $headers[] =  get_string('activity_grades', 'local_intelliboard');

        $this->define_headers($headers);
        $this->define_columns($columns);

        $params = array('userid'=>$userid);
        $sql = "";
        if($search){
            $sql .= " AND " . $DB->sql_like('c.fullname', ":fullname", false, false);
            $params['fullname'] = "%$search%";
        }
        $grade_single = intelliboard_grade_sql();
        $grade_avg = intelliboard_grade_sql(true);
        $completion = intelliboard_compl_sql("cmc.");

        $fields = "c.id, c.fullname as course, c.timemodified, c.startdate, c.enablecompletion, cri.gradepass, $grade_single AS grade, gc.average, cc.timecompleted, m.modules, cm.completedmodules, '' as actions, '' as letter";

        $from = "(SELECT DISTINCT c.id, c.fullname, c.startdate, c.enablecompletion, MIN(ue.timemodified) AS timemodified, ue.userid FROM {user_enrolments} ue, {enrol} e, {course} c WHERE ue.userid = :userid  AND ue.status = 0 AND e.id = ue.enrolid AND e.status = 0 AND c.id = e.courseid AND c.visible = 1 GROUP BY c.id, ue.userid) c

            LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = c.userid
            LEFT JOIN (SELECT course, count(id) as modules FROM {course_modules} WHERE visible = 1 AND completion > 0 GROUP BY course) m ON m.course = c.id
            LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completedmodules FROM {course_modules} cm, {course_modules_completion} cmc WHERE cm.id = cmc.coursemoduleid $completion AND cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course, cmc.userid) cm ON cm.course = c.id AND cm.userid = c.userid
            LEFT JOIN {course_completion_criteria} as cri ON cri.course = c.id AND cri.criteriatype = 6
            LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = c.userid
            LEFT JOIN (SELECT gi.courseid, $grade_avg AS average FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) as gc ON gc.courseid = c.id";
        $where = "c.id > 0 $sql";
        $this->set_sql($fields, $from, $where, $params);
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
            return get_string('completion_is_not_enabled', 'local_intelliboard');
        }
        return  ($values->timecompleted) ? get_string('completed_on', 'local_intelliboard', date('m/d/Y', $values->timemodified)) : get_string('incomplete', 'local_intelliboard');
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
        global $CFG;

        return html_writer::link(new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$values->id)), format_string($values->course), array("target"=>"_blank"));
    }
    function col_actions($values) {
        global $PAGE;

        return html_writer::link(new moodle_url($PAGE->url, array('id'=>$values->id)), get_string('activities', 'local_intelliboard'), array('class' =>'btn'));
    }
}

class intelliboard_activities_grades_table extends table_sql {

    function __construct($uniqueid, $userid = 0, $courseid = 0, $search = '') {
        global $PAGE, $DB;

        parent::__construct($uniqueid);

        $columns = array('itemname');
        $headers = array(get_string('activity_name', 'local_intelliboard'));

        if(get_config('local_intelliboard', 't43')){
            $columns[] =  'itemmodule';
            $headers[] =  get_string('type', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't44')){
            $columns[] =  'grade';
            $headers[] =  get_string('score', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't45')){
            $columns[] =  'timepoint';
            $headers[] =  get_string('graded', 'local_intelliboard');
        }if(get_config('local_intelliboard', 't46')){
            $columns[] =  'timecompleted';
            $headers[] =  get_string('type', 'local_intelliboard');
        }

        $this->define_headers($headers);
        $this->define_columns($columns);

        $sql = "";
        $params = array();
        if($search){
            $sql .= " AND " . $DB->sql_like('gi.itemname', ":itemname", false, false);
            $params['itemname'] = "%$search%";
        }
        $params['userid1'] = $userid;
        $params['userid2'] = $userid;
        $params['courseid'] = $courseid;

        $grade_single = intelliboard_grade_sql();
        $completion = intelliboard_compl_sql("cmc.");

        $fields = "gi.id, gi.itemname, cm.id as cmid, gi.itemmodule, cmc.timemodified as timecompleted, $grade_single AS grade,
            CASE WHEN g.timemodified > 0 THEN g.timemodified ELSE g.timecreated END AS timepoint";
        $from = "{grade_items} gi
            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = :userid1
            LEFT JOIN {modules} m ON m.name = gi.itemmodule
            LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
            LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion AND cmc.userid = :userid2";
        $where = "gi.courseid = :courseid AND gi.itemtype = 'mod' AND cm.visible = 1 $sql";

        $this->set_sql($fields, $from, $where, $params);
        $this->define_baseurl($PAGE->url);
    }

    function col_grade($values) {
        $html = html_writer::start_tag("div",array("class"=>"grade"));
        $html .= html_writer::tag("div", "", array("class"=>"circle-progress", "data-percent"=>(int)$values->grade));
        $html .= html_writer::end_tag("div");
        return $html;
    }


    function col_timecompleted($values) {
      return ($values->timecompleted) ? get_string('completed_on', 'local_intelliboard', date('m/d/Y', $values->timecompleted)) : get_string('incomplete', 'local_intelliboard');
    }
    function col_timepoint($values) {
      return ($values->timepoint) ? date('m/d/Y', $values->timepoint) : '';
    }
    function col_itemname($values) {
        global $CFG;

        return html_writer::link(new moodle_url("$CFG->wwwroot/mod/$values->itemmodule/view.php", array('id'=>$values->cmid)), format_string($values->itemname), array("target"=>"_blank"));
    }
}
