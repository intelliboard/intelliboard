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
	redirect("$url&confirm=$USER->email");
	return;
}
$params = array(
	'url'=>$CFG->wwwroot,
	'email'=>$USER->email,
	'firstname'=>$USER->firstname,
	'lastname'=>$USER->lastname,
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
	$params['site'] = $SITE->fullname;
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
	'sizemode'=>$sizemode,
	'start'=>0,
	'userid'=>0,
	'length'=>10,
	'courseid'=>0,
	'filter'=>'',
	'custom'=> $time,
	'custom2'=> '',
	'custom3'=> '',
	'timestart'=>strtotime('-6 month'),
	'timefinish'=>time()
);
$class = 'local_intelliboard_external';
$plugin = new $class();
$plugin->teacher_roles = '3,4';
$plugin->learner_roles = '5';

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
	$json_countries[] = "['".ucfirst($country->country)."', $country->users]";
}
$json_enrols = array();
foreach($enrols as $enrol){
	$json_enrols[] = "['".ucfirst($enrol->enrol)."', $enrol->enrols]";
}

$json_data = array();
foreach($LineChart[2] as $item){
	$d = date("j", $item->timepoint);
	$m = date("n", $item->timepoint) - 1;
	$y = date("Y", $item->timepoint);

	$l = $item->visits;
	$v = (isset($LineChart[3][$item->timepoint])) ? $LineChart[3][$item->timepoint]->users : 0;
	$t = (isset($LineChart[4][$item->timepoint])) ? $LineChart[4][$item->timepoint]->users : 0;
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
		<h3>Users activity</h3>
		<div class="range">
			<a class="<?php echo ($time == 'daily')?'active':'';?>" href="index.php?time=daily">Daily</a>
			<a class="<?php echo ($time == 'weekly')?'active':'';?>" href="index.php?time=weekly">Weekly</a>
			<a class="<?php echo ($time == 'monthly')?'active':'';?>" href="index.php?time=monthly">Monthly</a>
		</div>
	</div>
	<div class="intelliboard-stats">
		<h4 class="ion-person-stalker"> Number of sessions</h4>
		<p>
			<i class="<?php if(isset($stat[0]->sessions_today)){ echo ($stat[1]->sessions_today<$stat[0]->sessions_today or $stat[1]->sessions_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->sessions_today; ?> today &nbsp;
			<i class="<?php if(isset($stat[0]->sessions_week)){ echo ($stat[1]->sessions_week<$stat[0]->sessions_week or $stat[1]->sessions_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->sessions_week; ?> this week
		</p>

		<h4 class="ion-ribbon-b"> Course completions</h4>
		<p>
			<i class="<?php if(isset($stat[0]->compl_today)){ echo ($stat[1]->compl_today<$stat[0]->compl_today or $stat[1]->compl_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->compl_today; ?> today &nbsp;
			<i class="<?php if(isset($stat[0]->compl_week )){ echo ($stat[1]->compl_week<$stat[0]->compl_week or $stat[1]->compl_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->compl_week; ?> this week
		</p>

		<h4 class="ion-university"> User Enrolments</h4>
		<p>
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_today<$stat[0]->enrolments_today or $stat[1]->enrolments_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->enrolments_today; ?> today &nbsp;
			<i class="<?php if(isset($stat[0]->enrolments_today)){echo ($stat[1]->enrolments_week<$stat[0]->enrolments_week or $stat[1]->enrolments_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left';} ?>"></i>
			<?php echo (int)$stat[1]->enrolments_week; ?> this week
		</p>
	</div>
	<div id="intelliboard-chart" class="intelliboard-chart-body"></div>
</div>
<div class="intelliboard-total clearfix">
	<h3>Total</h3>
	<p><?php echo $totals->users; ?> <span>Users</span></p>
	<p><?php echo $totals->courses; ?> <span>Courses</span></p>
	<p><?php echo $totals->modules; ?> <span>Modules</span></p>
	<p><?php echo $totals->categories; ?> <span>Categories</span></p>
</div>

<div class="intelliboard-box">
	<div class="box60 pull-left">
		<h3>Users Overview <a href="<?php echo $CFG->wwwroot; ?>/local/intelliboard/index.php?action=<?php echo ($report_time)?'enable_report_time':'disable_report_time'; ?>" style="opacity: 0.4; font-size: 19px;" title="<?php echo ($report_time)?'Enable':'Disable'; ?> time spent and visits in Users Overview"><i class="<?php echo ($report_time)?'ion-android-radio-button-off':'ion-android-checkmark-circle'; ?>"></i></a></h3>

		<div class="ajax-widget" id="report43">Loading...</div>
	</div>
	<div class="box40 pl15 pull-right">
		<h3>Enrollments</h3>
		<div id="enrolments" style="width: 100%; height:300px;"></div>
	</div>
</div>
<div class="intelliboard-box">
	<div class="box45 pull-left">
		<h3>Registrations</h3>
		<div id="countries" style="width:100% height:400px;"></div>
	</div>
	<div class="box50 pull-right">
		<h3>Participation</h3>
		<div class="ajax-widget" id="report44">Loading...</div>
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
		data.addColumn('date', 'Time');
		data.addColumn('number', 'Number of sessions');
		data.addColumn('number', 'Course completions');
		data.addColumn('number', 'User Enrolments');
		data.addRows([<?php echo ($json_data) ? implode(",", $json_data):"";?>]);

		var options = {
			chartArea: {
				width: '90%',
				right:0,
				top:10
			},
			height: 280,
			hAxis: {
				format: '<?php echo $LineChart[0]; ?>',
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
		var data = google.visualization.arrayToDataTable([['Country', 'Users'], <?php echo ($json_countries) ? implode(",", $json_countries):"";?>]);
		var chart = new google.visualization.GeoChart(document.getElementById('countries'));
		chart.draw(data, {backgroundColor:{fill:'transparent'}});
	}

	google.setOnLoadCallback(drawEnrolments);
	function drawEnrolments() {
		var data = google.visualization.arrayToDataTable([['Enrolment Method', 'Users'], <?php echo ($json_enrols) ? implode(",", $json_enrols):"";?> ]);
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
