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

require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/instructor/lib.php');

require_login();
intelliboard_instructor_access();

$email = get_config('local_intelliboard', 'te1');
$params = array(
	'url'=>$CFG->wwwroot,
	'email'=>s($email),
	'firstname'=>s($USER->firstname),
	'lastname'=>s($USER->lastname),
	'do'=>'help',
	'mode'=> 2
);
$intelliboard = intelliboard($params);

$PAGE->set_pagetype('help');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url("/local/intelliboard/instructor/help.php"));
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');

echo $OUTPUT->header();
?>
<div class="intelliboard-page intelliboard-instructor">
	<?php include("views/menu.php"); ?>
		<div class="intelliboard-content">
			<p><?php echo get_string('click_link_below_support_pages','local_intelliboard'); ?></p>
			<p><a class="btn btn-default" target="_blank" href="https://support.intelliboard.net/hc/en-us/categories/200112249-Teacher-and-Learner-Dashboards"><?php echo get_string('support','local_intelliboard'); ?></a></p>
		</div>
	<?php include("../views/footer.php"); ?>
</div>

<?php echo $OUTPUT->footer();
