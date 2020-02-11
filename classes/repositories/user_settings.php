<?php

namespace local_intelliboard\repositories;

use local_intelliboard\helpers\DBHelper;

class user_settings
{
    public static function getInstructorDashboardCourses($userid)
    {
        global $DB;

        if (!get_config('local_intelliboard', 'instructor_course_visibility')) {
            $sqlcourseivsibility = " AND c.visible = 1";
        } else {
            $sqlcourseivsibility = "";
        }

        $numerictypecast = DBHelper::get_typecast("numeric");

        return $DB->get_records_sql(
            "SELECT *
               FROM {local_intelliboard_assign} lia
               JOIN {course} c ON c.id = lia.instance{$numerictypecast} {$sqlcourseivsibility}
              WHERE lia.rel = 'instructordashboard' AND lia.type = 'courses' AND lia.userid = ?",
            [$userid]
        );
    }
}