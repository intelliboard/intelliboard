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
 * IntelliBoard.net
 *
 *
 * @package    local_intelliboard
 * @copyright  2014 SEBALE LLC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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