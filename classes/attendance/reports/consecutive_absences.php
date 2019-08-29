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

class consecutive_absences implements attendance_report_interface {
    public static function get_data($params) {
        global $DB;

        $studentroles = explode(
            ',', get_config('local_intelliboard', 'filter11')
        );
        $order = '';

        if(!$params['users'] or !$studentroles) {
          return [];
        }

        $studentrolefilter = $DB->get_in_or_equal(
            $studentroles, SQL_PARAMS_NAMED, 'role'
        );

        $userFilter = $DB->get_in_or_equal(
          $params['users'], SQL_PARAMS_NAMED, 'user'
        );

        if($params['order']) {
            $order = "ORDER BY {$params['order']['field']} {$params['order']['dir']}";
        }

        return $DB->get_records_sql(
            "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                    COUNT(DISTINCT ra.contextid) as student_courses,
                    (SELECT AVG(gg.finalgrade)
                       FROM {grade_grades} gg
                       JOIN {grade_items} gi ON gi.id = gg.itemid AND
                                                gi.itemtype = 'course'
                      WHERE gg.userid = u.id
                   GROUP BY u.id
                    ) as avg_grade
               FROM {user} u
               JOIN {role_assignments} ra ON ra.userid = u.id AND
                                             ra.roleid {$studentrolefilter[0]}
               JOIN {context} cx ON cx.id = ra.contextid AND
                                    cx.contextlevel = :cxcourse
              WHERE u.id {$userFilter[0]}
           GROUP BY u.id, fullname {$order}",
            ['cxcourse' => CONTEXT_COURSE] + $userFilter[1] + $studentrolefilter[1],
            $params['offset'], $params['limit']
        );
    }
}