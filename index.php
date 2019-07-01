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
admin_externalpage_setup('intelliboardcontrolpanel');

$action = optional_param('action', '', PARAM_RAW);
$url = optional_param('url', '', PARAM_URL);
$time = optional_param('time', 'monthly', PARAM_RAW);
$filter = optional_param('filter', 0, PARAM_INT);

$page = optional_param('page', 0, PARAM_INT);
$length = optional_param('length', 10, PARAM_INT);
$type = optional_param('type', '', PARAM_RAW);

if($action == 'noalert'){
	$USER->noalert = true;
}
if($action == 'clear_ntf' and is_siteadmin()){
	$DB->delete_records('local_intelliboard_ntf');
	$DB->delete_records('local_intelliboard_ntf_hst');
	$DB->delete_records('local_intelliboard_ntf_pms');

	redirect(new moodle_url("/local/intelliboard/index.php"), get_string('deleted'));
}


$params = (object) array(
	'filter_user_deleted'=>get_config('local_intelliboard', 'filter1'),
	'filter_user_suspended'=>get_config('local_intelliboard', 'filter2'),
	'filter_user_guest'=>get_config('local_intelliboard', 'filter3'),
	'filter_course_visible'=>get_config('local_intelliboard', 'filter4'),
	'filter_enrolmethod_status'=>get_config('local_intelliboard', 'filter5'),
	'filter_enrol_status'=>get_config('local_intelliboard', 'filter6'),
	'filter_enrolled_users'=>get_config('local_intelliboard', 'filter8'),
	'filter_module_visible'=>get_config('local_intelliboard', 'filter7'),
	'filter_user_active'=> 0,
	'filter_columns'=>get_config('local_intelliboard', 'filter9'),
	'teacher_roles'=>get_config('local_intelliboard', 'filter10'),
	'learner_roles'=>get_config('local_intelliboard', 'filter11'),
	'filter_profile'=>0,
	'sizemode'=> false,
	'debug'=>0,
	'start'=>0,
	'userid'=>0,
	'length'=>10,
	'courseid'=>0,
	'externalid'=>0,
	'filter'=>'',
	'custom'=> s($time),
	'custom2'=> '',
	'custom3'=> '',
	'timestart'=>strtotime('-2 month'),
	'timefinish'=>time()
);
$plugin = new local_intelliboard_external();

if($action == 'report43'){
	$params->length = $length;
	if ($type == 'users' and $page > 1) {
		$params->start = (($page-1) * $length);
	}
	$avg = $plugin->get_dashboard_avg($params);
	$params->timestart = 0;
	$report43 = $plugin->report43($params);
	$page = ($page)?$page:1;
	include("views/report43.php");
	exit;
}elseif($action == 'report44'){
	$params->length = $length;
	if ($type == 'courses' and $page > 1) {
		$params->start = (($page-1) * $length);
	}
	$params->timestart = 0;
	$report44 = $plugin->report44($params);
	$page = ($page)?$page:1;
	include("views/report44.php");
	exit;
}

$intelliboard = intelliboard(['task'=>'dashboard']);

if ($action != 'dashboard' and !$intelliboard->token) {
	redirect(new moodle_url("/local/intelliboard/help.php", array()));
}

if ($action == 'sso' and $intelliboard->token and get_config('local_intelliboard', 'ssomenu')) {
	redirect(intelliboard_url()."auth/sso/".format_string($intelliboard->db)."/".format_string($intelliboard->token));
}


$stat = $plugin->get_dashboard_stats($params);
$LineChart = $plugin->get_site_activity($params);
$countries = $plugin->get_countries($params);
$enrols = $plugin->get_enrols($params);
$params->sizemode = 0;
$totals = $plugin->get_total_info($params);

$json_countries = array();
foreach($countries as $country){
	$json_countries[] = "['".format_string(ucfirst($country->country))."', ".s($country->users)."]";
}
$json_enrols = array();
foreach($enrols as $enrol){
	$json_enrols[] = "['". get_string('pluginname', 'enrol_'.$enrol->enrol)."', ".s($enrol->enrols)."]";
}

$json_data = array();
ksort($LineChart->sessions);

foreach($LineChart->sessions as $item){
	$d = date("j", $item->timepointval);
	$m = date("n", $item->timepointval) - 1;
	$y = date("Y", $item->timepointval);

	$l = $item->pointval;
	$v = (isset($LineChart->enrolments[$item->timepointval])) ? $LineChart->enrolments[$item->timepointval]->pointval : 0;
	$t = (isset($LineChart->completions[$item->timepointval])) ? $LineChart->completions[$item->timepointval]->pointval : 0;
	$json_data[] = "[new Date($y, $m, $d), $l, $t, $v]";
}
$PAGE->requires->jquery();
$PAGE->set_url(new moodle_url("/local/intelliboard/index.php", array()));
$PAGE->set_pagetype('home');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>
<div class="intelliboard-page">
<?php include("views/menu.php"); ?>
<div class="intelliboard-chart clearfix">
	<div class="intelliboard-chart-header">
		<h3><?php echo get_string('users_activity', 'local_intelliboard');?></h3>
		<div class="range">
			<a class="<?php echo ($time == 'daily')?'active':'';?>" href="index.php?time=daily"><?php echo get_string('daily', 'local_intelliboard');?></a>
			<a class="<?php echo ($time == 'weekly')?'active':'';?>" href="index.php?time=weekly"><?php echo get_string('weekly', 'local_intelliboard');?></a>
			<a class="<?php echo ($time == 'monthly')?'active':'';?>" href="index.php?time=monthly"><?php echo get_string('monthly', 'local_intelliboard');?></a>
		</div>
	</div>
	<div class="intelliboard-stats">
		<h4 class="ion-person-stalker"><?php echo get_string('number_of_sessions', 'local_intelliboard');?></h4>
		<p>
			<i class="<?php if(isset($stat[0]->sessions_today)){ echo ($stat[1]->sessions_today<$stat[0]->sessions_today or $stat[1]->sessions_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_today', 'local_intelliboard', (int) (isset($stat[1]->sessions_today)? $stat[1]->sessions_today : 0));?>  &nbsp;
			<i class="<?php if(isset($stat[0]->sessions_week)){ echo ($stat[1]->sessions_week<$stat[0]->sessions_week or $stat[1]->sessions_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int) (isset($stat[1]->sessions_week)? $stat[1]->sessions_week : 0));?>
		</p>

		<h4 class="ion-ribbon-b"><?php echo get_string('course_completions', 'local_intelliboard');?></h4>
		<p>
			<i class="<?php if(isset($stat[0]->compl_today)){ echo ($stat[1]->compl_today<$stat[0]->compl_today or $stat[1]->compl_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_today', 'local_intelliboard', (int) (isset($stat[1]->compl_today )? $stat[1]->compl_today : 0));?>
			<i class="<?php if(isset($stat[0]->compl_week )){ echo ($stat[1]->compl_week<$stat[0]->compl_week or $stat[1]->compl_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int) (isset($stat[1]->compl_week)? $stat[1]->compl_week : 0));?>
		</p>

		<h4 class="ion-university"><?php echo get_string('user_enrolments', 'local_intelliboard');?></h4>
		<p>
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_today<$stat[0]->enrolments_today or $stat[1]->enrolments_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_today', 'local_intelliboard', (int) (isset($stat[1]->enrolments_today)? $stat[1]->enrolments_today : 0));?>
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_week<$stat[0]->enrolments_week or $stat[1]->enrolments_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int) (isset($stat[1]->enrolments_week)? $stat[1]->enrolments_week : 0));?>
		</p>
	</div>
	<div id="intelliboard-chart" class="intelliboard-chart-body"></div>
</div>
<div class="intelliboard-total clearfix">
	<h3><?php echo get_string('total', 'local_intelliboard');?></h3>
	<p><?php echo format_string($totals->users); ?> <span><?php echo get_string('users', 'local_intelliboard');?></span></p>
	<p><?php echo format_string($totals->courses); ?> <span><?php echo get_string('courses', 'local_intelliboard');?></span></p>
	<p><?php echo format_string($totals->modules); ?> <span><?php echo get_string('modules', 'local_intelliboard');?></span></p>
	<p><?php echo format_string($totals->categories); ?> <span><?php echo get_string('categories', 'local_intelliboard');?></span></p>
</div>

<div class="intelliboard-box">
	<div class="box60 pull-left">
		<h3><?php echo get_string('users_overview', 'local_intelliboard');?></h3>

		<div class="ajax-widget" id="report43"><?php echo get_string('loading', 'local_intelliboard');?></div>
	</div>
	<div class="box40 pl15 pull-right">
		<h3><?php echo get_string('enrollments', 'local_intelliboard');?></h3>
		<div id="enrolments" style="width: 100%; height:300px;"></div>
	</div>
</div>
<div class="intelliboard-box">
	<div class="box45 pull-left">
		<h3><?php echo get_string('registrations', 'local_intelliboard');?></h3>
		<div id="countries" style="width:100% height:400px;"></div>
	</div>
	<div class="box50 pull-right">
		<h3><?php echo get_string('participation', 'local_intelliboard');?></h3>
		<div class="ajax-widget" id="report44"><?php echo get_string('loading', 'local_intelliboard');?></div>
	</div>
</div>
<?php include("views/footer.php"); ?>
</div>

<script type="text/javascript"
          src="https://www.google.com/jsapi?autoload={
            'modules':[{
              'name':'visualization',
              'version':'1',
              'packages':['corechart','geochart']
            }]
          }"></script>
<script type="text/javascript">
	google.setOnLoadCallback(drawChart);
	function drawChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('date', '<?php echo intellitext(get_string('time', 'local_intelliboard'));?>');
		data.addColumn('number', '<?php echo intellitext(get_string('number_of_sessions', 'local_intelliboard'));?>');
		data.addColumn('number', '<?php echo intellitext(get_string('course_completions', 'local_intelliboard'));?>');
		data.addColumn('number', '<?php echo intellitext(get_string('user_enrolments', 'local_intelliboard'));?>');
		data.addRows([<?php echo ($json_data) ? implode(",", $json_data):"";?>]);

		var options = {
			chartArea: {
				width: '90%',
				right:0,
				top:10
			},
			height: 280,
			hAxis: {
			    type: 'category',
				format: 'dd MMM',
				gridlines: {color: 'none'}
			},
			vAxis: {
				gridlines: {count: 5},
				minValue: 0
			},
			backgroundColor:{fill:'transparent'},
			legend: { position: 'bottom' }
		};
		var chart = new google.visualization.LineChart(document.getElementById('intelliboard-chart'));
		chart.draw(data, options);
	}

	google.setOnLoadCallback(drawRegionsMap);
	function drawRegionsMap() {
		var data = google.visualization.arrayToDataTable([['<?php echo intellitext(get_string('country'));?>', '<?php echo intellitext(get_string('users', 'local_intelliboard'));?>'], <?php echo ($json_countries) ? implode(",", $json_countries):"";?>]);
		var chart = new google.visualization.GeoChart(document.getElementById('countries'));
		chart.draw(data, {backgroundColor:{fill:'transparent'}});
	}

	google.setOnLoadCallback(drawEnrolments);
	function drawEnrolments() {
		var data = google.visualization.arrayToDataTable([['<?php echo intellitext(get_string('enrolment_method', 'local_intelliboard'));?>', '<?php echo intellitext(get_string('users', 'local_intelliboard'));?>'], <?php echo ($json_enrols) ? implode(",", $json_enrols):"";?> ]);
		var options = {
			backgroundColor:{fill:"transparent"},
			title: '',
			pieHole: 0.4,
			chartArea: {
				width: '100%'
			}
		};
		var chart = new google.visualization.PieChart(document.getElementById('enrolments'));
		chart.draw(data, options);
	}
	jQuery(document).ready(function(){
		jQuery('#report43').load('<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=report43&type=users&page=<?php echo $page; ?>&type=<?php echo $type; ?>');
		jQuery('#report44').load('<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=report44&type=users&page=<?php echo $page; ?>&type=<?php echo $type; ?>');
	});
</script>
<?php
echo $OUTPUT->footer();
