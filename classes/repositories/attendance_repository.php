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
 * @package    local_intelliboard
 * @copyright  2019 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intelliboard\repositories;

use local_intelliboard\reports\entities\in_filter;
use local_intelliboard\attendance\reports\report;
use local_intelliboard\reports\report_trait;
use moodle_url;
use user_picture;
use context_system;

class attendance_repository
{
    use report_trait;

    /**
     * Get all courses
     *
     * @param array $params
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_courses($params) {
        global $DB;

        if(isset($params['report_params'])) {
            $reportparams = json_decode($params['report_params'], true);
        } else {
            $reportparams = [];
        }

        $select = "c.*";
        $from = "{course} c";
        $where = "c.id <> 1";
        $sqlarguments = [];
        $limit = 0;
        $offset = 0;

        if(isset($reportparams['limit'])) {
            $limit = $reportparams['limit'];
        }

        if(isset($reportparams['offset'])) {
            $offset = $reportparams['offset'];
        }

        if(isset($reportparams['search'])) {
            $where .= ' AND ' . $DB->sql_like('c.fullname', ':search1', false);
            $sqlarguments['search1'] = "%{$reportparams['search']}%";
        }

        if(intval($params['userid'])) {
            $isteacherfilter = new in_filter($this->get_teacher_roles(), "role");

            $select .= ", MAX(CASE WHEN ra.roleid {$isteacherfilter->get_sql()}
                               THEN 1
                               ELSE 0
                           END) AS is_teacher";
            $from .= " JOIN {context} cx ON cx.instanceid = c.id AND
                                            cx.contextlevel = :coursecx
                       JOIN {role_assignments} ra ON ra.userid = :userid AND
                                                     ra.contextid = cx.id";
            $sqlarguments['coursecx'] = CONTEXT_COURSE;
            $sqlarguments['userid'] = $params['userid'];
            $sqlarguments = array_merge($sqlarguments, $isteacherfilter->get_params());

            if($params['role'] == 'student') {
                $rolefilter = new in_filter($this->get_student_roles(), "studrole");
                $from .= " AND ra.roleid {$rolefilter->get_sql()}";
                $sqlarguments = array_merge($sqlarguments, $rolefilter->get_params());
            } else if($params['role'] == 'teacher') {
                $rolefilter = new in_filter($this->get_teacher_roles(), "trole");
                $from .= " AND ra.roleid {$rolefilter->get_sql()}";
                $sqlarguments = array_merge($sqlarguments, $rolefilter->get_params());
            }
        }

        return $DB->get_records_sql(
            "SELECT {$select} FROM {$from} WHERE {$where} GROUP BY c.id", $sqlarguments,
            $offset, $limit
        );
    }

    /**
     * Get course by ID
     *
     * @param array $params Params
     * @return object
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course($params) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

        $rolefilter = new in_filter($this->get_teacher_roles(), "trole");
        $teachersSelect = get_operator(
            'GROUP_CONCAT',
            "CONCAT(u.firstname, ' ', u.lastname)",
            ['separator' => ', ']
        );

        $course = $DB->get_record_sql(
            "SELECT c.*,
                    {$teachersSelect} as teachers
               FROM {course} c
               JOIN {context} cx ON cx.instanceid = c.id AND
                                    cx.contextlevel = :coursecx
          LEFT JOIN {role_assignments} ra ON ra.roleid {$rolefilter->get_sql()} AND
                                             ra.contextid = cx.id
          LEFT JOIN {user} u ON u.id = ra.userid
              WHERE c.id = :courseid
           GROUP BY c.id",
            array_merge(['courseid' => $params['courseid'], 'coursecx' => CONTEXT_COURSE], $rolefilter->get_params())
        );

        if($course) {
            $course->url = (
            new moodle_url('/course/view.php', ['id' => $course->id])
            )->out();
        } else {
            return new \stdClass();
        }

        return $course;
    }

    /**
     * Check that user is teacher of course
     *
     * @param array $params Params
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function is_teacher($params) {
        global $DB;

        $rolefilter = new in_filter($this->get_teacher_roles(), "role");

        return $DB->record_exists_sql(
            "SELECT ra.*
               FROM {context} cx
               JOIN {role_assignments} ra ON ra.userid = :userid AND
                                             ra.contextid = cx.id AND
                                             ra.roleid {$rolefilter->get_sql()}
              WHERE cx.instanceid = :courseid AND
                    cx.contextlevel = :coursecontext",
            array_merge([
                'courseid' => $params['courseid'],
                'coursecontext' => CONTEXT_COURSE,
                'userid' => $params['userid'],
            ], $rolefilter->get_params())
        );

    }

    /**
     * Check that user is student of course
     *
     * @param array $params Params
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function is_student($params) {
        global $DB;

        $rolefilter = new in_filter($this->get_student_roles(), "srole");

        return $DB->record_exists_sql(
            "SELECT ra.*
               FROM {context} cx
               JOIN {role_assignments} ra ON ra.userid = :userid AND
                                             ra.contextid = cx.id AND
                                             ra.roleid {$rolefilter->get_sql()}
              WHERE cx.instanceid = :courseid AND
                    cx.contextlevel = :coursecontext",
            array_merge([
                'courseid' => $params['courseid'],
                'coursecontext' => CONTEXT_COURSE,
                'userid' => $params['userid'],
            ], $rolefilter->get_params())
        );

    }

    /**
     * Check that user is a participant of course
     *
     * @param array $params Params
     * @return bool
     * @throws \dml_exception
     */
    public function is_course_participant($params) {
        global $DB;

        return $DB->record_exists_sql(
            "SELECT ra.*
               FROM {context} cx
               JOIN {role_assignments} ra ON ra.userid = :userid AND
                                             ra.contextid = cx.id
              WHERE cx.instanceid = :courseid AND
                    cx.contextlevel = :coursecontext",
            [
                'courseid' => $params['courseid'],
                'coursecontext' => CONTEXT_COURSE,
                'userid' => $params['userid'],
            ]
        );

    }

    /**
     * Check that user ihas the role
     *
     * @param array $params Params
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function has_role($params) {
        global $DB;

        if($params['role'] == 'teacher') {
            $roles = $this->get_teacher_roles();
        } elseif($params['role'] == 'student') {
            $roles = $this->get_student_roles();
        }

        $rolefilter = new in_filter($roles, "role");

        return $DB->record_exists_sql(
            "SELECT ra.*
               FROM {role_assignments} ra
              WHERE ra.userid = :userid AND
                    ra.roleid {$rolefilter->get_sql()}",
            array_merge(['userid' => $params['userid']],  $rolefilter->get_params())
        );

    }

    /**
     * Get students of course
     *
     * @param array $params Params
     * @return array List of students
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_course_students($params) {
        global $DB, $PAGE;

        if(isset($params['report_params'])) {
            $reportparams = json_decode($params['report_params'], true);
        } else {
            $reportparams = [];
        }

        $limit = 0;
        $offset = 0;
        $where = "cx.contextlevel = :courselvl AND cx.instanceid = :courseid";
        $sqlarguments = [
            'courseid' => $params['courseid'],
            'courselvl' => CONTEXT_COURSE
        ];

        $rolefilter = new in_filter($this->get_student_roles(), "srole");

        /** Limit and offset */
        if(isset($reportparams['limit'])) {
            $limit = $reportparams['limit'];
        }

        if(isset($reportparams['offset'])) {
            $offset = $reportparams['offset'];
        }

        /** Search */
        if(isset($reportparams['search'])) {
            $where .= sprintf(
                ' AND (%s OR %s)',
                $DB->sql_like('CONCAT(u.firstname, \' \', u.lastname)', ':search1', false),
                $DB->sql_like('CONCAT(u.lastname, \' \', u.firstname)', ':search2', false)
            );
            $sqlarguments['search1'] = "%{$reportparams['search']}%";
            $sqlarguments['search2'] = "%{$reportparams['search']}%";
        }

        $students = $DB->get_records_sql(
            "SELECT DISTINCT u.*
               FROM {context} cx
               JOIN {role_assignments} ra ON ra.contextid = cx.id AND
                                             ra.roleid {$rolefilter->get_sql()}
               JOIN {user} u ON u.id = ra.userid
              WHERE {$where}",
            array_merge($sqlarguments, $rolefilter->get_params()), $offset, $limit
        );

        foreach($students as &$student) {
            $user_picture = new user_picture($student);
            $user_picture->size = 100;
            $student->picture = $user_picture->get_url($PAGE)->out();
        }

        return $students;
    }

    /**
     * Get activities of course
     *
     * @param array $params Params
     * @return array List of activities
     * @throws \dml_exception
     */
    public function get_course_activities($params) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

        if(isset($params['report_params'])) {
            $reportparams = json_decode($params['report_params'], true);
        } else {
            $reportparams = [];
        }

        $limit = 0;
        $offset = 0;
        $where = "t.id > 0";
        $sqlarguments = [
            'courseid' => $params['courseid'],
        ];

        /** Limit and offset */
        if(isset($reportparams['limit'])) {
            $limit = $reportparams['limit'];
        }

        if(isset($reportparams['offset'])) {
            $offset = $reportparams['offset'];
        }

        /** Search */
        if(isset($reportparams['search'])) {
            $where .= sprintf(
                ' AND (%s OR %s)',
                $DB->sql_like('t.name', ':search1', false),
                $DB->sql_like('t.name', ':search2', false)
            );
            $sqlarguments['search1'] = "%{$reportparams['search']}%";
            $sqlarguments['search2'] = "%{$reportparams['search']}%";
        }

        return $DB->get_records_sql(
            "SELECT t.* 
               FROM (SELECT cm.id, " . get_modules_names() . " as name
                       FROM {course_modules} cm
                       JOIN {modules} m ON cm.module = m.id
                      WHERE cm.course = :courseid) t
              WHERE {$where}",
            $sqlarguments, $offset, $limit
        );
    }

    /**
     * Get activity
     *
     * @param array $params Params
     * @return bool | object
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_activity($params) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

        $sqlarguments = [
            'activityid' => $params['activity_id'],
        ];

        $item = $DB->get_record_sql(
            "SELECT cm.id, " . get_modules_names() . " as name,
                    m.name as modulename
               FROM {course_modules} cm
               JOIN {modules} m ON cm.module = m.id
              WHERE cm.id = :activityid",
            $sqlarguments
        );

        if($item) {
            $activityurl = (new moodle_url(
                sprintf('/mod/%s/view.php?id=%s', $item->modulename, $item->id)
            ))->out();
            $item->url = $activityurl;

            return $item;
        }

        return false;
    }

    /**
     * Get User info
     *
     * @param array $params Params
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_user($params) {
        global $DB, $PAGE;

        $user = $DB->get_record(
            'user', ['id' => $params['userid']], '*', MUST_EXIST
        );

        $user_picture = new user_picture($user);
        $user_picture->size = 100;
        $user->picture = $user_picture->get_url($PAGE)->out();
        $user->timezone = \core_date::get_user_timezone($user->timezone);

        return $user;
    }

    /**
     * Check that user is admin
     *
     * @param array $params Params
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function is_admin($params) {
        return has_capability(
            'local/intelliboard:attendanceadmin',
            context_system::instance(),
            $params['userid']
        );
    }

    public function report_data($params) {
        $classname = '\local_intelliboard\attendance\reports\\' .
            $params['report_short_name'];

        /** @var report $classObj */
        $classObj = new $classname;

        return $classObj->get_data(json_decode($params['report_params'], true));
    }

    public function number_of_courses() {
        global $DB;

        return ['number_of_courses' => $DB->count_records('course')];
    }
}
