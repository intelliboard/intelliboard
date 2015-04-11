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
// IntelliBoard.net is built as a plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	local_intelliboard
 * @copyright  	2014-2015 SEBALE LLC
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	SEBALE LLC
 * @website		www.intelliboard.net
 */

defined('MOODLE_INTERNAL') || die;

$settings = new admin_settingpage('local_intelliboard', get_string('settings', 'local_intelliboard'));

if (!$ADMIN->locate('intelliboard')){
	$ADMIN->add('localplugins', new admin_category('intelliboard', get_string('pluginname', 'local_intelliboard')));
	$ADMIN->add('intelliboard', $settings);
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