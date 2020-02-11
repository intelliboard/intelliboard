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

function intelliboard_competency_access()
{
    global $USER;

    if (!get_capability_info('moodle/competency:competencyview')) {
        throw new moodle_exception('no_competency', 'local_intelliboard');
    }
    if (!get_config('local_intelliboard', 'competency_dashboard')) {
        throw new moodle_exception('invalidaccess', 'error');
    }
    if (is_siteadmin()) {
        return true;
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
    return true;
}

function intelliboard_competency_courses()
{
    global $DB, $USER;

    $sql = ""; $params = array();
    if (!is_siteadmin()) {
        list($sql_roles, $params) = $DB->get_in_or_equal(explode(',', get_config('local_intelliboard', 'filter10')), SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $USER->id;
        $sql = " AND cc.courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles AND ra.userid = :userid GROUP BY ctx.instanceid)";
    }

    return $DB->get_records_sql("SELECT c.id, c.shortname, COUNT(DISTINCT cc.courseid) AS courses
        FROM {competency} c, {competency_coursecomp} cc
        WHERE c.id = cc.competencyid $sql
        GROUP BY c.id", $params);
}

function intelliboard_competencies_progress($cohortid = [])
{
    global $DB, $USER;

    $sql1 = "";
    $sql2 = "";
    $sql3 = "";
    $params = array(
        'userid1' => $USER->id,
        'userid2' => $USER->id,
        'userid3' => $USER->id
    );

    $cohortmembersjoin = \local_intelliboard\helpers\SQLEntityHelper::cohortMembersJoin($USER->id, "cu.userid", $cohortid);

    if (!is_siteadmin()) {
        $roles = explode(',', get_config('local_intelliboard', 'filter10'));

        list($sql_roles1, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r1');
        $params = array_merge($params,$sql_params);

        $sql1 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles1 AND ra.userid = :userid1 GROUP BY ctx.instanceid)";

        list($sql_roles2, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r2');
        $params = array_merge($params,$sql_params);

        $sql2 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles2 AND ra.userid = :userid2 GROUP BY ctx.instanceid)";

        list($sql_roles3, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r3');
        $params = array_merge($params,$sql_params);

        $sql3 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles3 AND ra.userid = :userid3 GROUP BY ctx.instanceid)";
    }

    return $DB->get_records_sql(
        "SELECT c.id, c.shortname,
                (SELECT count(id)
                   FROM {competency_usercompcourse} cu
                   {$cohortmembersjoin}
                  WHERE competencyid = c.id AND proficiency = 1 {$sql1}
                ) AS proficient,
                (SELECT count(id)
                   FROM {competency_usercompcourse} cu
                   {$cohortmembersjoin}
                  WHERE competencyid = c.id AND proficiency = 0 AND grade IS NOT NULL {$sql2}
                ) AS unproficient,
                (SELECT count(id)
                   FROM {competency_usercompcourse} cu
                   {$cohortmembersjoin}
                  WHERE competencyid = c.id AND grade IS NULL {$sql3}
                ) AS unrated
           FROM {competency} c",
        $params
    );
}
function intelliboard_competencies_total($cohortid = [])
{
    global $DB, $USER;

    $sql1 = "";
    $sql2 = "";
    $sql3 = "";
    $sql4 = "";
    $params = array(
        'userid1' => $USER->id,
        'userid2' => $USER->id,
        'userid3' => $USER->id,
        'userid4' => $USER->id
    );

    $cohortmembersjoin = \local_intelliboard\helpers\SQLEntityHelper::cohortMembersJoin($USER->id, "cu.userid", $cohortid);

    if (!is_siteadmin()) {
        $roles = explode(',', get_config('local_intelliboard', 'filter10'));

        list($sql_roles1, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r1');
        $params = array_merge($params,$sql_params);

        $sql1 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles1 AND ra.userid = :userid1 GROUP BY ctx.instanceid)";

        list($sql_roles2, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r2');
        $params = array_merge($params,$sql_params);

        $sql2 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles2 AND ra.userid = :userid2 GROUP BY ctx.instanceid)";

        list($sql_roles3, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r3');
        $params = array_merge($params,$sql_params);

        $sql3 = " AND courseid IN (SELECT ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles3 AND ra.userid = :userid3 GROUP BY ctx.instanceid)";

        list($sql_roles4, $sql_params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r3');
        $params = array_merge($params,$sql_params);

        $sql4 = " WHERE id IN (SELECT DISTINCT cc.competencyid FROM {role_assignments} ra, {context} ctx, {competency_coursecomp} cc WHERE cc.courseid = ctx.instanceid AND ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.roleid $sql_roles4 AND ra.userid = :userid4)";
    }

    return $DB->get_record_sql("
        SELECT
            (SELECT count(id) FROM {competency} $sql4) AS competencies,
            (SELECT count(id) FROM {competency_framework}) AS frameworks,
            (SELECT count(id) FROM {competency_plan}) AS plans,
            (SELECT count(id) FROM {competency_usercompcourse} cu {$cohortmembersjoin} WHERE proficiency = 1 $sql1) AS proficient,
            (SELECT count(id) FROM {competency_usercompcourse} cu {$cohortmembersjoin} WHERE proficiency = 0 AND grade IS NOT NULL $sql2) AS unproficient,
            (SELECT count(id) FROM {competency_usercompcourse} cu {$cohortmembersjoin} WHERE grade IS NULL $sql3) AS unrated",
        $params
    );
}
function intelliboard_competency_frameworks()
{
    global $DB;

    return $DB->get_records_sql("SELECT cf.id, cf.shortname, COUNT(c.id) AS competencies
        FROM
            {competency_framework} cf
            LEFT JOIN {competency} c ON c.competencyframeworkid = cf.id
        WHERE cf.id > 0
        GROUP BY cf.id");
}

function intelliboard_course_total($courseid)
{
    global $DB;

    $params = array('courseid' => $courseid);
    $learner_roles = get_config('local_intelliboard', 'filter11');

    list($sql, $params) = intelliboard_filter_in_sql($learner_roles, "ra.roleid", $params);

    return $DB->get_record_sql("
        SELECT c.id,c.fullname, c.startdate, c.enablecompletion, ca.name AS category,
            (SELECT COUNT(DISTINCT cu.id) FROM {competency_usercompcourse} cu WHERE cu.courseid = c.id AND cu.proficiency = 1) AS proficiency,
            (SELECT COUNT(DISTINCT cc.competencyid) FROM {competency_coursecomp} cc WHERE cc.courseid = c.id) AS competencies,
            (SELECT COUNT(DISTINCT ra.userid) FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql AND ctx.instanceid = c.id) AS learners
        FROM {course} c
            LEFT JOIN {course_categories} ca ON ca.id = c.category
        WHERE c.id = :courseid LIMIT 1", $params);
}
function intelliboard_learners_total($courseid, $competencyid)
{
    global $DB;

    $params = array(
        'competencyid' => $competencyid,
        'courseid' => $courseid
    );

    return $DB->get_record_sql("
        SELECT c.id, co.fullname AS course, cc.courseid, c.shortname, c.description, c.idnumber, c.timecreated AS created, cc.timecreated AS asigned,
            (SELECT COUNT(DISTINCT cu.id) FROM {competency_usercompcourse} cu WHERE cu.competencyid = c.id AND cu.courseid = cc.courseid AND cu.proficiency = 1) AS proficient,
            (SELECT COUNT(DISTINCT cu.id) FROM {competency_usercompcourse} cu WHERE cu.competencyid = c.id AND cu.courseid = cc.courseid AND cu.grade IS NOT NULL) AS rated,
            (SELECT COUNT(DISTINCT m.cmid) FROM {course_modules} cm, {competency_modulecomp} m WHERE cm.visible = 1 AND m.cmid = cm.id AND cm.course = cc.courseid AND  m.competencyid = cc.competencyid) AS activities
        FROM {competency_coursecomp} cc
            LEFT JOIN {competency} c ON c.id = cc.competencyid
            LEFT JOIN {course} co ON co.id = cc.courseid
        WHERE c.id = :competencyid AND cc.courseid = :courseid LIMIT 1", $params);
}

function intelliboard_learner_total($userid, $courseid)
{
    global $DB;

    $params = array(
        'userid' => $userid,
        'courseid' => $courseid
    );

    return $DB->get_record_sql("SELECT u.id, ctx.instanceid AS courseid, co.fullname AS course,
            (SELECT COUNT(DISTINCT comp.id)
                FROM {competency_coursecomp} coursecomp
                    JOIN {competency} comp ON coursecomp.competencyid = comp.id
                WHERE coursecomp.courseid = ctx.instanceid) AS competencycount,
            (SELECT COUNT(DISTINCT cu.competencyid)
                FROM {competency_usercompcourse} cu WHERE cu.courseid = ctx.instanceid AND cu.userid = u.id AND cu.proficiency = 1) AS proficientcompetencycount,
            (SELECT COUNT(DISTINCT cu.id) FROM {competency_usercompcourse} cu WHERE cu.courseid = ctx.instanceid AND cu.userid = u.id AND cu.grade IS NOT NULL) AS users_rated
            FROM {role_assignments} ra
            LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
            LEFT JOIN {course} co ON co.id = ctx.instanceid
            LEFT JOIN {user} u ON u.id = ra.userid
        WHERE ctx.instanceid = :courseid AND ra.userid = :userid LIMIT 1", $params);
}

