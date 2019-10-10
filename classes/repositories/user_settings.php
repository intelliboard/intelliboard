<?php

namespace local_intelliboard\repositories;

class user_settings
{
    public static function getInstructorDashboardCourses($userid)
    {
        global $DB;

        return $DB->get_records(
            'local_intelliboard_assign',
            ['rel' => 'instructordashboard', 'type' => 'courses', 'userid' => $userid],
            '',
            'instance, rel, type, userid, timecreated'
        );
    }
}