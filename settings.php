<?php
// IntelliBoard.net
//
// IntelliBoard.net is built to work with any LMS designed in Moodle
// with the goal to deliver educational data analytics to single dashboard instantly.
// With power to turn this analytical data into simple and easy to read reports,
// IntelliBoard.net will become your primary reporting tool.
//
// Moodle
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// IntelliBoard.net is built as a local plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	intelliboard
 * @copyright  	2015 IntelliBoard, Inc
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	IntelliBoard, Inc
 * @website		www.intelliboard.net
 */

defined('MOODLE_INTERNAL') || die;

$settings = new admin_settingpage('local_intelliboard', get_string('settings', 'local_intelliboard'));

$ADMIN->add('root', new admin_category('intelliboardroot', get_string('intelliboardroot', 'local_intelliboard')));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardcontrolpanel', get_string('dashboard', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/index.php', 'local/intelliboard:manage'));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardlearners', get_string('learners', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/learners.php', 'local/intelliboard:manage'));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardcourses', get_string('courses', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/courses.php', 'local/intelliboard:manage'));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardload', get_string('load', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/load.php', 'local/intelliboard:manage'));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardreports', get_string('reports', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/reports.php', 'local/intelliboard:manage'));
$ADMIN->add('intelliboardroot', new admin_externalpage('intelliboardsettings', get_string('settings'),
        $CFG->wwwroot.'/local/intelliboard/config.php', 'local/intelliboard:manage'));

if (!$ADMIN->locate('intelliboard') and $ADMIN->locate('localplugins')){
	$ADMIN->add('localplugins', new admin_category('intelliboard', get_string('pluginname', 'local_intelliboard')));
	$ADMIN->add('intelliboard', $settings);


	$ADMIN->add('intelliboard', new admin_externalpage('intelliboardlogs', get_string('logs', 'local_intelliboard'),
        $CFG->wwwroot.'/local/intelliboard/logs.php'));
}

$settings->add(new admin_setting_heading('local_intelliboard/tracking_title', get_string('tracking_title', 'local_intelliboard'), ''));

$name = 'local_intelliboard/enabled';
$title = get_string('enabled', 'local_intelliboard');
$description = get_string('enabled_desc', 'local_intelliboard');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$settings->add($setting);

$name = 'local_intelliboard/ajax';
$title = get_string('ajax', 'local_intelliboard');
$description = get_string('ajax_desc', 'local_intelliboard');
$default = '30';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$settings->add($setting);

$name = 'local_intelliboard/inactivity';
$title = get_string('inactivity', 'local_intelliboard');
$description = get_string('inactivity_desc', 'local_intelliboard');
$default = '60';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$settings->add($setting);

$name = 'local_intelliboard/trackadmin';
$title = get_string('trackadmin', 'local_intelliboard');
$description = get_string('trackadmin_desc', 'local_intelliboard');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$settings->add($setting);


$settings->add(new admin_setting_heading('local_intelliboard/ts1', get_string('ts1', 'local_intelliboard'), ''));

$name = 'local_intelliboard/t1';
$title = get_string('t1', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/te1';
$title = get_string('te1', 'local_intelliboard');
$description = get_string('te1_desc', 'local_intelliboard');
$setting = new admin_setting_configtext($name, $title, $description, '');
$settings->add($setting);

$name = 'local_intelliboard/t01';
$title = get_string('t01', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t02';
$title = get_string('t02', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t03';
$title = get_string('t03', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t04';
$title = get_string('t04', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t05';
$title = get_string('t05', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t06';
$title = get_string('t06', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t07';
$title = get_string('t07', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$settings->add(new admin_setting_heading('local_intelliboard/ts2', get_string('ts2', 'local_intelliboard'), ''));

$name = 'local_intelliboard/t2';
$title = get_string('t2', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t5';
$title = get_string('t5', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t6';
$title = get_string('t6', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t7';
$title = get_string('t7', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t8';
$title = get_string('t8', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t9';
$title = get_string('t9', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t31';
$title = get_string('t31', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t32';
$title = get_string('t32', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t10';
$title = get_string('t10', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t33';
$title = get_string('t33', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t34';
$title = get_string('t34', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t11';
$title = get_string('t11', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t35';
$title = get_string('t35', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t36';
$title = get_string('t36', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t37';
$title = get_string('t37', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t38';
$title = get_string('t38', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t12';
$title = get_string('t12', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t13';
$title = get_string('t13', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t14';
$title = get_string('t14', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t15';
$title = get_string('t15', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$settings->add(new admin_setting_heading('local_intelliboard/ts3', get_string('ts3', 'local_intelliboard'), ''));

$name = 'local_intelliboard/t3';
$title = get_string('t3', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$options = array("Blue","Light Blue","Orange","Green","Red","Gray");
$name = 'local_intelliboard/t47';
$title = get_string('t47', 'local_intelliboard');
$setting = new admin_setting_configselect($name, $title,'','',$options);
$settings->add($setting);

$name = 'local_intelliboard/t16';
$title = get_string('t16', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t17';
$title = get_string('t17', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t18';
$title = get_string('t18', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t19';
$title = get_string('t19', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t20';
$title = get_string('t20', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t21';
$title = get_string('t21', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t22';
$title = get_string('t22', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$settings->add(new admin_setting_heading('local_intelliboard/ts4', get_string('ts4', 'local_intelliboard'), ''));

$name = 'local_intelliboard/t4';
$title = get_string('t4', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t23';
$title = get_string('t23', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t24';
$title = get_string('t24', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t25';
$title = get_string('t25', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t39';
$title = get_string('t39', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t40';
$title = get_string('t40', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t26';
$title = get_string('t26', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t27';
$title = get_string('t27', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t28';
$title = get_string('t28', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t29';
$title = get_string('t29', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);


$name = 'local_intelliboard/t41';
$title = get_string('t41', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t42';
$title = get_string('t42', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t43';
$title = get_string('t43', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t44';
$title = get_string('t44', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t45';
$title = get_string('t45', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);

$name = 'local_intelliboard/t46';
$title = get_string('t46', 'local_intelliboard');
$setting = new admin_setting_configcheckbox($name, $title, '', true, true, false);
$settings->add($setting);
