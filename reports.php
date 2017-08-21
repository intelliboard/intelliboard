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


$id = optional_param('id', 1, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$length = optional_param('length', 20, PARAM_INT);
$filter = clean_raw(optional_param('filter', '', PARAM_RAW));
$daterange = clean_raw(optional_param('daterange', '', PARAM_RAW));
$download = optional_param('download', '', PARAM_ALPHA);
$format = optional_param('format', 'html', PARAM_ALPHA);
$custom = optional_param('custom', 0, PARAM_INT);
$custom2 = optional_param('custom2', 0, PARAM_INT);
$custom3 = optional_param('custom3', 0, PARAM_INT);
$users = optional_param('users', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

if ($download) {
	$length = 100000; //Max. records to export
} else {
	admin_externalpage_setup('intelliboardreports');
}

if (!$daterange) {
	$timestart = strtotime('-7 days');
	$timefinish = time();

	$timestart_date = date("Y-m-d", $timestart);
	$timefinish_date = date("Y-m-d", $timefinish);

	$daterange = $timestart_date . ' to ' . $timefinish_date;
} elseif($daterange == 'disabled') {
	$timestart = 0;
	$timefinish = time();

	$timestart_date = date("Y-m-d", strtotime('-7 days'));
	$timefinish_date = date("Y-m-d", $timefinish);
} else {
	$range = explode(" to ", $daterange);

	$timestart = ($range[0]) ? strtotime(trim($range[0])) : strtotime('-7 days');
	$timefinish = ($range[1]) ? strtotime(trim($range[1])) : time();

	$timestart_date = date("Y-m-d", $timestart);
	$timefinish_date = date("Y-m-d", $timefinish);
}

if($id){
	$page = ($page)?$page:1;
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
		'completion'=>get_config('local_intelliboard', 'completions'),
		'filter_profile'=>0,
		'sizemode'=>0,
		'custom'=> '',
		'custom2'=> '',
		'custom3'=> '',
		'length'=>$length,
		'start'=>(($page-1) * $length),
		'users'=>0,
		'userid'=>0,
		'courseid'=>0,
		'cohortid'=>0,
		'filter'=>s($filter),
		'timestart'=> $timestart,
		'timefinish'=>$timefinish
	);

	$function = "report$id";
	$plugin = new local_intelliboard_external();
	$data = json_encode($plugin->{$function}($params));
}else{
	$data = '';
}

$params = array(
	'download' => 1,
    'output' => ($download)?1:0,
	'reports'=>get_config('local_intelliboard', 'reports'),
	'filter'=>s($filter),
	'daterange'=>$daterange,
	'data'=>$data,
	'id'=> $id,
	'length'=>$length,
	'page'=>$page,
	'type'=>'reports',
	'do'=>'reports'
);
$intelliboard = intelliboard($params);
if($download and isset($intelliboard->json) and isset($intelliboard->itemname)){
	intelliboard_export_report($intelliboard->json, $intelliboard->itemname, $format);
}
$PAGE->set_url(new moodle_url("/local/intelliboard/reports.php", array('id'=>$id)));
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('reports');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/flatpickr.min.js');
$PAGE->requires->css('/local/intelliboard/assets/css/flatpickr.min.css');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>
<div class="intelliboard-page">
	<?php include("views/menu.php"); ?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			$("#daterange").wrap("<span class='daterange-wrap'></span>");
			$("#daterange").after('<i class="daterange-trigger ion-android-checkbox-outline"></i>');
			$(".daterange-trigger").click(function(){
				$(".hidden-daterange").remove();
				if ($(this).hasClass('ion-android-checkbox-outline')) {
					$(this).attr('class', 'daterange-trigger ion-android-checkbox-outline-blank');
					$("#daterange").addClass('disabled').prop( "disabled", true ).val('');
					$(".daterange-wrap").append('<input type="hidden" class="hidden-daterange" name="daterange" value="disabled"/>');
				} else {
					$(this).attr('class', 'daterange-trigger ion-android-checkbox-outline');
					$("#daterange").removeClass('disabled').prop( "disabled", false ).val('');
				}
			});
			$("#daterange").flatpickr({
			    mode: "range",
			    dateFormat: "Y-m-d",
			    defaultDate: ["<?php echo $timestart_date; ?>", "<?php echo $timefinish_date; ?>"],
			    onReady: function(selectedDates, dateStr, instance){
					jQuery('<div/>', {
					    class: 'flatpickr-calendar-title',
					    text: $("#daterange").attr('title')
					}).appendTo('.flatpickr-calendar');

    			}
			});
			<?php if ($daterange == 'disabled'): ?>
				$(".daterange-trigger").trigger('click');
			<?php endif; ?>
		});
	</script>
	<div class="intelliboard-content"><?php echo intelliboard_clean($intelliboard->content); ?></div>
	<?php include("views/footer.php"); ?>
</div>
<?php
echo $OUTPUT->footer();
