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
 * @copyright  2018 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

defined('MOODLE_INTERNAL') || die();

class local_external_functions
{
    protected $functions;
    protected $columns;

    public function __construct()
    {
        $coursetagsconcat = get_operator('GROUP_CONCAT', 'c1tg.name', ['separator' => ',']);
        $activitytagsconcat = get_operator('GROUP_CONCAT', 'c2tg.name', ['separator' => ',']);
        $this->columns = [
          0 => "course_alias_.fullname", //course
          1 => "course_alias_.shortname", //course
          2 => "course_alias_.category", //course
          3 => "course_alias_.idnumber", //course
          4 => "course_alias_.startdate", //course
          5 => "course_alias_.enddate", //course
          6 => "course_alias_.visible", //course
          7 => "(SELECT name FROM {course_categories} WHERE id = course_alias_.category)", //course
          8 => "(SELECT COUNT(id) FROM {course_modules} WHERE visible = 1 AND course = course_alias_.id)", //course
          9 => "(SELECT {$coursetagsconcat}
                   FROM {tag_instance} c1ti
                   JOIN {tag} c1tg ON c1tg.id = c1ti.tagid
                  WHERE c1ti.component = 'core' AND c1ti.itemtype = 'course' AND
                        c1ti.itemid = course_alias_.id
                  GROUP BY c1ti.itemid)", //course tags
          10 => "(SELECT {$activitytagsconcat}
                    FROM {tag_instance} c2ti
                    JOIN {tag} c2tg ON c2tg.id = c2ti.tagid
                   WHERE c2ti.component = 'core' AND c2ti.itemtype = 'course_modules' AND
                         c2ti.itemid = cm_alias_.id
                   GROUP BY c2ti.itemid)", // activity tags
          11 => "creator.name", // user created by
          12 => "course_roles.roles", // user course roles
          13 => "system_roles.roles", // user system roles
          14 => "CONCAT(enrol_by.firstname, ' ', enrol_by.lastname)", // Enrolled by
          15 => "course_teachers.names", // Course teachers
          17 => "CONCAT(course_sections.name, '|', course_sections.section)",
          18 => "COALESCE(course_module_completion.timemodified, 0)",
          19 => "intellicart_vendors.names"
        ];
        $this->functions = [
            'report1',
            'report2',
            'report3',
            'report4',
            'report5',
            'report6',
            'report7',
            'report8',
            'report9',
            'report10',
            'report11',
            'report12',
            'report13',
            'report14',
            'report15',
            'report16',
            'report17',
            'report18',
            'report18_graph',
            'report19',
            'report20',
            'report21',
            'report22',
            'report23',
            'report24',
            'report25',
            'report26',
            'report27',
            'report28',
            'report29',
            'report30',
            'report31',
            'report32',
            'get_scormattempts',
            'get_competency',
            'get_competency_templates',
            'report33',
            'report34',
            'report35',
            'report36',
            'report37',
            'report38',
            'report39',
            'report40',
            'report41',
            'report43',
            'report44',
            'report45',
            'report42',
            'report46',
            'report47',
            'report58',
            'report66',
            'report72',
            'report73',
            'report75',
            'report76',
            'report77',
            'report79',
            'report80',
            'report81',
            'report82',
            'report83',
            'report84',
            'report85',
            'report86',
            'report87',
            'report88',
            'report89',
            'report90',
            'report91',
            'report92',
            'report93',
            'report94',
            'report95',
            'report96',
            'report97',
            'report98',
            'report99',
            'report99_graph',
            'report100',
            'report101',
            'report102',
            'report103',
            'report104',
            'report105',
            'report106',
            'report107',
            'report108',
            'report109',
            'report110',
            'report111',
            'report112',
            'report113',
            'report114',
            'report114_graph',
            'report115',
            'report116',
            'report117',
            'report118',
            'report119',
            'report120',
            'report121',
            'report122',
            'report123',
            'report124',
            'report125',
            'report126',
            'report127',
            'report128',
            'get_course_modules',
            'report78',
            'report74',
            'report71',
            'report70',
            'report67',
            'report68',
            'report69',
            'get_max_attempts',
            'report56',
            'analytic1',
            'analytic2',
            'get_quizes',
            'analytic3',
            'analytic4',
            'analytic5',
            'analytic5table',
            'analytic6',
            'analytic7',
            'analytic7table',
            'analytic8',
            'analytic8details',
            'get_visits_perday',
            'get_visits_perweek',
            'get_live_info',
            'get_course_instructors',
            'get_course_discussions',
            'get_course_questionnaire',
            'get_course_survey',
            'get_course_questionnaire_questions',
            'get_course_survey_questions',
            'get_cohort_users',
            'get_users',
            'get_grade_letters',
            'get_questions',
            'get_total_info',
            'get_system_users',
            'get_system_courses',
            'get_system_load',
            'get_module_visits',
            'get_useragents',
            'get_useros',
            'get_userlang',
            'get_users_count',
            'get_most_visited_courses',
            'get_enrollments_per_course',
            'get_active_courses_per_day',
            'get_unique_sessions',
            'get_new_courses_per_day',
            'get_users_per_day',
            'get_active_users_per_day',
            'get_countries',
            'get_cohorts',
            'get_elisuset',
            'get_totara_pos',
            'get_scorm_user_attempts',
            'get_course_users',
            'get_info',
            'get_courses',
            'get_userids',
            'get_modules',
            'get_outcomes',
            'get_roles',
            'get_roles_fix_name',
            'get_tutors',
            'get_cminfo',
            'get_enrols',
            'get_learner',
            'get_learners',
            'get_learner_courses',
            'get_course',
            'get_userinfo',
            'get_user_info_fields_data',
            'get_user_info_fields',
            'get_site_avg',
            'get_site_activity',
            'count_records',
            'analytic9',
            'get_course_sections',
            'get_course_user_groups',
            'get_course_assignments',
            'get_data_question_answers',
            'get_course_databases',
            'get_databases_question',
            'get_history_items',
            'get_history_grades',
            'monitor27',
            'monitor28',
            'monitor29',
            'monitor30',
            'monitor31',
            'get_assign_users',
            'get_assign_courses',
            'get_assign_fields',
            'get_assign_categories',
            'get_assign_cohorts',
            'get_course_grade_categories',
            'get_visits_per_day_by_entity',
            'report137',
            'get_role_users',
            'report139_header',
            'report139',
            'get_course_feedback',
            'report140',
            'report141',
            'report142',
            'report143',
            'report149',
            'get_incorrect_answers',
            'report150',
            'report151',
            'report152',
            'report154',
            'monitor32',
            'monitor33',
            'monitor34',
            'monitor35',
            'monitor36',
            'monitor37',
            'monitor38',
            'monitor39',
            'report144',
            'report145',
            'report155',
            'report156',
            'report157',
            'report158',
            'report159',
            'report160',
            'report161',
            'report162',
            'report163',
            'report164',
            'report165',
            'analytic10',
            'analytic10table',
            'report167',
            'get_question_tags',
            'get_course_checklists',
            'report168',
            'get_course_checklist_items',
            'get_quiz_questions',
            'report169',
            'report170',
            'report171',
            'report172',
            'report173',
            'report174',
            'report179',
            'report181',
            'get_moodle_size',
            'monitor53',
            'get_event_contexts',
            'monitor54',
            'kill_db_queries',
            'get_intellicart_vendors',
            'report182',
            'analytic11',
            'analytic11Table',
            'report175',
            'report176',
            'report177Chart',
            'report177Table',
            'report177StudentInfo',
            'report178Charts',
            'report178Table',
            'get_bb_collaborate_sessions',
            'report180Table',
            'report180SessionDetails',
            'monitor57',
            'monitor58',
            'monitor59',
            'monitor60',
            'plugin_settings',
            'users_overview',
            'monitor62',
            'report183',
            'available_modules',
            'report185',
            'report186',
            'report187',
            'get_assignment_grading_definitions',
            'report188',
            'report193',
            'get_system_roles_fix_names',
            'report195',
            'report196',
            'get_cohort_courses',
            'get_course_items',
            'get_cohort_items',
            'get_relation_users_per_day',
            'report198',
            'report199',
            'report200',
            'report201',
            'report202',
            'report203',
            'report204',
            'report205',
            'report206',
            'get_cohort_stats',
            'get_cohort_feedback_items',
            'get_course_feedback_items',
            'get_course_categories',
            'get_cohort_feedbacks',
            'report207',
            'report208',
            'report209',
            'get_attendance_statuses',
            'subaccount_export_prepare',
            'monitor69',
            'get_hospitals',
            'get_hospital_cohorts',
            'get_hospital_info',
            'monitor71',
            'monitor72',
            'monitor73',
            'monitor74',
            'monitor75',
            'report212',
            'report213',
            'report213_header',
            'get_course_scorms',
            'monitor76',
            'monitor77',
            'monitor78',
            'get_tracking_logs',
            'get_tracking_details',
            'report217',
            'report219',
            'report222',
            'report223',
            'monitor84',
            'monitor85',
            'report224',
            'report225',
            'get_template_competencies',
            'report226',
            'monitor21',
            'report229',
            'get_students',
            'report230',
            'report231',
            'get_courses_transcripts',
            'get_modules_transcripts',
            'monitor89',
            'course_custom_fields',
            'monitor90',
            'monitor91',
            'monitor92',
            'intellicart_vendors',
            'analytic232Table',
            'analytic232Details',
            'general_lms_data',
            'get_enrollments_sessions_completions',
            'report233',
            'report234',
            'get_feedback_items',
            'report235',
            'report236',
            'report237',
            'report238',
            'monitor93',
            'monitor94',
            'get_course_feedbacks',
            'get_feedback_questions',
            'analytic241',
            'report242',
            'report243',
            'report244',
            'report245',
            'report246',
            'report247',
            'report248',
            'get_enrolment_methods',
            'report249',
            'get_quiz_attempt',
            'report251',
            'report252',
            'report253',
            'get_programs',
            'get_mwp_program_users_completion',
            'report254',
            'get_course_modules_names',
            'get_program_courses',
            'report255',
            'get_deleted_assigns',
            'report256',
            'get_course_quiz_questions',
            'get_icpayment_types',
            'get_icproduct_custom_fields',
        ];
    }
    public function get_function($params = null)
    {
      $function = (isset($params->function)) ? $params->function : '';

      if (in_array($function, $this->functions)) {
          return $function;
      } else {
        return false;
      }
    }
    public function get_functions()
    {
        return $this->functions;
    }

    public function get_columns()
    {
        return $this->columns;
    }
}
