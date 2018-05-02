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

if($url){
	redirect("$url&confirm=".get_config('local_intelliboard', 'te1'));
	return;
}
$params = array(
	'reports'=>get_config('local_intelliboard', 'reports'),
	'filter'=>$filter
);
if($action == 'enable_report_time'){
	set_config("report_time", 0, "local_intelliboard");
}
if($action == 'disable_report_time'){
	set_config("report_time", 1, "local_intelliboard");
}
$report_time = get_config('local_intelliboard', 'report_time');
$sizemode = get_config('local_intelliboard', 'sizemode');

if($action == 'noalert'){
	$USER->noalert = true;
}elseif($action == 'signup' or $action == 'setup'){
	$webservice = $DB->get_record_sql("SELECT token FROM {external_services} exs, {external_tokens} ext WHERE exs.component='local_intelliboard' AND ext.externalserviceid = exs.id");
	$params['token'] = (isset($webservice->token)) ? $webservice->token : 'none';
	$params['site'] = format_string($SITE->fullname);
	$params['do'] = $action;
	$params['agreement'] = true;
}
$intelliboard = intelliboard($params);
$params = (object) array(
	'filter_user_deleted'=>get_config('local_intelliboard', 'filter1'),
	'filter_user_suspended'=>get_config('local_intelliboard', 'filter2'),
	'filter_user_guest'=>get_config('local_intelliboard', 'filter3'),
	'filter_course_visible'=>get_config('local_intelliboard', 'filter4'),
	'filter_enrolmethod_status'=>get_config('local_intelliboard', 'filter5'),
	'filter_enrol_status'=>get_config('local_intelliboard', 'filter6'),
	'filter_enrolled_users'=>get_config('local_intelliboard', 'filter8'),
	'filter_module_visible'=>get_config('local_intelliboard', 'filter7'),
	'filter_columns'=>get_config('local_intelliboard', 'filter9'),
	'teacher_roles'=>get_config('local_intelliboard', 'filter10'),
	'learner_roles'=>get_config('local_intelliboard', 'filter11'),
	'filter_profile'=>0,
	'sizemode'=>$sizemode,
	'start'=>0,
	'userid'=>0,
	'length'=>10,
	'courseid'=>0,
	'filter'=>'',
	'custom'=> s($time),
	'custom2'=> '',
	'custom3'=> '',
	'timestart'=>strtotime('-6 month'),
	'timefinish'=>time()
);
$plugin = new local_intelliboard_external();

if($action == 'report43'){
	if(!$sizemode){
		$avg = $plugin->get_dashboard_avg($params);
	}else{
		$avg = null;
	}

	$params->timestart = 0;
	$params->sizemode = $report_time;
	$report43 = $plugin->report43($params);
	include("views/report43.php");
	exit;
}elseif($action == 'report44'){
	$params->timestart = 0;
	$report44 = $plugin->report44($params);
	include("views/report44.php");
	exit;
}



$stat = $plugin->get_dashboard_stats($params);
$LineChart = $plugin->get_dashboard_info($params);
$countries = $plugin->get_dashboard_countries($params);
$enrols = $plugin->get_dashboard_enrols($params);
$params->sizemode = 1;
$totals = $plugin->get_total_info($params);

$json_countries = array();
foreach($countries as $country){
	$json_countries[] = "['".format_string(ucfirst($country->country))."', ".s($country->users)."]";
}
$json_enrols = array();
foreach($enrols as $enrol){
	$json_enrols[] = "['".format_string(ucfirst($enrol->enrol))."', ".s($enrol->enrols)."]";
}

$json_data = array();
ksort($LineChart[2]);
foreach($LineChart[2] as $item){
	$d = date("j", $item->timepointval);
	$m = date("n", $item->timepointval) - 1;
	$y = date("Y", $item->timepointval);

	$l = $item->visits;
	$v = (isset($LineChart[3][$item->timepointval])) ? $LineChart[3][$item->timepointval]->users : 0;
	$t = (isset($LineChart[4][$item->timepointval])) ? $LineChart[4][$item->timepointval]->users : 0;
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
            <?php echo get_string('number_today', 'local_intelliboard', (int)$stat[1]->sessions_today);?>  &nbsp;
			<i class="<?php if(isset($stat[0]->sessions_week)){ echo ($stat[1]->sessions_week<$stat[0]->sessions_week or $stat[1]->sessions_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int)$stat[1]->sessions_week);?>
		</p>

		<h4 class="ion-ribbon-b"><?php echo get_string('course_completions', 'local_intelliboard');?></h4>
		<p>
			<i class="<?php if(isset($stat[0]->compl_today)){ echo ($stat[1]->compl_today<$stat[0]->compl_today or $stat[1]->compl_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_today', 'local_intelliboard', (int)$stat[1]->compl_today);?>
			<i class="<?php if(isset($stat[0]->compl_week )){ echo ($stat[1]->compl_week<$stat[0]->compl_week or $stat[1]->compl_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int)$stat[1]->compl_week);?>
		</p>

		<h4 class="ion-university"><?php echo get_string('user_enrolments', 'local_intelliboard');?></h4>
		<p>
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_today<$stat[0]->enrolments_today or $stat[1]->enrolments_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_today', 'local_intelliboard', (int)$stat[1]->enrolments_today);?>
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_week<$stat[0]->enrolments_week or $stat[1]->enrolments_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
            <?php echo get_string('number_this_week', 'local_intelliboard', (int)$stat[1]->enrolments_week);?>
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
		<h3><?php echo get_string('users_overview', 'local_intelliboard');?> <a href="<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=<?php echo ($report_time)?'enable_report_time':'disable_report_time'; ?>" style="opacity: 0.4; font-size: 19px;" title="<?php echo ($report_time)?get_string('enable_time_and_visits_users_overview', 'local_intelliboard'):get_string('disable_time_and_visits_users_overview', 'local_intelliboard'); ?>"><i class="<?php echo ($report_time)?'ion-android-radio-button-off':'ion-android-checkmark-circle'; ?>"></i></a></h3>

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
		data.addColumn('date', '<?php echo get_string('time', 'local_intelliboard');?>');
		data.addColumn('number', '<?php echo get_string('number_of_sessions', 'local_intelliboard');?>');
		data.addColumn('number', '<?php echo get_string('course_completions', 'local_intelliboard');?>');
		data.addColumn('number', '<?php echo get_string('user_enrolments', 'local_intelliboard');?>');
		data.addRows([<?php echo ($json_data) ? implode(",", $json_data):"";?>]);

		var options = {
			chartArea: {
				width: '90%',
				right:0,
				top:10
			},
			height: 280,
			hAxis: {
				format: '<?php echo s($LineChart[0]); ?>',
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
		var data = google.visualization.arrayToDataTable([['<?php echo get_string('country');?>', '<?php echo get_string('users', 'local_intelliboard');?>'], <?php echo ($json_countries) ? implode(",", $json_countries):"";?>]);
		var chart = new google.visualization.GeoChart(document.getElementById('countries'));
		chart.draw(data, {backgroundColor:{fill:'transparent'}});
	}

	google.setOnLoadCallback(drawEnrolments);
	function drawEnrolments() {
		var data = google.visualization.arrayToDataTable([['<?php echo get_string('enrolment_method', 'local_intelliboard');?>', '<?php echo get_string('users', 'local_intelliboard');?>'], <?php echo ($json_enrols) ? implode(",", $json_enrols):"";?> ]);
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
		jQuery('#report43').load('<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=report43');
		jQuery('#report44').load('<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=report44');
	});
</script>
<?php
echo $OUTPUT->footer();
