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

require('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once('../locallib.php');
require_once('lib.php');

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if(!get_config('local_intelliboard', 't1')){
	throw new moodle_exception('invalidaccess', 'error');
}elseif(!get_config('local_intelliboard', 't2')){
	if(get_config('local_intelliboard', 't3')){
		redirect("$CFG->wwwroot/local/intelliboard/student/courses.php");
	}if(get_config('local_intelliboard', 't4')){
		redirect("$CFG->wwwroot/local/intelliboard/student/grades.php");
	}
	throw new moodle_exception('invalidaccess', 'error');
}

$c = new curl;
$email = get_config('local_intelliboard', 'te1');
$params = array('url'=>$CFG->wwwroot,'email'=>$email,'firstname'=>$USER->firstname,'lastname'=>$USER->lastname,'do'=>'learner');
$intelliboard = json_decode($c->post('https://intelliboard.net/dashboard/api', $params));
if (isset($intelliboard->content)) {
    $factorInfo = json_decode($intelliboard->content);
} else {
	// TODO: Further define case when no content loaded.
	$factorInfo = '';
}

$action = optional_param('action', '', PARAM_RAW);
$search = optional_param('search', '', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$url = optional_param('url', '', PARAM_RAW);
$time = optional_param('time', 0, PARAM_INT);

$activity_setting = optional_param('activity_setting', 0, PARAM_INT);
$activity_courses = optional_param('activity_courses', 0, PARAM_INT);
$activity_time = optional_param('activity_time', 0, PARAM_INT);

if($activity_setting){
	$USER->activity_courses = $activity_courses;
	$USER->activity_time = $activity_time;
}else{
	$USER->activity_courses = (isset($USER->activity_courses))?$USER->activity_courses:0;
	$USER->activity_time = (isset($USER->activity_time))?$USER->activity_time:-1;
}

$PAGE->set_url(new moodle_url("/local/intelliboard/student/index.php", array("type"=>$type, "search"=>$search)));
$PAGE->set_pagetype('home');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.circlechart.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');

$t5 = get_config('local_intelliboard', 't5');
$t6 = get_config('local_intelliboard', 't6');
$t7 = get_config('local_intelliboard', 't7');
$t8 = get_config('local_intelliboard', 't8');
$t9 = get_config('local_intelliboard', 't9');
$t10 = get_config('local_intelliboard', 't10');
$t11 = get_config('local_intelliboard', 't11');
$t12 = get_config('local_intelliboard', 't12');
$t13 = get_config('local_intelliboard', 't13');
$t14 = get_config('local_intelliboard', 't14');
$t15 = get_config('local_intelliboard', 't15');
$t31 = get_config('local_intelliboard', 't31');
$t32 = get_config('local_intelliboard', 't32');
$t33 = get_config('local_intelliboard', 't33');
$t34 = get_config('local_intelliboard', 't34');
$t35 = get_config('local_intelliboard', 't35');
$t36 = get_config('local_intelliboard', 't36');
$t37 = get_config('local_intelliboard', 't37');
$t38 = get_config('local_intelliboard', 't38');

$courses = intelliboard_learner_courses($USER->id);
$totals = intelliboard_learner_totals($USER->id);

if($t12 or $t13)
	$modules_progress = intelliboard_learner_modules($USER->id);
if($t9)
	$assignments = intelliboard_data('assignment', $USER->id);
if($t10)
	$quizes = intelliboard_data('quiz', $USER->id);
if($t11)
	$courses_report = intelliboard_data('course', $USER->id);

if($t5){
	$progress = intelliboard_learner_progress($time, $USER->id);
	$json_data = array();
	foreach($progress[0] as $item){
		$l = 0;
		if(isset($progress[1][$item->timepoint])){
			$d = $progress[1][$item->timepoint];
			$l = round($d->grade,2);
		}
		$item->grade = round($item->grade,2);
		$tooltip = "<div class=\"chart-tooltip\">";
		$tooltip .= "<div class=\"chart-tooltip-header\">".date('D, M d Y', $item->timepoint)."</div>";
		$tooltip .= "<div class=\"chart-tooltip-body clearfix\">";
		$tooltip .= "<div class=\"chart-tooltip-left\"><span>". round($item->grade, 2)."%</span> current grade</div>";
		$tooltip .= "<div class=\"chart-tooltip-right\"><span>". round($l, 2)."%</span> average grade</div>";
		$tooltip .= "</div>";
		$tooltip .= "</div>";
		$item->timepoint = $item->timepoint*1000;
		$json_data[] = "[new Date($item->timepoint), $item->grade, '$tooltip', $l, '$tooltip']";
	}
}
$json_data2 = array();
foreach($courses as $item){
	$l = intval($item->duration_calc);
	$d = seconds_to_time(intval($item->duration));

	$tooltip = "<div class=\"chart-tooltip\">";
	$tooltip .= "<div class=\"chart-tooltip-header\">". $item->fullname ."</div>";
	$tooltip .= "<div class=\"chart-tooltip-body clearfix\">";
	$tooltip .= "<div class=\"chart-tooltip-left\">Grade: <span>". round($item->grade, 2)."</span></div>";
	$tooltip .= "<div class=\"chart-tooltip-right\">Time spent: <span>". $d."</span></div>";
	$tooltip .= "</div>";
	$tooltip .= "</div>";
	$json_data2[] = "[$l, $item->grade,'$tooltip']";
}

$menu = array("Last Week");
if(get_config('local_intelliboard', 't01'))
	array_push($menu, "Last Month");
if(get_config('local_intelliboard', 't02'))
	array_push($menu, "Last Quarter");
if(get_config('local_intelliboard', 't03'))
	array_push($menu, "Last Semester");

echo $OUTPUT->header();
?>
<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-student">
	<?php include("views/menu.php"); ?>
		<div class="intelliboard-box intelliboard-origin">
			<?php if($t5 or $t6): ?>
				<div class="intelliboard-origin-head clearfix">
					<?php if($t5): ?>
						<a>Activity progress</a>
					<?php endif; ?>
					<?php if($t6): ?>
						<a class="nofilter">Course progress</a>
					<?php endif; ?>
					<div class="intelliboard-dropdown">
						<?php foreach($menu as $key=>$value): ?>
							<?php if($key == $time): ?>
								<button><span value="<?php echo $key; ?>"><?php echo $value; ?></span> <i class="ion-android-arrow-dropdown"></i></button>
							<?php endif; ?>
						<?php endforeach; ?>
						<ul>
							<?php foreach($menu as $key=>$value): ?>
								<?php if($key != $time): ?>
									<li value="<?php echo $key; ?>"><?php echo $value; ?></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
				<?php if($t5): ?>
					<div id="intelliboard-chart" class="intelliboard-chart-dash"></div>
				<?php endif; ?>

				<?php if($t6): ?>
					<div id="intelliboard-chart-combo" class="intelliboard-chart-dash"></div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if($t7 or $t8): ?>
				<div class="avg <?php echo (!$t7 or !$t8)?'full':''; ?>">
					<?php if($t7): ?>
						<p class="user"><?php echo round($totals->grade, 2); ?>% <span>my course average (all courses)</span></p>
					<?php endif; ?>
					<?php if($t8): ?>
						<p class="site"><?php echo round($totals->average, 2); ?>% <span>overall course average (all learners; all courses)</span></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if($t9 or $t10 or $t11): ?>
		<div class="intelliboard-box">
			<?php if($t9 or $t10): ?>
			<div class="<?php echo (!$t11)?'box100':'box45'; ?> pull-left">
				<ul class="nav nav-tabs">
					<?php if($t9): ?>
						<li role="presentation" class="active"><a href="assignment">Assignments</a></li>
					<?php endif; ?>
					<?php if($t10): ?>
						<li role="presentation" class="<?php echo (!$t9)?'active':''; ?>"><a href="quiz">Quizzes</a></li>
					<?php endif; ?>
					<span>
						<form action="<?php echo $PAGE->url; ?>" method="GET" class="clearfix">
							<input class="intype" name="type" type="hidden" value="assignment" />
							<input class="intsearch" name="search" placeholder="Search" type="text" value="<?php echo ($type == 'assignment' or $type == 'quiz')?$search:''; ?>" />
							<button type="submit"><i class="ion-ios-search-strong"></i></button>
							<a href="" class="searchviewclose"><i class="ion-android-close"></i></a>
						</form>
						<a href="" class="searchview actbtn"><i class="ion-ios-search-strong"></i></a>
						<a href="" class="intsettings actbtn"><i class="ion-ios-settings-strong"></i></a>
					</span>
				</ul>
				<div>
					<?php if($t9): ?>
					<table class="intelliboard-data-table table tab active">
						<thead>
							<th colspan="2">Assignment name</th>
							<?php if($t31): ?><th class="align-center">Grade</th><?php endif; ?>
							<?php if($t32): ?><th class="align-center">Due Date</th><?php endif; ?>
						</thead>
						<tbody>
							<?php if(!count($assignments['data'])): ?>
								<tr colspan="3">
									<td>No data</td>
								</tr>
							<?php endif; ?>

							<?php foreach($assignments['data'] as $item): ?>
							<?php
								$d = $item->duedate - time();
								if($item->completionstate){
									$class = "f4 ion-android-done";
								}elseif($d <= 0){
									$class = "f5 ion-clipboard";
								}elseif($d <= 3600){
									$class = "f3 ion-clipboard";
								}elseif($d <= 86400){
									$class = "f6 ion-clipboard";
								}elseif($d <= 604800){
									$class = "f6 ion-clipboard";
								}else{
									$class = "f6 ion-clipboard";
								}
							?>
							<tr>
								<td width="1%"><i class="intelliboard-icon <?php echo $class; ?>"></i></td>
								<td>
									<a href="<?php echo $CFG->wwwroot; ?>/mod/assign/view.php?id=<?php echo $item->cmid; ?>"><?php echo $item->name; ?></a>
									<p class="intelliboard-fade60"><?php echo $item->fullname; ?></p>
								</td>
								<?php if($t31): ?>
								<td class="align-center">
									<div class="circle-progress"  data-percent="<?php echo (int)$item->grade; ?>"></div>
								</td>
								<?php endif; ?>
								<?php if($t32): ?>
								<td class="align-center"><?php echo ($item->duedate)?date("m/d/Y", $item->duedate):'-'; ?></span></td>
								<?php endif; ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<td align="right" colspan="4"><?php echo $assignments['pagination']; ?></td>
							</tr>
						</tfoot>
					</table>
					<?php endif; ?>

					<?php if($t10): ?>
					<table class="intelliboard-data-table table tab <?php echo (!$t9)?'active':''; ?>">
						<thead>
							<th colspan="2">Quiz name</th>
							<?php if($t33): ?><th class="align-center">Grade</th><?php endif; ?>
							<?php if($t34): ?><th class="align-center">Due Date</th><?php endif; ?>
						</thead>
						<tbody>
							<?php if(!count($quizes['data'])): ?>
								<tr colspan="3">
									<td>No data</td>
								</tr>
							<?php endif; ?>

							<?php foreach($quizes['data'] as $item): ?>
							<?php
								$d = $item->timeclose - time();
								if($item->completionstate){
									$class = "f4 ion-ios-list-outline";
								}elseif($d <= 0){
									$class = "f5 ion-ios-list-outline";
								}elseif($d <= 3600){
									$class = "f3 ion-ios-list-outline";
								}elseif($d <= 86400){
									$class = "f6 ion-ios-list-outline";
								}elseif($d <= 604800){
									$class = "f6 ion-ios-list-outline";
								}else{
									$class = "f6 ion-ios-list-outline";
								}
							?>
							<tr class="">
								<td width="1%"><i class="intelliboard-icon <?php echo $class; ?>"></i></td>
								<td>
									<a href="<?php echo $CFG->wwwroot; ?>/mod/quiz/view.php?id=<?php echo $item->cmid; ?>"><?php echo $item->name; ?></a>
									<p class="intelliboard-fade60"><?php echo $item->fullname; ?></p>
								</td>
								<?php if($t33): ?>
								<td class="align-center">
									<div class="circle-progress"  data-percent="<?php echo (int)$item->grade; ?>"></div>
								</td>
								<?php endif; ?>
								<?php if($t34): ?>
								<td class="align-center"><?php echo ($item->timeclose)?date("m/d/Y", $item->timeclose):'-'; ?></td>
								<?php endif; ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<td align="right" colspan="4"><?php echo $quizes['pagination']; ?></td>
							</tr>
						</tfoot>
					</table>
					<?php endif; ?>

					<div class="tab intsettings-box settings-tab">
						<form action="<?php echo $PAGE->url; ?>" method="GET" class="clearfix">
							<input name="activity_setting" type="hidden" value="1" />

							<div class="form-group">
							<label for="">Courses:</label>
							<select name="activity_courses" class="form-control">
								<option>All courses</option>
							  	<?php foreach($courses as $row):  ?>
									<option <?php echo ($USER->activity_courses == $row->id)?'selected="selected"':''; ?> value="<?php echo $row->id; ?>"><?php echo $row->fullname; ?></option>
								<?php endforeach; ?>
							</select>
							</div>

							<div class="form-group">
							<label for="">Time period (Due Date):</label>
							<select id="activity_time" name="activity_time" class="form-control">
								<option value="-1">All data</option>
								<?php foreach($menu as $key=>$value): ?>
									<option <?php echo ($USER->activity_time == $key)?'selected="selected"':''; ?> value="<?php echo $key; ?>"><?php echo $value; ?></option>
								<?php endforeach; ?>
							</select>
							</div>
							<button type="submit" class="btn btn-primary">Save</button>
							<button type="button" class="closesettings btn">Cancel</button>
						</form>
					</div>
				</div>
			</div>
			<?php endif; ?>


			<?php if($t11): ?>
			<div class="<?php echo (!$t9 and !$t10)?'box100':'box50'; ?>  pull-right">
				<ul class="nav nav-tabs clearfix">
				  <li role="presentation" class="active"><a href="#">Course Progress</a></li>

					<span>
						<form action="<?php echo $PAGE->url; ?>" method="GET" class="clearfix">
							<input name="type" type="hidden" value="course" />
							<input class="intsearch" name="search" placeholder="Search" type="text" value="<?php echo ($type == 'course')?$search:''; ?>" />
							<button type="submit"><i class="ion-ios-search-strong"></i></button>
							<a href="" class="searchviewclose"><i class="ion-android-close"></i></a>
						</form>
						<a href="" class="searchview actbtn"><i class="ion-ios-search-strong"></i></a>
						<a class="active actbtn cview" href="tm"><i class="ion-android-apps"></i></a>
						<a class="cview actbtn" href="list"><i class="ion-android-menu"></i></a>
					</span>
				</ul>

				<table class="intelliboard-data-table cview-table table tab active">
					<thead >
						<th colspan="2">Course</th>
						<?php if($t35): ?><th>Progress</th><?php endif; ?>
						<?php if($t36): ?><th class="align-center">Grade</th><?php endif; ?>
						<?php if($t37): ?><th>Enrolled</th><?php endif; ?>
						<?php if($t38): ?><th>Completed</th><?php endif; ?>

					</thead>
					<tbody>
						<?php if(!count($courses_report['data'])): ?>
							<tr colspan="6">
								<td>No data</td>
							</tr>
						<?php endif; ?>
						<?php foreach($courses_report['data'] as $item): ?>
						<tr class="">
							<td width="1%"><i class="intelliboard-icon <?php echo (!$item->timecompleted)?'f6 ion-social-buffer':'f4 ion-android-done'; ?>"></i></td>
							<td>
								<a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $item->id; ?>"><?php echo $item->fullname; ?></a>
							</td>
							<?php if($t35): ?>
							<td width="100">
								<div class="intelliboard-progress g1 xl intelliboard-tooltip"  title="<?php echo "Activities: $item->modules, Completed: $item->completedmodules"; ?>"><span style="width:<?php echo ($item->completedmodules) ? (($item->completedmodules / $item->modules) * 100) : 0; ?>%"></span></div>
							</td>
							<?php endif; ?>

							<?php if($t36): ?>
							<td class="align-center">
									<div class="circle-progress"  data-percent="<?php echo (int)$item->grade; ?>"></div>
								</td>
							<?php endif; ?>

							<?php if($t37): ?>
							<td align="right">
								<?php echo date("m/d/Y", $item->timemodified); ?>
							</td>
							<?php endif; ?>

							<?php if($t38): ?>
							<td align="right">
								<?php echo ($item->timecompleted) ? date("m/d/Y", $item->timecompleted):'-'; ?>
							</td>
							<?php endif; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<td align="right" colspan="6"><?php echo $courses_report['pagination']; ?></td>
						</tr>
					</tfoot>
				</table>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if($t12 or $t13 or $t14 or $t15): ?>
		<div class="intelliboard-box">
			<?php if($t12 or $t13): ?>
			<div class="<?php echo (!$t14 and !$t15)?'box100':'box40'; ?> pull-left h410">
				<ul class="nav nav-tabs chart-tabs">
					<?php if($t12): ?>
					  <li role="presentation" class="active"><a href="#">Activity participation</a></li>
					<?php endif; ?>

					<?php if($t13): ?>
					  <li role="presentation" class="<?php echo (!$t12)?'active':''; ?>"><a href="#">Learning</a></li>
					<?php endif; ?>
				</ul>
				<?php if($t12): ?>
				<div id="chart1" class="chart-tab active"></div>
				<?php endif; ?>

				<?php if($t13): ?>
				<div id="chart2" class="chart-tab <?php echo (!$t12)?'active':''; ?>"></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if($t14 or $t15): ?>
			<div class="<?php echo (!$t12 and !$t13)?'box100':'box50'; ?> pull-right h410">
				<ul class="nav nav-tabs chart-tabs">
					<?php if($t14): ?>
					  <li role="presentation" class="active"><a href="#">Course success</a></li>
					<?php endif; ?>

					<?php if($t15): ?>
					  <li role="presentation" class="<?php echo (!$t14)?'active':''; ?>"><a href="#">Correlations</a></li>
					<?php endif; ?>
				</ul>

				<?php if($t14): ?>
				<div id="chart3" class="chart-tab active"></div>
				<?php endif; ?>

				<?php if($t15): ?>
				<div id="chart4" class="chart-tab <?php echo (!$t14)?'active':''; ?>"></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript"
          src="https://www.google.com/jsapi?autoload={
            'modules':[{
              'name':'visualization',
              'version':'1',
              'packages':['corechart']
            }]
          }"></script>
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('.circle-progress').percentcircle(<?php echo $factorInfo->GradesCalculation; ?>);
	jQuery('.intelliboard-dropdown ul li').click(function(e){
		var stext = jQuery(this).parent().parent().find('span').text();
		var svalue = jQuery(this).parent().parent().find('span').attr('value');
		var ctext = jQuery(this).text();
		var cvalue = jQuery(this).attr('value');

		jQuery(this).text(stext);
		jQuery(this).attr('value', svalue);
		jQuery(this).parent().parent().find('span').text(ctext);
		jQuery(this).parent().parent().find('span').attr('value', cvalue);
		jQuery(this).parent().hide();
		location = "<?php echo $CFG->wwwroot; ?>/local/intelliboard/student/index.php?userid=<?php echo $USER->id; ?>&time="+cvalue;
	});

	jQuery('.intelliboard-dropdown button').click(function(e){
		if(jQuery(this).parent().hasClass('disabled')){
			return false;
		}
		jQuery(this).parent().find('ul').toggle();
	});

	jQuery('.closesettings').click(function(e){
		e.preventDefault();
		jQuery(this).parent().parent().parent().parent().find('.nav-tabs li:first a').trigger("click");
	});

	jQuery('.intsettings').click(function(e){
		e.preventDefault();
		jQuery(this).parent().parent().find('li').removeClass("active");
		jQuery(this).parent().parent().parent().find('.tab').removeClass("active");
		jQuery('.intsettings-box').addClass("active");
	});
	jQuery('.searchviewclose').click(function(e){
		e.preventDefault();
		jQuery(this).parent().parent().removeClass("active");
	});
	jQuery('.searchview').click(function(e){
		e.preventDefault();
		jQuery(this).parent().addClass("active");
	});

	jQuery('.cview').click(function(e){
		e.preventDefault();

		jQuery(this).parent().find('a').removeClass("active");
		jQuery(this).addClass("active");
		var m = jQuery(this).attr('href');
		jQuery('.cview-table').removeClass("list");
		jQuery('.cview-table').addClass(m);
	});

	jQuery('.nav-tabs li a').click(function(e){
		e.preventDefault();
		jQuery(this).parent().parent().find('li').removeClass("active");
		jQuery(this).parent().parent().find('.intype').val(jQuery(this).attr('href'));
		jQuery(this).parent().addClass("active");
		jQuery(this).parent().parent().parent().find('.tab').removeClass("active").eq(jQuery(this).parent().index()).addClass("active");
	});

	jQuery('.chart-tabs a').click(function(e){
		e.preventDefault();
		jQuery(this).parent().parent().parent().find('.chart-tab').hide().eq(jQuery(this).parent().index()).show();
	});

	jQuery('.intelliboard-origin-head a').click(function(e){
		e.preventDefault();

		jQuery(this).parent().find('a').removeClass("active");
		jQuery(this).addClass("active");
		jQuery(this).parent().parent().find('.intelliboard-chart-dash').hide().eq(jQuery(this).index()).show();
		if(jQuery(this).hasClass('nofilter')){
			jQuery('.intelliboard-dropdown').addClass('disabled');
		}else{
			jQuery('.intelliboard-dropdown').removeClass('disabled');
		}
	});

});
<?php if($t14): ?>
google.setOnLoadCallback(CourseSuccess);
      function CourseSuccess() {
        var data = google.visualization.arrayToDataTable([
          ['Status', 'Courses'],
          ['Completed',<?php echo (int)$totals->completed; ?>],
          ['In progress',<?php echo (int)$totals->inprogress; ?>],
          ['Not started',<?php echo intval($totals->enrolled)-(intval($totals->inprogress) + intval($totals->completed)); ?>],
        ]);
        var options = <?php echo $factorInfo->CourseSuccessCalculation; ?>;
        var chart = new google.visualization.PieChart(document.getElementById('chart3'));
        chart.draw(data, options);
}
<?php endif; ?>

<?php if($t15): ?>
google.setOnLoadCallback(Correlations);
      function Correlations() {
		var data = new google.visualization.DataTable();
		data.addColumn('number', 'Grade');
		data.addColumn('number', 'Time Spent (%)');
		data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
		data.addRows([<?php echo ($json_data2) ? implode(",", $json_data2):"";?>]);
        var options = <?php echo $factorInfo->CorrelationsCalculation; ?>;
        var chart = new google.visualization.ScatterChart(document.getElementById('chart4'));
        chart.draw(data, options);
}
<?php endif; ?>

<?php if($t12): ?>
	google.setOnLoadCallback(ActivityParticipation);
	      function ActivityParticipation() {
	        var data = google.visualization.arrayToDataTable([
	          ['Module name', 'Total', 'Viewed', 'Completed'],
	          <?php foreach($modules_progress as $row):  ?>
		          ['<?php echo ucfirst($row->name); ?>', <?php echo (int)$row->modules; ?>, <?php echo (int)$row->start_modules; ?>, <?php echo (int)$row->completed_modules; ?>],
		      <?php endforeach; ?>
	        ]);
			var options = <?php echo $factorInfo->ActivityParticipationCalculation; ?>;
	        var chart = new google.visualization.ColumnChart(document.getElementById('chart1'));
	        chart.draw(data, options);
	}
<?php endif; ?>

<?php if($t13): ?>
	google.setOnLoadCallback(LearningProgress);
	      function LearningProgress() {

	        var data = google.visualization.arrayToDataTable([
	          ['Module', 'Time spent'],
	          <?php foreach($modules_progress as $row):  ?>
		          ['<?php echo ucfirst($row->name); ?>', {v:<?php echo (int)$row->duration; ?>, f:'<?php echo seconds_to_time(intval($row->duration)); ?>'}],
		      <?php endforeach; ?>
	        ]);
	        var options = <?php echo $factorInfo->LearningProgressCalculation; ?>;
	        var chart = new google.visualization.PieChart(document.getElementById('chart2'));
	        chart.draw(data, options);
	}
<?php endif; ?>

<?php if($t6): ?>
	google.setOnLoadCallback(drawCourseProgress);
      function drawCourseProgress() {
        var data = google.visualization.arrayToDataTable([
         ['Course', 'Course Average', 'My Grade'],
         <?php foreach($courses as $row):  ?>
         ['<?php echo $row->fullname; ?>', <?php echo (int)$row->average; ?>, <?php echo (int)$row->grade; ?>],
         <?php endforeach; ?>
      ]);

    var options = <?php echo $factorInfo->CourseProgressCalculation; ?>;
    var chart = new google.visualization.ComboChart(document.getElementById('intelliboard-chart-combo'));
    chart.draw(data, options);
    jQuery('.intelliboard-origin-head a:first').trigger('click');
}
<?php endif; ?>

<?php if($t5): ?>
	google.setOnLoadCallback(drawActivityProgress);
	function drawActivityProgress() {
		var data = new google.visualization.DataTable();
		data.addColumn('date', 'Time');
		data.addColumn('number', 'My grade progress');
		data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
		data.addColumn('number', 'Average grade');
		data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
		data.addRows([<?php echo ($json_data) ? implode(",", $json_data):"";?>]);
		var options = <?php echo $factorInfo->ActivityProgressCalculation; ?>;
		var chart = new google.visualization.LineChart(document.getElementById('intelliboard-chart'));
		chart.draw(data, options);
		jQuery('.intelliboard-origin-head a:first').trigger('click');
	}
<?php endif; ?>
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
