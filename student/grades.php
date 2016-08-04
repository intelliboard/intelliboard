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
require_once('tables.php');

$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if(!get_config('local_intelliboard', 't1') or !get_config('local_intelliboard', 't4')){
	throw new moodle_exception('invalidaccess', 'error');
}

$c = new curl;
$email = get_config('local_intelliboard', 'te1');
$params = array('url'=>$CFG->wwwroot,'email'=>$email,'firstname'=>$USER->firstname,'lastname'=>$USER->lastname,'do'=>'learner');
$intelliboard = json_decode($c->post('https://intelliboard.net/dashboard/api', $params));
$factorInfo = json_decode($intelliboard->content);

$PAGE->set_url(new moodle_url("/local/intelliboard/student/grades.php", array("search"=>$search, "id"=>$id)));
$PAGE->set_pagetype('grades');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.circlechart.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');


$totals = intelliboard_learner_totals($USER->id);
if($id){
	$table = new intelliboard_activities_grades_table('table', $USER->id, $id, $search);
	$course = intelliboard_learner_course($USER->id, $id);
}else{
	$table = new intelliboard_courses_grades_table('table', $USER->id, $search);
}
$table->show_download_buttons_at(array());
$table->is_downloading('', '', '');

echo $OUTPUT->header();
?>
<?php if(!$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-student">
	<?php include("views/menu.php"); ?>
		<div class="intelliboard-overflow grades-table">
			<?php if(isset($course)): ?>
				<div class="intelliboard-course-header clearfix">
					<div class="grade">
						<div class="circle-progress-course"  data-percent="<?php echo (int)$course->grade; ?>"></div>
					</div>
					<div class="details">
						<h3><?php echo $course->fullname ?></h3>
						<p>
							<?php if($course->enablecompletion and get_config('local_intelliboard', 't41')): ?>
								<?php echo ($course->timecompleted) ? " <i class='green-color ion-android-done'></i> Completed on ".date('m/d/Y', $course->timecompleted) : " <i class='orange-color ion-android-radio-button-on'></i> Incomplete" ?>
							<?php endif; ?>
							<?php if(get_config('local_intelliboard', 't42')): ?>
							&nbsp; &nbsp; &nbsp;

							<?php echo ($course->timeaccess) ? " <i class='ion-android-person'></i> Last access on course: ".date('F d, Y h:i', $course->timeaccess) : "" ?>
							<?php endif; ?>
						</p>
					</div>
					<a href="<?php echo $CFG->wwwroot.'/local/intelliboard/student/grades.php'; ?>" class="btn">
					<i class="ion-android-arrow-back"></i> Return to Grades</a>
				</div>
			<?php endif; ?>
			<div class="intelliboard-search clearfix">
				<form action="<?php echo $PAGE->url; ?>" method="GET">
					<input name="id" type="hidden" value="<?php echo $id; ?>" />
					<input name="search" type="text" value="<?php echo $search; ?>" placeholder="Type here..." />
					<button>Search</button>
				</form>
			</div>

			<?php $table->out(10, true); ?>
		</div>
	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.circle-progress').percentcircle(<?php echo $factorInfo->GradesXCalculation; ?>);
		jQuery('.circle-progress-course').percentcircle(<?php echo $factorInfo->GradesZCalculation; ?>);
	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
