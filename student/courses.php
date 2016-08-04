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
require_once('lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$search = optional_param('search', '', PARAM_TEXT);

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if(!get_config('local_intelliboard', 't1') or !get_config('local_intelliboard', 't3')){
	throw new moodle_exception('invalidaccess', 'error');
}

$c = new curl;
$email = get_config('local_intelliboard', 'te1');
$params = array('url'=>$CFG->wwwroot,'email'=>$email,'firstname'=>$USER->firstname,'lastname'=>$USER->lastname,'do'=>'learner');
$intelliboard = json_decode($c->post('https://intelliboard.net/dashboard/api', $params));
$factorInfo = json_decode($intelliboard->content);

if($courseid and $action == 'details'){
	$progress = intelliboard_learner_course_progress($courseid, $USER->id);
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
		$json_data[] = array($item->timepoint, $item->grade, $tooltip, $l, $tooltip);
	}
	echo json_encode($json_data);
	exit;
}

$PAGE->set_url(new moodle_url("/local/intelliboard/student/courses.php", array("search"=>$search)));
$PAGE->set_pagetype('courses');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.circlechart.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');

$courses = intelliboard_data('courses', $USER->id);
$totals = intelliboard_learner_totals($USER->id);

$t16 = get_config('local_intelliboard', 't16');
$t17 = get_config('local_intelliboard', 't17');
$t18 = get_config('local_intelliboard', 't18');
$t19 = get_config('local_intelliboard', 't19');
$t20 = get_config('local_intelliboard', 't20');
$t21 = get_config('local_intelliboard', 't21');
$t22 = get_config('local_intelliboard', 't22');
$t47 = get_config('local_intelliboard', 't47');

echo $OUTPUT->header();
?>
<?php if(!$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-student">
	<?php include("views/menu.php"); ?>

		<div class="intelliboard-search clearfix">
			<form action="<?php echo $PAGE->url; ?>" method="GET">
				<input name="search" type="text" value="<?php echo $search; ?>" placeholder="Type here..." />
				<button>Search</button>
				<span>
					<a class="active" value="grid" href=""><i class="ion-android-apps"></i></a>
					<a href="" value="list"><i class="ion-android-menu"></i></a>
				</span>

			</form>
		</div>
		<div class="intelliboard-overflow">
			<ul class="intelliboard-courses-grid clearfix">
			<?php $i=0; foreach($courses['data'] as $item): $i++; ?>
				<li class="f<?php echo $t47+1; ?> course-item">
					<div class="course-info clearfix">
						<div class="icon">
							<i class="ion-social-buffer"></i>
							<?php if($t22): ?>
							<span title="Enrolled date"><?php echo date("d F", $item->timemodified); ?></span>
							<?php endif; ?>
						</div>
						<div class="title">
							<strong><?php echo $item->fullname; ?></strong>
							<?php if($t16): ?>
								<?php $teachers = get_users_by_capability(context_course::instance($item->id), 'moodle/course:update', '', '', '', '', '', null, false); ?>
								<?php foreach($teachers as $teacher): ?>
									<p title="Teacher"><?php echo $OUTPUT->user_picture($teacher, array('size'=>20)); ?> <?php echo fullname($teacher); ?></p>
								<?php break; endforeach; ?>
							<?php endif; ?>
							<?php if($t17): ?>
								<span title="Category"><i class="ion-ios-folder-outline"></i> <?php echo $item->category; ?></span>
							<?php endif; ?>
						</div>
						<?php if($t19): ?>
						<div class="grade" title="Current grade">
							<div class="circle-progress"  data-percent="<?php echo (int)$item->grade; ?>"></div>
						</div>
						<?php endif; ?>
					</div>
					<div class="course-stats clearfix">
						<?php if($t18): ?>
						<div>
							<span>Completion</span>
							<p><?php echo (int)$item->completedmodules; ?>/<?php echo (int)$item->modules; ?></p>
						</div>
						<?php endif; ?>

						<?php if($t20): ?>
						<div>
							<span>Class average</span>
							<p><?php echo (int)$item->average; ?>%</p>
						</div>
						<?php endif; ?>

						<?php if($t21): ?>
						<div>
							<span>Time Spent</span>
							<p><?php echo ($item->duration)?gmdate("H:i:s", intval($item->duration)):'-'; ?></p>
						</div>
						<?php endif; ?>
					</div>
					<div class="course-chart" id="course-chart<?php echo $item->id; ?>"></div>
					<div class="course-more clearfix">
						<span>
							<?php if($item->timecompleted): ?><a title="Completed on <?php echo date("m/d/Y", $item->timecompleted); ?>" href="#completed"><i class="ion-android-done-all"></i></a><?php endif; ?>
							<?php //<a href=""><i class="ion-alert-circled"></i></a> ?>
							<?php if($item->certificates): ?><a title="You have <?php echo $item->certificates; ?> certificates" href="#certificates"><i class="ion-ribbon-b"></i></a><?php endif; ?>
							<a class="course-details" href="" value="<?php echo $item->id; ?>"><i class="ion-podium"></i>
								<strong>Close</strong>
							</a>
						</span>
						<a class="more" href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $item->id; ?>">View course details</a>
					</div>
				</li>
			<?php endforeach; ?>
			</ul>
			<?php echo $courses['pagination']; ?>
		</div>
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
		jQuery('.course-details').click(function(e){
			e.preventDefault();
			var id = jQuery(this).attr('value');
			var icon = jQuery(this).find('i');

			if(jQuery(this).hasClass('active')){
				jQuery('.intelliboard-courses-grid').removeClass('list cview');
				jQuery('.course-item').removeClass('active');
				jQuery(this).removeClass('active');
			}else{
				jQuery('.intelliboard-courses-grid').addClass('list cview');
				jQuery('.course-item').removeClass('active');
				jQuery(this).addClass('active')
				jQuery(this).parents('.course-item').addClass('active');

				jQuery.ajax({
					url: '<?php echo $PAGE->url; ?>&action=details&courseid='+id,
					dataType: "json",
					beforeSend: function(){
						jQuery(icon).attr('class','ion-ios-loop-strong ion-spin-animation');
					}
				}).done(function( data ) {
					jQuery(icon).attr('class','ion-podium ion-spin-animation');

					var json_data = [];
					for(var i = 0; i < data.length; i++){
						var item = data[i];
						json_data.push([new Date(item[0]), item[1], item[2], item[3], item[4]]);
					}

					var data = new google.visualization.DataTable();
					data.addColumn('date', 'Time');
					data.addColumn('number', 'My grade progress');
					data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
					data.addColumn('number', 'Average grade');
					data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
					data.addRows(json_data);

					var options = <?php echo $factorInfo->CoursesCalculation; ?>;
					var chart = new google.visualization.LineChart(document.getElementById('course-chart'+id));
					chart.draw(data, options);
				});
			}
		});

		jQuery('.circle-progress').percentcircle(<?php echo $factorInfo->GradesFCalculation; ?>);
		jQuery('.intelliboard-search span a').click(function(e){
			e.preventDefault();
			jQuery(this).parent().find('a').removeClass("active");
			jQuery(this).addClass("active");
			jQuery('.intelliboard-courses-grid').removeClass('list');
			jQuery('.intelliboard-courses-grid').addClass(jQuery(this).attr('value'));
			jQuery('.intelliboard-courses-grid').removeClass('cview');
			jQuery('.course-item').removeClass('active');
		});
	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
