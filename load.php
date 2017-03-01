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
admin_externalpage_setup('intelliboardload');

$params = (object) array(
	'filter_user_deleted'=>get_config('local_intelliboard', 'filter1'),
	'filter_user_suspended'=>get_config('local_intelliboard', 'filter2'),
	'filter_user_guest'=>get_config('local_intelliboard', 'filter3'),
	'filter_course_visible'=>get_config('local_intelliboard', 'filter4'),
	'filter_enrolmethod_status'=>get_config('local_intelliboard', 'filter5'),
	'filter_enrol_status'=>get_config('local_intelliboard', 'filter6'),
	'filter_enrolled_users'=>get_config('local_intelliboard', 'filter8'),
	'filter_module_visible'=>get_config('local_intelliboard', 'filter7'),
	'teacher_roles'=>get_config('local_intelliboard', 'filter10'),
	'learner_roles'=>get_config('local_intelliboard', 'filter11'),
	'sizemode'=> get_config('local_intelliboard', 'sizemode'),
	'userid'=>0,
	'courseid'=>0,
	'timestart'=> strtotime('-6 month'),
	'timefinish'=>time()
);
$plugin = new local_intelliboard_external();

$data  = array(
	17 => json_encode($plugin->get_system_load($params)),
	10 => json_encode($plugin->get_module_visits($params)),
	11 => json_encode($plugin->get_active_ip_users($params)),
	12 => json_encode($plugin->get_size_courses($params)),
	24 => json_encode($plugin->get_useragents($params)),
	25 => json_encode($plugin->get_userlang($params)),
	26 => json_encode($plugin->get_useros($params))
);

$params = array(
	'reports'=>get_config('local_intelliboard', 'reports'),
	'data'=>json_encode($data),
	'type'=>'load',
	'do'=>'widgets'
);
$intelliboard = intelliboard($params);
$PAGE->set_url(new moodle_url("/local/intelliboard/load.php"));
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('load');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>
<script type="text/javascript"
          src="https://www.google.com/jsapi?autoload={
            'modules':[{
              'name':'visualization',
              'version':'1',
			  'language':'en',
              'packages':['corechart']
            }]
          }"></script>
<div class="intelliboard-page">
	<?php include("views/menu.php"); ?>
	<div class="intelliboard-content"><?php echo intelliboard_clean($intelliboard->content); ?></div>
	<?php include("views/footer.php"); ?>
</div>
<?php
echo $OUTPUT->footer();
