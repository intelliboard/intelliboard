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
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot .'/local/intelliboard/externallib.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');

require_login();
require_capability('local/intelliboard:view', context_system::instance());
admin_externalpage_setup('intelliboardsettings');

$reports = optional_param_array('report', array(), PARAM_INT);

if($reports){
	set_config("reports", implode(",", $reports), "local_intelliboard");
}

$params = array(
	'url'=>$CFG->wwwroot,
    'email'=>s($USER->email),
    'firstname'=>s($USER->firstname),
    'lastname'=>s($USER->lastname),
	'reports'=>get_config('local_intelliboard', 'reports'),
	'type'=>'settings',
	'do'=>'reportslist'
);
$intelliboard = intelliboard($params);
$PAGE->set_url(new moodle_url("/local/intelliboard/settings.php"));
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('settings');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>
<div class="intelliboard-page">
	<?php include("views/menu.php"); ?>
	<div class="intelliboard-content"><?php echo intelliboard_clean($intelliboard->content); ?></div>
	<a href="<?php echo $CFG->wwwroot; ?>/admin/settings.php?section=local_intelliboard"><?php echo get_string('adv_settings','local_intelliboard');?></a>
	<?php include("views/footer.php"); ?>
</div>
<?php
echo $OUTPUT->footer();
