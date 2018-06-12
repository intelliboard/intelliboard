<?php

namespace Helpers;

use Containers\ColumnsContainer;
use Containers\TablesContainer;

class DB {

    private static $systemWords = array(
        'students?', 'users?', 'admins?', 'learners?', 'courses?', 'activity', 'activities',
        'cohorts?', 'guest', 'teachers?', 'data'
    );

    private static $operators = array(
        'regexp' => array(
            \DataExtractor::MYSQL_MODE => 'REGEXP',
            \DataExtractor::POSTGRES_MODE => '~*'
        )
    );

    private static $virtualColumns;
    private static $virtualTables;

    public static function init() {
        global $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

        self::$virtualColumns = array(
            "user_fullname" => "CONCAT_WS(' ', firstname, lastname)",
            "student_fullname" => "CONCAT_WS(' ', firstname, lastname)",
            "teacher_fullname" => "CONCAT_WS(' ', firstname, lastname)",
            "activity_activity" => function() {
                return \get_modules_names();
            },
            "config_plugins_type" => "SUBSTRING_INDEX(plugin, '_', 1)",
            "config_plugins_name" => "REPLACE(TRIM(BOTH '_' FROM SUBSTRING(plugin, POSITION('_' IN plugin))), '_', ' ')",
            "local_plugins_name" => "REPLACE(TRIM(BOTH '_' FROM SUBSTRING(plugin, POSITION('_' IN plugin))), '_', ' ')",
        );

        self::$virtualTables = array(
            "teacher" => function($settings, $getter) {
                $getter->add('tables', '{user} AS u INNER JOIN {role_assignments} AS ra ON ra.userid = u.id AND ra.roleid IN(' . $settings['teacher_roles'] . ')');
                return 'u';
            },
            "student" => function($settings, $getter) {
                $getter->add('tables', '{user} AS u INNER JOIN {role_assignments} AS ra ON ra.userid = u.id AND ra.roleid IN(' . $settings['learner_roles'] . ')');
                return 'u';
            },
            "activity" => function($settings, $getter) {
                $getter->add('tables', '{modules} as m INNER JOIN {course_modules} as cm ON m.id = cm.module');
                return 'cm';
            },
            "local_plugins" => function($settings, $getter) {
                $getter->add('tables', '{config_plugins} as cp');
                return 'cp';
            }
        );
    }

    private static $initialized = array();

    public static function getParamsFromDB(
        $table,
        $column,
        $settings = array(),
        $length = null,
        $like = null,
        $id = false,
        $pluralize = 0,
        $paramFilters = array(),
        $additionalFields = array()
    ) {
        global $DB;

        list($table, $column, $getter, $types, $alias) = static::start($table, $column, $settings);

        if (in_array('custom', $types)) {
            $choices = array();
            if (in_array('country', $types)) {

                $countries = \get_string_manager()->get_list_of_countries();
                foreach ($countries as $id => $value) {
                    $choices[] = empty($additionalFields['id'])? compact('value') : compact('id', 'value');
                }

            } else {

                $plugins = \core_component::get_plugin_list($table);

                foreach ($plugins as $key => $unused) {
                    $value = get_string('pluginname', "{$table}_{$key}");
                    $id = array_pop(explode('/', $key));
                    $choices[] = empty($additionalFields['id'])? compact('value') : compact('id', 'value');
                }

            }

            $choices = $pluralize? static::pluralize($choices) : $choices;
            return $length? array_slice($choices, 0, $length) : $choices;

        }

        static::applyFilters($table, $column, $getter, $types, $alias, $settings, $paramFilters, $additionalFields);

        if ($like) {
            $getter->add('filters', "LOWER($column) LIKE LOWER(:like)");
            $getter->setParam('like', "%$like%");

        }

        if ($id) {
            $getter->add('filters', "$alias.id = $id");
        }

        $data  = $getter->release();
        $result = $data['sql'];

        if ($length) {
            $result .=  ' LIMIT ' . $length;
        }

        $result = json_decode(json_encode($DB->get_records_sql($result, $data['params'])),true);

        if (!$additionalFields) {
            $result = array_filter($result, function($item) {
                return $item['value'] !== '';
            });
        }

        return $pluralize? static::pluralize($result) : $result;
    }

    protected static function pluralize($result) {
        $result = array_values($result);
        return array_merge($result, array_map(function($item) {
            $item['value'] = PluralHelper::pluralize($item['value']);
            return $item;
        }, $result));
    }

    public static function getTable($table, $settings, $getter) {

        if (self::$virtualTables[$table]) {
            $function = self::$virtualTables[$table];
            $alias = $function($settings, $getter);
        } else {
            $alias = $table[0];
            $getter->add('tables', '{' . $table . '} as ' . $alias);
        }

        return $alias;

    }

    public static function getColumn($column, $table, $settings) {
            $destination = null;
            $key = $table . '_' . $column;

            if (isset(self::$virtualColumns[$key])) {

                if(is_callable(self::$virtualColumns[$key])) {
                    $function = self::$virtualTables[$table];
                    $destination = $function($settings);
                } else {
                    $destination = self::$virtualColumns[$key];
                }

            } else {
                $destination = $column;
            }

            return $destination;
    }

    public static function extractParamsFromSentence (
        $table,
        $column,
        $sentence,
        $params = array(),
        $pluralize = 0,
        $escapeSystem = 0,
        $additionalFields = array(),
        $pattern = null,
        $prefix = null
    ) {

        global $DB;

        list($table, $column, $getter, $types, $alias) = static::start($table, $column, $params);
        $variants = array();

        if (!empty($prefix['value'])) {
            $prefix['value'] = (is_array($prefix['value'])? '(' . implode('|', $prefix['value']) . ')' : $prefix['value']);
        }

        if ($pluralize || in_array('custom', $types)) {
            $values = static::getParamsFromDB($table, $column, $params, null, null, null, $pluralize, array(), $additionalFields);

            array_walk($values, function($value) {
                $value['value'] = trim($value['value']);
            });

            $values = array_filter($values, function($value) use($escapeSystem) {
                if ($value['value'] === '') {
                    return false;
                }

                if ($escapeSystem) {
                    $systemWords = implode('|', static::$systemWords);

                    if (preg_match("~^($systemWords)$~i", $value['value'])) {
                        return false;
                    }
                }

                return true;
            });

            usort($values, function($a, $b) {
                return strlen($b['value']) - strlen($a['value']);
            });

            foreach($values as $value) {

                if ($prefix) {
                    $search = $prefix['type'] === 'prefix'? $prefix['value'] . ' ' . $value['value'] : $value['value']  . ' ' . $prefix['value'];
                    $replacement = $prefix['type'] === 'prefix'? ':prefix ' : ' :prefix';
                } else {
                    $search = $value['value'];
                    $replacement = ' ';
                }

                $sentence = preg_replace_callback('~(?:^|\s)' . $search . '(?:\s|$)~i',
                    function ($matches) use (&$variants, $value, $replacement, $prefix) {
                        $variants[] = $value;

                        return $prefix? str_replace(':prefix', $matches[1], $replacement) : $replacement;
                    }, $sentence, 1
                );

                if ($variants) {
                    break;
                }

            }

        } else {


            static::applyFilters($table, $column, $getter, $types, $alias, $params, array(), $additionalFields);

            if ($prefix) {
                $search = ($prefix['type'] === 'prefix'? ":prefix, ' ',  $column" : "$column, ' ', :prefix");
                $getter->setParam('prefix', trim($prefix['value']));
            } else {
                $search = $column;
            }

            $pattern = $pattern? $pattern : ":sentence " . static::getOperator("regexp") . " CONCAT('[[:<:]]', :column, '[[:>:]]')";

            $getter->add('filters', str_replace(array(':column', ':sentence'), array($search, "'$sentence'"), $pattern));
            $getter->add('filters', "$column <> ''");

            if ($escapeSystem) {
                $systemWords = rtrim(array_reduce(static::$systemWords, function($buffer, $item) use ($getter) {
                    static $i = 1;
                    $key = 'system' . $i++;
                    $getter->setParam($key, $item);
                    return $buffer . "LOWER(:$key),";
                }, ''), ',');

                $getter->add('filters', "$column NOT IN ($systemWords)");
            }

            $sentence = " $sentence ";
            $getter->add('columns', "INSERT('$sentence', POSITION(CONCAT(' ', LOWER({$column})) IN '$sentence'), CHAR_LENGTH($column) + 1, ' ') as replacement");
            $data = $getter->release();
            $sql  = $data['sql'] . " ORDER BY CHAR_LENGTH($column) DESC LIMIT 1";
            $variant = json_decode(json_encode($DB->get_record_sql($sql, $data['params'])), true);
            $sentence = !empty($variant['replacement'])? $variant['replacement'] : $sentence;
            $variants = $variant? array(array_diff_key($variant, array('replacement' => 1))) : [];

        }

        if ($variants && $table === 'role') {
            $value = $variants[0]['value'];

            if (in_array($value, array('student', 'students', 'teacher', 'teachers', 'user', 'users'))) {
                $getter = new ParamGetter();
                static::addAdditionalFields($getter, 'shortname', $additionalFields);
                $getter->add('tables', '{role}');

                switch($value) {
                    case 'student':
                    case 'students':
                        $getter->add('filters', 'id IN (' . $params['learner_roles'] . ')');
                        break;
                    case 'teacher':
                    case 'teachers':
                        $getter->add('filters', 'id IN (' . $params['teacher_roles'] . ')');
                        break;
                }
                $variants = json_decode(json_encode(array_values($DB->get_records_sql($getter->release()['sql']))), true);

                if ($pluralize && $variants) {
                    $variants = static::pluralize($variants);
                }
            }
        }

        $result = $variants;

        static::removeInitialization();
        return compact('sentence', 'result');
    }

    public static function processAutoCompleteDb($table, $column, $remainder, $params = array()) {

        $variants = static::extractParamsFromSentence($table, $column, $remainder, $params, 0, 0, array(), ":sentence " . static::getOperator("regexp") . " CONCAT('^', :column, '[[:>:]]')")['result'];
        $maxShift = 0;
        $found = '';

        foreach($variants as $variant) {
            $wordCount = count(explode(' ', $variant));

            if ($wordCount > $maxShift) {
                $maxShift = $wordCount;
                $found    = $variant;
            }
        }

        $variants = static::extractParamsFromSentence($table, $column, '^' . $remainder, $params, 0, 0, array(), ":column " . static::getOperator("regexp") . " :sentence")['result'];
        $endings = array_map(function($item) use($remainder) {
            return substr($item, mb_strlen($remainder));
        }, $variants);

        return compact('endings', 'found');
    }

    protected static function applyFilters(
        &$table,
        &$column,
        ParamGetter $getter,
        $types,
        $alias,
        $settings = array(),
        $paramFilters = array(),
        $additionalFields = array()
    ) {

        $courseFilter        = !empty($paramFilters['course']) ? $paramFilters['course']       : false;
        $roleFilter          = !empty($paramFilters['role']) ? $paramFilters['role']           : false;
        $activityFilter      = !empty($paramFilters['activity']) ? $paramFilters['activity']   : false;
        $cohortFilter        = !empty($paramFilters['cohort']) ? $paramFilters['cohort']       : false;
        $groupFilter         = !empty($paramFilters['group']) ? $paramFilters['group']         : false;
        $teacherFilter       = !empty($paramFilters['teacher']) ? $paramFilters['teacher']     : false;
        $enrolFilter         = !empty($paramFilters['enrol']) ? $paramFilters['enrol']         : false;
        $moduleFilter        = !empty($paramFilters['module']) ? $paramFilters['module']       : false;
        $userInfoDataFilter  = !empty($paramFilters['userdata']) ? $paramFilters['userdata']   : false;
        $userInfoFieldFilter = !empty($paramFilters['userfield']) ? $paramFilters['userfield'] : false;
        $userFilter          = !empty($paramFilters['user']) ? $paramFilters['user']           : false;

        $pluginTypes = array('certificate', 'questionnaire');

        if (in_array('user', $types)) {

            $showNotActive = !empty($settings['filter_enrol_status'])? $settings['filter_enrol_status'] : false;

            if (!empty($settings['filter_enrolled_users']) || $courseFilter || $activityFilter) {
                $getter->add('tables', 'INNER JOIN {user_enrolments} AS ue ON ue.userid = u.id');

                if (!$showNotActive || $courseFilter || $activityFilter) {
                    $getter->add('tables', 'INNER JOIN {enrol} AS e ON e.id = ue.enrolid');
                    $getter->add('filters', 'ue.status = 0 AND e.status = 0');
                }

                if($courseFilter || $activityFilter) {
                    $getter->add('tables', 'INNER JOIN {course} AS c ON c.id = e.course');

                    if ($courseFilter) {
                        $getter->add('filters', 'c.id = :course');
                        $getter->setParam('course', $courseFilter);
                    }

                }

            }

            if ($cohortFilter) {
                $getter->add('tables', 'INNER JOIN {cohort_members} AS chm ON chm.userid = u.id');
                $getter->add('filters', 'chm.cohortid = :cohort');
                $getter->setParam('cohort', $cohortFilter);
            }

            if ($groupFilter) {
                $getter->add('tables', 'INNER JOIN {group_members} AS gpm ON gpm.userid = u.id');
                $getter->add('filters', 'gpm.groupid = :group');
                $getter->setParam('group', $groupFilter);
            }

            if ($roleFilter) {
                $getter->add('tables', 'INNER JOIN {role_assignments} AS ra ON ra.userid = ' . $alias . '.id');
                $getter->add('tables', 'INNER JOIN {role} AS r ON r.id = ra.roleid');
                $getter->add('filters', 'r.shortname = :role');
                $getter->setParam('role', $roleFilter);
            }

            if (empty($settings['filter_user_deleted'])) {
                $getter->add('filters', 'u.deleted = 0');
            }

            if (empty($settings['filter_user_suspended'])) {
                $getter->add('filters', 'u.suspended = 0');
            }

            if (empty($settings['filter_user_guest'])) {
                $getter->add('filters', 'u.username <> \'guest\'');
            }

            if ($userInfoDataFilter) {
                $getter->add('tables', 'INNER JOIN {user_info_data} uid ON uid.userid = u.id');
                $getter->add('filters', 'uid.data = :data');
                $getter->setParam('data', $userInfoDataFilter);
            }

            if (!empty($settings['external_id'])) {
                $getter->setParam('external_id_1', $settings['external_id']);
                $getter->setParam('external_id_2', $settings['external_id']);
                $getter->add('filters', "
                    ($alias.id IN (SELECT instance FROM {local_intelliboard_assign} WHERE userid = :external_id_1 AND type = 'users')
                     OR $alias.id IN (SELECT m.userid FROM {local_intelliboard_assign} a, {cohort_members} m WHERE m.cohortid = a.instance AND a.userid = :external_id_2 AND a.type = 'cohorts'))
                ");
            }

            $getter->add('tables', 'INNER JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30');

        }

        if ((in_array('course', $types) || in_array('user', $types)) && $activityFilter) {
            if ($table !== 'activity') {
                $getter->add('tables', 'INNER JOIN {course_modules} AS cm ON cm.course = c.id');
            }

            $getter->add('filters', 'cm.id = :activity');
            $getter->setParam('activity', $activityFilter);
        }

        if (in_array('course', $types)) {

            if ($groupFilter) {
                $getter->add('tables', 'INNER JOIN {groups} AS gp ON gp.courseid = c.id');
                $getter->add('filters', 'gp.id = :group');
                $getter->setParam('group', $groupFilter);
            }

            if ($teacherFilter) {
                $getter->add('tables', 'INNER JOIN {context} AS ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50');

                $sql = 'INNER JOIN {role_assignments} AS ra ON ra.contextid = ctx.id AND ra.roleid';
                $roles = explode(',', $settings['teacher_roles']);

                ParamGetter::in_sql($getter, 'tables', $sql, $roles);
                $getter->add('filters', 'ra.userid = :teacher');
                $getter->setParam('teacher', $teacherFilter);
            }

            if ($enrolFilter) {
                $getter->add('tables', 'INNER JOIN {enrol} AS e ON e.courseid = c.id');
                $getter->add('filters', 'e.enrol = :enrol');
                $getter->setParam('enrol', $enrolFilter);
            }

            if (empty($settings['filter_course_visible'])) {
                $getter->add('filters', 'c.visible =  1');
            }

            if (!empty($settings['external_id'])) {
                $getter->setParam('external_id', $settings['external_id']);
                $getter->add('filters', "($alias.id IN (SELECT instance FROM {local_intelliboard_assign} WHERE userid = :external_id AND type = 'courses'))");
            }

            $getter->add('tables', 'LEFT JOIN {context} cx ON cx.instanceid = c.id AND contextlevel = 50');
        }

        if (in_array('activity', $types)) {

            if ($table !== 'activity') {
                $getter->add('tables', 'INNER JOIN {modules} m ON m.name = \'' . $table . '\'');
                $getter->add('tables', 'INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = ' . $table . '.id');
            }

            if ($moduleFilter) {
                $getter->add('filters', 'm.id = :module');
                $getter->setParam('module', $moduleFilter);
            }

            if ($courseFilter) {
                $getter->add('filters', 'cm.course = :course');
                $getter->setParam('course', $courseFilter);
            }

            if (empty($settings['filter_module_visible'])) {
                $getter->add('filters', 'm.visible =  1');
            }

            if (!empty($settings['external_id'])) {
                $getter->setParam('external_id', $settings['external_id']);
                $getter->add('tables', 'INNER JOIN {course} c ON cm.course = c.id');
                $getter->add('filters', '(c.id IN (SELECT instance FROM {local_intelliboard_assign} WHERE userid = :external_id AND type = \'courses\'))');

            }

        }

        if (in_array('group', $types)) {

            if ($courseFilter) {
                $getter->add('tables', 'INNER JOIN {course} c ON c.id = groups.courseid');
                $getter->add('filters', 'c.id = :course');
                $getter->setParam('course', $courseFilter);
            }

        }

        if (in_array('role', $types)) {
            $originalColumn = $column;
            $column = 'shortname';

            switch($originalColumn) {
                case 'student':
                    ParamGetter::in_sql($getter, 'filters', 'r.id', explode(',', $settings['learner_roles']));
                    break;
                case 'teacher':
                    ParamGetter::in_sql($getter, 'filters', 'r.id', explode(',', $settings['teacher_roles']));
                    break;
                case 'user':
                    break;
                default:
                    $column = $originalColumn;
            }

            if ($activityFilter || $courseFilter) {

                $getter->add('tables', 'INNER JOIN {role_assignments} AS ra ON ra.roleid = r.id');
                $getter->add('tables', 'INNER JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contexlevel = 50');

                if ($courseFilter) {
                    $getter->add('filters', 'ctx.instanceid = :course');
                    $getter->setParam('course', $courseFilter);
                }

                if ($activityFilter) {
                    $getter->add('tables', 'INNER JOIN {course} AS c ON c.id = ctx.instanceid');
                    $getter->add('tables', 'INNER JOIN {course_modules} AS cm ON cm.course = c.id');

                    $getter->add('filters', 'cm.id = :activity');
                    $getter->setParam('activity', $activityFilter);
                }

            }
        }

        if (in_array('local_plugins', $types)) {
            $getter->add('filters', "$alias.plugin LIKE 'local_%'");
        }

        if (in_array('user_info_data', $types)) {
            if ($userInfoFieldFilter) {
                $getter->add('filters', 'fieldid = :field');
                $getter->setParam('field', $userInfoFieldFilter);
            }

            if ($userFilter) {
                $getter->add('filters', '{user_info_data}.userid = :user');
                $getter->setParam('user', $userFilter);
            }
        }

        if (in_array('user_info_field', $types)) {
            if ($userInfoDataFilter) {
                $getter->add('tables', 'INNER JOIN {user_info_data} uid ON uid.fieldid = {user_info_field}.id');
                $getter->add('filters', 'uid.data = :data');
                $getter->setParam('data', $userInfoDataFilter);
            }
        }

        static::addAdditionalFields($getter, $column, $additionalFields, $alias);

        if ($required = array_intersect($types, $pluginTypes)) {

            foreach($required as $item) {
                $pluginManager = \core_plugin_manager::instance();

                if(!$pluginManager->get_plugin_info($item)) {
                    return false;
                }
            }

        }

        return $getter;

    }

    protected static function addAdditionalFields ($getter, $column, $additionalFields = array(), $alias = false) {
        $alias = $alias? $alias . '.' : '';
        if ($additionalFields) {
            foreach ($additionalFields as $name => $field) {
                $field = preg_replace('/\s+/', '', $field);
                if (strpos($field, '.') === false) {
                    $getter->add('columns', $alias . $field . " as $name");
                } else {
                    $getter->add('columns', "$field as $name");
                }
            }

            $getter->add('columns', $column . ' AS value');
        } else {
            $getter->add('columns', ' DISTINCT(' . $column . ') AS value');
        }

    }

    protected static function start($table, $column, $settings)
    {
        global $CFG;

        if (!static::$initialized) {

            if (is_numeric($column)) {
                $column = ColumnsContainer::getById($column, $CFG->dbtype)['name'];
            }

            if (is_numeric($table)) {
                $table = TablesContainer::getById($table)['sql'];
            }

            $column = preg_replace('/\s+/', '', $column);
            $table = preg_replace('/\s+/', '', $table);

            static::init();

            $table = trim($table, '{}');

            $getter = new ParamGetter();
            $types = static::detectType($table);

            $alias = static::getTable($table, $settings, $getter);
            $column = static::getColumn($column, $table, $settings);

            static::$initialized = array($table, $column, $getter, $types, $alias);
        }

        return static::$initialized;
    }

    public static function removeInitialization()
    {
        static::$initialized = false;
    }

    public static function detectType($table)
    {
        global $DB;

        if(in_array($table, array('teacher', 'user', 'student'))) {
            return array('user');
        } else if (in_array($table, array_merge(array('activity'), array_keys($DB->get_records('modules', array(),null,'name'))))) {
            return array('module', $table);
        } else if (in_array($table, array('auth', 'enrol', 'country'))) {
            return array('custom', $table);
        } else if (in_array($table, array('group'))) {
            return array('group', 'course');
        } else {
            return array($table);
        }

    }

    protected static function getOperator($operator)
    {
        global $CFG;

        $val = static::$operators[$operator];

        if (is_array($val)) {
            $val = $val[$CFG->dbtype];
        }

        return $val;
    }

}