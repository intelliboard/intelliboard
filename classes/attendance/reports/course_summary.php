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

namespace local_intelliboard\attendance\reports;

class course_summary implements attendance_report_interface {
    public static function get_data($params) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

        $order = '';
        $where = 'c.id <> 1';
        $sqlparams = ['coursecx' => CONTEXT_COURSE];
        $studentroles = explode(
            ',', get_config('local_intelliboard', 'filter11')
        );
        $teacherroles = explode(
            ',', get_config('local_intelliboard', 'filter10')
        );

        // student roles
        if(!$studentroles) {
            $studentrolefilter = ['IN (-1)', []];
        } else {
            $studentrolefilter = $DB->get_in_or_equal(
                $studentroles, SQL_PARAMS_NAMED, 'strole'
            );
        }
        $sqlparams += $studentrolefilter[1];

        //teacher roles
        if(!$teacherroles) {
            $teacherrolefilter = ['IN (-1)', []];
        } else {
            $teacherrolefilter = $DB->get_in_or_equal(
                $teacherroles, SQL_PARAMS_NAMED, 'teachrole'
            );
        }
        $sqlparams += $teacherrolefilter[1];

        if($params['order']) {
            $order = "ORDER BY {$params['order']['field']} {$params['order']['dir']}";
        }

        if(isset($params['courses']) && $params['courses']) {
            $coursefilter = $DB->get_in_or_equal(
                $params['courses'], SQL_PARAMS_NAMED, 'course'
            );
            $where .= " AND c.id {$coursefilter[0]}";
            $sqlparams += $coursefilter[1];
        }

        $teachersSelect = get_operator(
            'GROUP_CONCAT',
            "DISTINCT CONCAT(u.firstname, ' ', u.lastname)",
            ['separator' => ', ']
        );

        return $DB->get_records_sql(
           "SELECT  c.id, c.fullname as course,
                    COUNT(DISTINCT ra.userid) as number_of_enrollments,
                    {$teachersSelect} as teachers
               FROM {course} c
               JOIN {context} cx ON cx.instanceid = c.id AND
                                    cx.contextlevel = :coursecx
          LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id AND
                                             ra.roleid {$studentrolefilter[0]}
          LEFT JOIN {role_assignments} ra2 ON ra2.contextid = cx.id AND
                                              ra2.roleid {$teacherrolefilter[0]}
          LEFT JOIN {user} u ON u.id = ra2.userid
              WHERE {$where}
           GROUP BY c.id {$order}",
            $sqlparams,
            $params['offset'], $params['limit']
        );
    }
}