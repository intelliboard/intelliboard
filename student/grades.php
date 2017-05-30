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
 * @website    http://intelliboard.net/
 */

require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/student/lib.php');
require_once($CFG->dirroot .'/local/intelliboard/student/tables.php');

$id = optional_param('id', 0, PARAM_INT);
$search = clean_raw(optional_param('search', '', PARAM_TEXT));

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if ($search) {
	require_sesskey();
}

if(!get_config('local_intelliboard', 't1') or !get_config('local_intelliboard', 't4')){
	throw new moodle_exception('invalidaccess', 'error');
}
$email = get_config('local_intelliboard', 'te1');
$params = array(
	'do'=>'learner',
	'mode'=> 1
);
$intelliboard = intelliboard($params);
if (isset($intelliboard->content)) {
    $factorInfo = json_decode($intelliboard->content);
} else {
	$factorInfo = '';
}

$PAGE->set_url(new moodle_url("/local/intelliboard/student/grades.php", array("search"=>s($search), "id"=>$id, "sesskey"=> sesskey())));
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
	$table = new intelliboard_activities_grades_table('table', $USER->id, $id, s($search));
	$course = intelliboard_learner_course($USER->id, $id);
}else{
	$table = new intelliboard_courses_grades_table('table', $USER->id, s($search));
}
$table->show_download_buttons_at(array());
$table->is_downloading('', '', '');

echo $OUTPUT->header();
?>
<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
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
						<h3><?php echo format_string($course->fullname); ?></h3>
						<p>
							<?php if($course->enablecompletion and get_config('local_intelliboard', 't41')): ?>
								<?php echo ($course->timecompleted) ? " <i class='green-color ion-android-done'></i> ". get_string('completed_on', 'local_intelliboard', date('m/d/Y', $course->timecompleted)): " <i class='orange-color ion-android-radio-button-on'></i> ".get_string('incomplete', 'local_intelliboard'); ?>
							<?php endif; ?>
							<?php if(get_config('local_intelliboard', 't42')): ?>
							&nbsp; &nbsp; &nbsp;

							<?php echo ($course->timeaccess) ? " <i class='ion-android-person'></i> ".get_string('last_access_on_course', 'local_intelliboard', date('F d, Y h:i', $course->timeaccess)) : "" ?>
							<?php endif; ?>
						</p>
					</div>
					<a href="<?php echo $CFG->wwwroot.'/local/intelliboard/student/grades.php'; ?>" class="btn">
					<i class="ion-android-arrow-back"></i> <?php echo get_string('return_to_grades', 'local_intelliboard');?></a>
				</div>
			<?php endif; ?>
			<div class="intelliboard-search clearfix">
				<form action="<?php echo $PAGE->url; ?>" method="GET">
					<input type="hidden" name="sesskey" value="<?php p(sesskey()); ?>" />
					<input name="id" type="hidden" value="<?php echo $id; ?>" />
					<span class="pull-left"><input class="form-control" name="search" type="text" value="<?php echo format_string($search); ?>" placeholder="<?php echo get_string('type_here', 'local_intelliboard');?>" /></span>
					<button class="btn btn-default"><?php echo get_string('search');?></button>
				</form>
			</div>

			<?php $table->out(10, true); ?>
		</div>
	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.circle-progress').percentcircle(<?php echo format_string($factorInfo->GradesXCalculation); ?>);
		jQuery('.circle-progress-course').percentcircle(<?php echo format_string($factorInfo->GradesZCalculation); ?>);
	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
