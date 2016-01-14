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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir.'/adminlib.php');
require('externallib.php');

require_login();
require_capability('local/intelliboard:view', context_system::instance());
admin_externalpage_setup('intelliboardcontrolpanel');

$action = optional_param('action', '', PARAM_RAW);
$url = optional_param('url', '', PARAM_RAW);
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
if($action == 'noalert'){
	$USER->noalert = true;
}elseif($action == 'signup' or $action == 'setup'){
	$webservice = $DB->get_record_sql("SELECT token FROM {external_services} exs, {external_tokens} ext WHERE exs.component='local_intelliboard' AND ext.externalserviceid = exs.id");
	$params['token'] = (isset($webservice->token)) ? $webservice->token : 'none';
	$params['site'] = $SITE->fullname;
	$params['do'] = $action;
	$params['agreement'] = true;
}
$c = new curl;
$intelliboard = json_decode($c->post('http://intelliboard.net/dashboard/api', $params));

$params = (object) array(
	'start'=>0,
	'userid'=>0,
	'length'=>10,
	'courseid'=>0,
	'filter'=>'',
	'custom'=> $time,
	'timestart'=>strtotime('-6 month'),
	'timefinish'=>time()
);
$class = 'local_intelliboard_external';
$plugin = new $class();
$plugin->teacher_roles = '3,4';
$plugin->learner_roles = '5';

$stat = $plugin->get_dashboard_stats($params);
$LineChart = $plugin->get_dashboard_info($params);
$countries = $plugin->get_dashboard_countries($params);
$enrols = $plugin->get_dashboard_enrols($params);
$courses = $plugin->get_dashboard_courses($params);
$totals = $plugin->get_total_info($params);
$avg = $plugin->get_dashboard_avg($params);

$params->timestart = 0;
$report43 = $plugin->report43($params);
$report44 = $plugin->report44($params);

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
		<p><i class="<?php echo ($stat[1]->sessions_today<$stat[0]->sessions_today or $stat[1]->sessions_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->sessions_today; ?> today &nbsp;  <i class="<?php echo ($stat[1]->sessions_week<$stat[0]->sessions_week or $stat[1]->sessions_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->sessions_week; ?> this week</p>
		<h4 class="ion-ribbon-b"> Course completions</h4>
		<p><i class="<?php echo ($stat[1]->compl_today<$stat[0]->compl_today or $stat[1]->compl_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->compl_today; ?> today &nbsp; <i class="<?php echo ($stat[1]->compl_week<$stat[0]->compl_week or $stat[1]->compl_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->compl_week; ?> this week</p>
		<h4 class="ion-university"> User Enrolments</h4>
		<p><i class="<?php echo ($stat[1]->enrolments_today<$stat[0]->enrolments_today or $stat[1]->enrolments_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->enrolments_today; ?> today &nbsp; <i class="<?php echo ($stat[1]->enrolments_week<$stat[0]->enrolments_week or $stat[1]->enrolments_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat[1]->enrolments_week; ?> this week</p>
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
		<h3>Users Overview</h3>
		<table class="table">
			<thead>
				<tr>
					<th>Name</th>
					<th align="center">Progress</th>
					<th align="center">Score</th>
					<th align="center">Visits</th>
					<th align="center">Time Spent</th>
					<th align="center">Registered</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($report43['data'] as $row): ?>
				<tr>
					<td><a href="<?php echo $CFG->wwwroot; ?>/user/profile.php?id=<?php echo $row->id; ?>"><?php echo $row->user; ?></a></td>
					<td align="center" class="intelliboard-tooltip" title="<?php echo "Enrolled: ".intval($row->courses).", Competed: ".intval($row->completed_courses); ?>">
						<div class="intelliboard-progress xl"><span style="width:<?php echo ($row->completed_courses) ? (($row->completed_courses / $row->courses) * 100) : 0; ?>%"></span></div>
					</td>
					<td align="center" class="intelliboard-tooltip" title="<?php echo "$row->user grade: ".intval($row->grade).", Average grade: ".intval($avg->grade_site); ?>"><span class='<?php echo ($avg->grade_site > $row->grade) ? "down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left"; ?>'> <?php echo (int)$row->grade; ?></span></td>
					<td align="center" class="intelliboard-tooltip" title="<?php echo "$row->user visits: ".intval($row->visits).", Average visits: ".intval($avg->visits_site); ?>"><span class='<?php echo ($avg->visits_site > $row->visits) ? "down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left"; ?>'> <?php echo (int)$row->visits; ?></span></td>
					<td align="center" class="intelliboard-tooltip" title="<?php echo "$row->user time: ".gmdate("H:i:s", $row->timespend).", Average time: ".gmdate("H:i:s", $avg->timespend_site); ?>"><span class='<?php echo ($avg->timespend_site > $row->timespend) ? "down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left"; ?>'> <?php echo gmdate("H:i:s", $row->timespend); ?></span></td>
					<td><?php echo date("m/d/Y", $row->timecreated); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="6">
						<a style="float:left" href="learners.php">More users</a>
						<span style="float:right;color:#ddd;">Showing 1 to 10 of <?php echo $report43['recordsTotal']; ?></span>
					</td>
				</tr>
			</tfoot>
		</table>
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
		<ul class="intelliboard-list">
			<?php foreach($report44['data'] as $row):  ?>
			<li class="intelliboard-tooltip" title="<?php echo "Enrolled users: $row->users, Competed: $row->completed"; ?>">
				<?php echo $row->fullname; ?>
				<span class="pull-right"><?php echo (int) $row->completed; ?>/<?php echo (int) $row->users; ?></span>
				<div class="intelliboard-progress"><span style="width:<?php echo ($row->completed) ? (($row->completed / $row->users) * 100) : 0; ?>%"></span></div>
			</li>
			<?php endforeach; ?>
			<li class="clearfix"><a style="float:left" href="courses.php">More courses</a>
				<span style="float:right;color:#ddd;">Showing 1 to 10 of <?php echo $report44['recordsTotal']; ?></span>
			</li>
		</ul>
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
		chart.draw(data, {});
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
</script>
<?php
echo $OUTPUT->footer();
