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

if($time == 'daily'){
	$timefinish = time();
	$timestart = strtotime('last week');
	$ext = 86400;
	$format = 'EEEE';
}elseif($time == 'weekly'){
	$timefinish = time();
	$timestart = strtotime('last month');
	$ext = 86400;
	$format = 'dd MMM';
}elseif($time == 'monthly'){
	$timefinish = time();
	$timestart = strtotime('-12 month');
	$ext = 604800;
	$format = 'MMMM';
}else{
	$timefinish = strtotime('+1 year');
	$timestart = strtotime('-5 years');
	$ext = 31556926;
	$format = 'yyyy';
}

if($CFG->version < 2014051200){
	$table = "log";
	$table_time = "time";
}else{
	$table = "logstore_standard_log";
	$table_time = "timecreated";
}
		
$countries = $DB->get_records_sql("SELECT country, count(*) as users FROM {user} WHERE confirmed = 1 and deleted = 0 and suspended = 0 and country != '' GROUP BY country");
$json_countries = array();
foreach($countries as $country){
	$json_countries[] = "['".ucfirst($country->country)."', $country->users]";
}

$enrols = $DB->get_records_sql("SELECT e.id, e.enrol, count(ue.id) as enrols FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid GROUP BY e.enrol");
$json_enrols = array();
foreach($enrols as $enrol){
	$json_enrols[] = "['".ucfirst($enrol->enrol)."', $enrol->enrols]";
}

$timeyesterday = strtotime('yesterday');
$timelastweek = strtotime('last week');
$timetoday = strtotime('today');
$timeweek = strtotime('previous monday');

$stat = $DB->get_record_sql("SELECT
	(SELECT COUNT(DISTINCT (userid)) FROM {$CFG->prefix}$table WHERE $table_time BETWEEN $timeyesterday AND $timetoday) as sessions_today,
	(SELECT COUNT(DISTINCT (userid)) FROM {$CFG->prefix}$table WHERE $table_time BETWEEN $timelastweek AND $timeweek) as sessions_week,
	(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timeyesterday AND $timetoday) as enrolments_today,
	(SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN $timelastweek AND $timeweek) as enrolments_week,
	(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timeyesterday AND $timetoday) as compl_today,
	(SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN $timelastweek AND $timeweek) as compl_week");
	
$stat1 = $DB->get_record_sql("SELECT
	(SELECT COUNT(DISTINCT (userid)) FROM {$CFG->prefix}$table WHERE $table_time BETWEEN $timetoday AND $timefinish) as sessions_today,
	(SELECT COUNT(DISTINCT (userid)) FROM {$CFG->prefix}$table WHERE $table_time BETWEEN $timeweek AND $timefinish) as sessions_week,
	(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN $timetoday AND $timefinish) as enrolments_today,
	(SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN $timeweek AND $timefinish) as enrolments_week,
	(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN $timetoday AND $timefinish) as compl_today,
	(SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN $timeweek AND $timefinish) as compl_week");

$data = $DB->get_records_sql("
	(SELECT floor($table_time / $ext) * $ext as $table_time, COUNT(DISTINCT (userid)) as visits
		FROM {$CFG->prefix}$table
			WHERE $table_time BETWEEN $timestart AND $timefinish
				GROUP BY floor($table_time / $ext) * $ext
					ORDER BY $table_time ASC)");
					
$data2 = $DB->get_records_sql("
	(SELECT floor(timecreated / $ext) * $ext as timecreated, COUNT(DISTINCT (userid)) as users
		FROM {user_enrolments}
			WHERE timecreated BETWEEN $timestart AND $timefinish
				GROUP BY floor(timecreated / $ext) * $ext
					ORDER BY timecreated ASC)");
					
$data3 = $DB->get_records_sql("
	(SELECT floor(timecompleted / $ext) * $ext as timecreated, COUNT(DISTINCT (userid)) as users
		FROM {course_completions}
			WHERE timecompleted BETWEEN $timestart AND $timefinish
				GROUP BY floor(timecompleted / $ext) * $ext
					ORDER BY timecompleted ASC)");

$json_data = array();
foreach($data as $item){
	$d = date("j", $item->{$table_time});
	$m = date("n", $item->{$table_time}) - 1;
	$y = date("Y", $item->{$table_time});
	
	
	$l = $item->visits;
	$v = (isset($data2[$item->timecreated])) ? $data2[$item->timecreated]->users : 0;
	$t = (isset($data3[$item->timecreated])) ? $data3[$item->timecreated]->users : 0;
	$json_data[] = "[new Date($y, $m, $d), $l, $t, $v]";
}

$totals = $DB->get_record_sql("SELECT
	(SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND suspended = 0 and username != 'guest') as users,
	(SELECT COUNT(*) FROM {course} WHERE visible = 1 and category > 0) as courses,
	(SELECT COUNT(*) FROM {course_modules} WHERE visible = 1 ) as modules,
	(SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 ) as categories");

$avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site FROM
	(SELECT round(avg(b.timespend_site),0) as timespend_site, round(avg(b.visits_site),0) as visits_site
		FROM (SELECT sum(timespend) as timespend_site, sum(visits) as visits_site 
			FROM {$CFG->prefix}local_intelliboard_tracking 
			WHERE userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN (5)) and userid != $USER->id GROUP BY userid) as b) a, 
	(SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT AVG( (g.finalgrade/g.rawgrademax)*100) AS grade 
		FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g 
		WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid NOT IN (SELECT distinct userid FROM {$CFG->prefix}role_assignments WHERE roleid NOT  IN (5)) and g.userid != $USER->id GROUP BY g.userid) b) c");

$users = $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) as user,  u.email,  u.timecreated, ue.courses,  round(gc.grade, 2) as grade,  cm.completed_courses, lit.timespend, lit.visits
		FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user as u
			LEFT JOIN (SELECT ue.userid, count(DISTINCT e.courseid) as courses FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid AND ue.status = 0 and e.status = 0 GROUP BY ue.userid) as ue ON ue.userid = u.id
			LEFT JOIN (SELECT userid, count(id) as completed_courses FROM {course_completions} cc WHERE timecompleted > 0 GROUP BY userid) as cm ON cm.userid = u.id
			LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id							
			LEFT JOIN (SELECT l.userid, sum(l.timespend) as timespend, sum(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) as lit ON lit.userid = u.id							
			WHERE ra.roleid IN (5) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 ORDER BY u.lastaccess DESC LIMIT 0, 10");

$courses = $DB->get_records_sql("SELECT c.id, c.fullname, count(*) users, cc.completed 
		FROM {user_enrolments} ue 
		LEFT JOIN {enrol} e ON e.id = ue.enrolid 
		LEFT JOIN {course} c ON c.id = e.courseid 
		LEFT JOIN (SELECT course, count(*) as completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) cc ON cc.course = e.courseid 
		WHERE ue.status = 0 and e.status = 0 GROUP BY e.courseid ORDER BY cc.completed DESC LIMIT 0, 10");

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
		<p><i class="<?php echo ($stat1->sessions_today<$stat->sessions_today or $stat1->sessions_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->sessions_today; ?> today &nbsp;  <i class="<?php echo ($stat1->sessions_week<$stat->sessions_week or $stat1->sessions_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->sessions_week; ?> this week</p>
		<h4 class="ion-ribbon-b"> Course completions</h4>
		<p><i class="<?php echo ($stat1->compl_today<$stat->compl_today or $stat1->compl_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->compl_today; ?> today &nbsp; <i class="<?php echo ($stat1->compl_week<$stat->compl_week or $stat1->compl_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->compl_week; ?> this week</p>
		<h4 class="ion-university"> User Enrolments</h4>
		<p><i class="<?php echo ($stat1->enrolments_today<$stat->enrolments_today or $stat1->enrolments_today ==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->enrolments_today; ?> today &nbsp; <i class="<?php echo ($stat1->enrolments_week<$stat->enrolments_week or $stat1->enrolments_week==0)?'down ion-arrow-graph-down-left':'up ion-arrow-graph-up-left'; ?>"></i> <?php echo $stat1->enrolments_week; ?> this week</p>
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
				<?php foreach($users as $row): ?>
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
					<td colspan="6"><a href="learners.php">More users</a></td>
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
		<h3>Registration</h3>
		<div id="countries" style="width:100% height:400px;"></div>
	</div>
	<div class="box50 pull-right">
		<h3>Participation</h3>
		<ul class="intelliboard-list">
			<?php foreach($courses as $row): ?>
			<li class="intelliboard-tooltip" title="<?php echo "Enrolled users: $row->users, Competed: $row->completed"; ?>">
				<?php echo $row->fullname; ?>
				<span class="pull-right"><?php echo (int) $row->completed; ?>/<?php echo (int) $row->users; ?></span>
				<div class="intelliboard-progress"><span style="width:<?php echo ($row->completed) ? (($row->completed / $row->users) * 100) : 0; ?>%"></span></div>
			</li>
			<?php endforeach; ?>
			<li><a href="courses.php">More courses</a></li>
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
				format: '<?php echo $format; ?>',
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