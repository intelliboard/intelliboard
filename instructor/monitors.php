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

require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/instructor/lib.php');
require_once('../externallib.php');


$id = optional_param('widget', 0, PARAM_INT);
$field = optional_param('field', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_RAW);
$courseid = optional_param('courseid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$custom = optional_param('custom', 0, PARAM_INT);
$custom2 = optional_param('custom2', 0, PARAM_INT);
$daterange = clean_raw(optional_param('daterange', '', PARAM_RAW));

require_login();
intelliboard_instructor_access();

if ($action == 'get_widget_data') {
	if ($daterange) {
		$date = explode(" to ", $daterange);
		$timestart = strtotime($date[0]);
		$timefinish = strtotime($date[1]);
	} else {
		$timestart = strtotime('-6 month');
		$timefinish = time();
	}

	$params = (object) array(
		'filter_user_deleted'=>get_config('local_intelliboard', 'filter1'),
		'filter_user_suspended'=>get_config('local_intelliboard', 'filter2'),
		'filter_user_guest'=>get_config('local_intelliboard', 'filter3'),
		'filter_course_visible'=>get_config('local_intelliboard', 'filter4'),
		'filter_enrolmethod_status'=>get_config('local_intelliboard', 'filter5'),
		'filter_enrol_status'=>get_config('local_intelliboard', 'filter6'),
		'filter_module_visible'=>get_config('local_intelliboard', 'filter7'),
		'filter_columns'=>get_config('local_intelliboard', 'filter9'),
		'teacher_roles'=>get_config('local_intelliboard', 'filter10'),
		'learner_roles'=>get_config('local_intelliboard', 'filter11'),
		'completion'=>get_config('local_intelliboard', 'completions'),
		'filter_profile'=>0,
		'sizemode'=>0,
		'users'=> 0,
		'custom'=> $custom,
		'custom2'=> $custom2,
		'custom3'=> 0,
		'length'=>0,
		'start'=>0,
		'userid'=>$USER->id,
		'courseid'=>0,
		'cohortid'=> $cohortid,
		'filter'=> '',
		'timestart'=> $timestart,
		'timefinish'=>$timefinish
	);

	$function = "widget$id";
	$plugin = new local_intelliboard_external();
	$data = $plugin->{$function}($params);

	$params = new stdClass();
	$params->timestart = $timestart;
	$params->timefinish = $timefinish;
	$params->cohortid = ($cohortid) ? implode(",", array_map('intval', $cohortid)) : $cohortid;
	$params->custom = ($cohortid) ? implode(",", array_map('intval', $custom)) : $custom;
	$params->custom2 = ($cohortid) ? implode(",", array_map('intval', $custom2)) : $custom2;

	$html = intelliboard_get_widget($id, $data, $params);
	die($html);
} elseif ($action == 'get_user_info_fields_data') {
	$html = '';
	if($field){
        $result = $DB->get_records_sql("
        	SELECT id, fieldid, data, count(id) as items
			FROM {user_info_data}
			WHERE fieldid = ?
			GROUP BY data
			ORDER BY data ASC", array($field));

		foreach($result as $item){
			if(!empty($item->data)){
				$html .= '<option value="'.$item->id.'">'. $item->data .'</option>';
			}
		}
	}
	die($html);
}

$params = array(
	'do'=>'instructor',
	'mode'=> 2
);
$intelliboard = intelliboard($params);
if (isset($intelliboard->content)) {
    $factorInfo = json_decode($intelliboard->content);
} else {
	$factorInfo = '';
}

$PAGE->set_url(new moodle_url("/local/intelliboard/instructor/monitors.php"));
$PAGE->set_pagetype('monitors');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.multiple.select.js');
$PAGE->requires->js('/local/intelliboard/assets/js/flatpickr.min.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
$PAGE->requires->css('/local/intelliboard/assets/css/multiple-select.css');
$PAGE->requires->css('/local/intelliboard/assets/css/flatpickr.min.css');


echo $OUTPUT->header();
?>
<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-instructor">
	<?php include("views/menu.php"); ?>
	<script type="text/javascript"
		src="https://www.google.com/jsapi?autoload={
			'modules':[{
				'name':'visualization',
				'version':'1',
				'packages':['corechart']
			}]
		}"></script>
	<ul class="monitors">
		<li class="widget-chart" id="widget_27"></li>
		<li class="widget-chart" id="widget_28"></li>
		<li class="widget-chart" id="widget_29"></li>
		<li class="widget-chart" id="widget_30"></li>
		<li class="widget-chart" id="widget_31"></li>
	</ul>

	<script type="text/javascript">
		loadwidget(27, {"widget":"27"});
		loadwidget(28, {"widget":"28"});
		loadwidget(29, {"widget":"29"});
		loadwidget(30, {"widget":"30"});
		loadwidget(31, {"widget":"31"});

		function info_fields(id){
		 	var field = jQuery( '#custom'+id ).val();
		 	if (field) {
		 		jQuery.ajax({
					url: '<?php echo new moodle_url('/local/intelliboard/instructor/monitors.php', array('action' => 'get_user_info_fields_data')); ?>',
					type: "POST",
					data: 'field='+field,
					dataType: "html",
					beforeSend: function(){
						$('#custom2'+id).html("...").multipleSelect("refresh");
					}
				}).done(function( data ) {
				jQuery('#custom2'+id).html(data).multipleSelect("refresh");
				jQuery(".custom2"+id+" .ms-drop").append('<div class="actions"><button type="button" class="custom2-close'+id+'">OK</button></div>');
				jQuery(".custom2-close"+id).click(function(){updatewidget(id); jQuery("#custom2"+id).multipleSelect("close"); });
				});
		 	}
		 }
		 function updatewidget(id){
		 	var params = jQuery( '#widgetform'+id ).serialize();
		 	loadwidget(id, params);
		 }
		 function loadwidget(id, params){
			jQuery.ajax({
				url: '<?php echo new moodle_url('/local/intelliboard/instructor/monitors.php', array('action' => 'get_widget_data')); ?>',
				type: "POST",
				data: params,
				dataType: "html",
				beforeSend: function(){
					if (jQuery('#widget_'+id).html() ){
						jQuery('#widget_'+id).addClass('loading');
					} else {
						jQuery('#widget_'+id).html('...');
					}
				}
			}).done(function( data ) {
				jQuery('#widget_'+id).removeClass('loading').html(data);
			});
		}
	</script>
	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){

	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
