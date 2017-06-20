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

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$view = optional_param('view', '', PARAM_ALPHANUMEXT);
$search = clean_raw(optional_param('search', '', PARAM_RAW));
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$time = optional_param('time', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$length = optional_param('length', 100, PARAM_INT);
$daterange = clean_raw(optional_param('daterange', '', PARAM_RAW));
$filter_courses = optional_param('filter_courses', '', PARAM_ALPHANUMEXT);

require_login();
intelliboard_instructor_access();

if(!$action){
	$params = array('do'=>'instructor','mode'=> 2);
	$intelliboard = intelliboard($params);
	if (isset($intelliboard->content)) {
	    $factorInfo = json_decode($intelliboard->content);
	} else {
		$factorInfo = '';
	}
}

if (!$daterange) {
    $timestart = strtotime('-7 days');
    $timefinish = time();

    $timestart_date = date("Y-m-d", $timestart);
    $timefinish_date = date("Y-m-d", $timefinish);

    $daterange = $timestart_date . ' to ' . $timefinish_date;
} else {
    $range = explode(" to ", $daterange);

    $timestart = ($range[0]) ? strtotime(trim($range[0])) : strtotime('-7 days');
    $timefinish = ($range[1]) ? strtotime(trim($range[1])) : time();

    $timestart_date = date("Y-m-d", $timestart);
    $timefinish_date = date("Y-m-d", $timefinish);
}

$PAGE->set_url(new moodle_url("/local/intelliboard/instructor/index.php", array("type"=>$type, "search"=>$search)));
$PAGE->set_pagetype('home');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/flatpickr.min.js');
$PAGE->requires->css('/local/intelliboard/assets/css/flatpickr.min.css');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');

if($action == 'modules'){
	$data = intelliboard_instructor_modules();
	die(json_encode($data));
}elseif ($action == 'correlations') {
	$data = intelliboard_instructor_correlations($page, $length);
	die(json_encode($data));
}

$mycourses = intelliboard_instructor_get_my_courses();
$list_of_my_courses = array();
foreach($mycourses as $item){
    $list_of_my_courses[$item->id] = $item->fullname;
}
if($course == 0){
    $course = key($list_of_my_courses);
}

$n1 = get_config('local_intelliboard', 'n1');
$n2 = get_config('local_intelliboard', 'n2');
$n3 = get_config('local_intelliboard', 'n3');
$n4 = get_config('local_intelliboard', 'n4');
$n5 = get_config('local_intelliboard', 'n5');
$n6 = get_config('local_intelliboard', 'n6');
$n7 = get_config('local_intelliboard', 'n7');
$n12 = get_config('local_intelliboard', 'n12');

$menu = array();
if($n1){
	$menu['progress'] = get_string('in11', 'local_intelliboard');
}
if($n2){
	$menu['grades'] = get_string('in12', 'local_intelliboard');
}
if($n3){
    $menu['activities'] = get_string('activity_progress', 'local_intelliboard');
}
if($n12){
    $menu['course_overview'] = get_string('course_overview', 'local_intelliboard');
}

if(empty($view)){
    $view = key($menu);
}

$summary_menu = array('curent_progress'=>get_string('in2', 'local_intelliboard'), 'total_student'=>get_string('in27', 'local_intelliboard'));


$stats = intelliboard_instructor_stats();
$courses = intelliboard_instructor_courses($view, $page, $length, $course);

echo $OUTPUT->header();
?>
<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-instructor">
	<?php include("views/menu.php"); ?>
	<?php if(isset($stats->courses) and isset($stats->enrolled) and $stats->courses > 0 and $stats->enrolled > 0): ?>
	<div class="intelli-instructor-header clearfix">
		<div class="instructor-head <?php echo($n5)?'':'full'; ?>">
			<?php if($n1 or $n2 or $n3 or $n12): ?>
				<h3><?php echo get_string('in1', 'local_intelliboard'); ?></h3>
	            <div class="intelliboard-dropdown">
	                <?php foreach($menu as $key=>$value): ?>
	                    <?php if($key == $view): ?>
	                        <button><span value="<?php echo $key; ?>"><?php echo format_string($value); ?></span> <i class="ion-android-arrow-dropdown"></i></button>
	                    <?php endif; ?>
	                <?php endforeach; ?>
	                <ul>
	                    <?php foreach($menu as $key=>$value): ?>
	                        <?php if($key != $view): ?>
	                            <li><a href="<?php echo $PAGE->url ?>&view=<?php echo $key; ?>"><?php echo format_string($value); ?></a></li>
	                        <?php endif; ?>
	                    <?php endforeach; ?>
	                </ul>
	            </div>
                <?php if($view == 'course_overview'):?>
                    <div class="intelliboard-additional-form">
                        <div class="intelliboard-dropdown">
                            <?php foreach($list_of_my_courses as $key=>$value): ?>
                                <?php if($key == $course): ?>
                                    <button><span value="<?php echo $key; ?>"><?php echo format_string($value); ?></span> <i class="ion-android-arrow-dropdown"></i></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <ul>
                                <?php foreach($list_of_my_courses as $key=>$value): ?>
                                    <?php if($key != $course): ?>
                                        <li><a href="<?php echo $PAGE->url ?>&view=<?php echo $view; ?>&course=<?php echo $key; ?>"><?php echo format_string($value); ?></a></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
	            <div class="clearfix"></div>
	            <div id="instructor-chart<?php echo ($view)?"-".$view:""; ?>" class="instructor-chart area"></div>
            <?php endif; ?>
            <?php if($n4): ?>
			<ul class="instructor-total">
				<li>
					<strong><?php echo (int)$stats->courses; ?></strong>
					<?php echo get_string('in3', 'local_intelliboard'); ?>
				</li>
				<li>
					<strong><?php echo (int)$stats->enrolled; ?></strong>
					<?php echo get_string('in4', 'local_intelliboard'); ?>
				</li>
				<li>
					<strong><?php echo (int)$stats->grades; ?></strong>
					<?php echo get_string('in5', 'local_intelliboard'); ?>
				</li>
			</ul>
			<?php endif; ?>
		</div>
		<?php if($n5): ?>
		<div class="summary clearfix">
            <div class="intelliboard-dropdown">
                <button><i class="ion-android-arrow-dropdown"></i></button>
                <ul>
                    <?php foreach($summary_menu as $key=>$value): ?>
                        <li><a href="#" data-tab="<?php echo $key;?>"><?php echo format_string($value); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="summary-item curent_progress">
                <h3><?php echo get_string('in2', 'local_intelliboard'); ?></h3>

                <div class="summary-chart-wrap">
                    <span class="summary-chart-label"><?php echo  intval(($stats->completed / $stats->enrolled) * 100); ?>%
                        <i><?php echo get_string('progress', 'local_intelliboard'); ?></i>
                    </span>
                    <div id="summary-chart" class="summary-chart" ></div>
                </div>
                <ul class="instructor-summary  clearfix">
                    <li>
                        <?php echo get_string('in6', 'local_intelliboard'); ?>
                        <strong><?php echo (int)$stats->completed; ?></strong>
                    </li>
                    <li>
                        <?php echo get_string('in7', 'local_intelliboard'); ?>
                        <strong><?php echo intval($stats->enrolled) - intval($stats->completed); ?></strong>
                    </li>
                    <li>
                        <?php echo get_string('in8', 'local_intelliboard'); ?>
                        <strong><?php echo (int)$stats->grade; ?></strong>
                    </li>
                </ul>
            </div>

            <div class="summary-item total_student" style="display: none;">
                <h3><?php echo get_string('in27', 'local_intelliboard'); ?></h3>

                <div class="filter-box">
                    <input type="text" id="summary-student-daterange" class="daterange flatpickr-input form-control" name="daterange" title="<?php echo get_string('filter_dates','local_intelliboard');?>" readonly="readonly" placeholder="<?php echo get_string('select_date','local_intelliboard');?>">
                </div>
                <div class="summary-chart-wrap">
                    <span class="summary-chart-label"><span id="summary-chart-label">0</span>
                        <i><?php echo get_string('in29', 'local_intelliboard'); ?></i>
                    </span>
                    <div id="summary-users-chart" class="summary-users-chart" ></div>
                </div>
                <ul class="instructor-summary  clearfix">
                    <li>
                        <?php echo get_string('learners_enrolled_period', 'local_intelliboard'); ?>:
                        <strong id="total-student-number">-</strong>
                    </li>
                    <li>
                        <?php echo get_string('in32', 'local_intelliboard'); ?>:
                        <strong id="avg-timespent-number">-</strong>
                    </li>
                </ul>
            </div>
		</div>
		<?php endif; ?>
	</div>

	<div class="intelliboard-box">
		<?php if($n6): ?>
		<div class="box<?php echo($n7)?'50':'100'; ?> pull-left h410">
			<ul class="nav nav-tabs clearfix">
	            <li role="presentation" class="nav-item active" data-tab="chart4"><a class="nav-link active" href="#"><?php echo get_string('in9', 'local_intelliboard'); ?></a></li>
	            <li role="presentation" class="nav-item" data-tab="chart5"><a class="nav-link" href="#"><?php echo get_string('in26', 'local_intelliboard'); ?></a></li>
	        </ul>
	        <div class="card-block">
	        	<div id="chart4" class="chart-tab active"><?php echo get_string('loading', 'local_intelliboard'); ?></div>
	        	<div id="chart5" class="chart-tab" style="display: none;">
                    <div class="filter-box clearfix">
                        <input type="text" class="form-control daterange flatpickr-input" name="daterange" title="<?php echo get_string('filter_dates','local_intelliboard');?>" readonly="readonly" placeholder="<?php echo get_string('select_date','local_intelliboard');?>">

                        <div class="intelliboard-dropdown">
                            <?php foreach($list_of_my_courses as $key=>$value): ?>
                                <?php if($key == $course): ?>
                                    <button value="<?php echo $key; ?>"><span><?php echo format_string($value); ?></span> <i class="ion-android-arrow-dropdown"></i></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <ul>
                                <?php foreach($list_of_my_courses as $key=>$value): ?>
                                        <li><a href="#" dava-value="<?php echo $key; ?>"><?php echo format_string($value); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div id="chart5_area" class="area"><?php echo get_string('loading', 'local_intelliboard'); ?></div>
                </div>
	        </div>
		</div>
		<?php endif; ?>
		<?php if($n7): ?>
		<div class="box<?php echo($n6)?'40':'100'; ?> pull-right h410">
			<ul class="nav nav-tabs clearfix">
	            <li role="presentation" class="nav-item active" data-tab="chart2"><a class="nav-link active" href="#"><?php echo get_string('in10', 'local_intelliboard'); ?></a></li>
	            <li role="presentation" class="nav-item" data-tab="chart3"><a class="nav-link" href="#"><?php echo get_string('in31', 'local_intelliboard'); ?></a></li>
	        </ul>
	        <div class="card-block">
	        	<div id="chart2" class="chart-tab active"><?php echo get_string('loading', 'local_intelliboard'); ?></div>
	        	<div id="chart3" class="chart-tab" style="display: none;">
                    <div class="filter-box clearfix">
                        <input type="text" class="daterange form-control flatpickr-input" name="daterange" title="<?php echo get_string('filter_dates','local_intelliboard');?>" readonly="readonly" placeholder="<?php echo get_string('select_date','local_intelliboard');?>">

                        <div class="intelliboard-dropdown">
                            <?php foreach($list_of_my_courses as $key=>$value): ?>
                                <?php if($key == $course): ?>
                                    <button value="<?php echo $key; ?>"><span><?php echo format_string($value); ?></span> <i class="ion-android-arrow-dropdown"></i></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <ul>
                                <?php foreach($list_of_my_courses as $key=>$value): ?>
                                    <li><a href="#" dava-value="<?php echo $key; ?>"><?php echo format_string($value); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div id="chart3_area" class="area"><?php echo get_string('loading', 'local_intelliboard'); ?></div>
                </div>
	        </div>
		</div>
		<?php endif; ?>
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
			jQuery('.intelliboard-dropdown button').click(function(e){
				if(jQuery(this).parent().hasClass('disabled')){
					return false;
				}
				jQuery(this).parent().find('ul').toggle();
			});

			jQuery('.nav-tabs .nav-item').click(function (e) {
			    e.preventDefault();
                var parent = jQuery(this).parent();
                var container = jQuery(parent).parent();
                var tab = jQuery(this).attr('data-tab');

                jQuery(parent).children('.nav-item').removeClass('active');
                jQuery(parent).children('.nav-item').children('.nav-link').removeClass('active');

                jQuery(this).addClass('active');
                jQuery(this).children('.nav-link').addClass('active');

                jQuery(container).children('.card-block').children('.chart-tab').hide();
                jQuery('#'+tab).show();
            });

            jQuery("#summary-student-daterange").flatpickr({
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: ["<?php echo $timestart_date; ?>", "<?php echo $timefinish_date; ?>"],
                onClose: function(selectedDates, dateStr, instance) {
                    summary_student_daterange_change();
                }
            });

            jQuery('.summary .intelliboard-dropdown ul li a').click(function () {
                var tab = jQuery(this).attr('data-tab');
                jQuery('.summary .summary-item').hide();
                jQuery('.summary .summary-item.'+tab).show();
                jQuery('.summary .intelliboard-dropdown ul').hide();

                summary_student_daterange_change();
            });

            jQuery("#chart5 .daterange").flatpickr({
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: ["<?php echo $timestart_date; ?>", "<?php echo $timefinish_date; ?>"],
                onClose: function(selectedDates, dateStr, instance) {
                    load_engagement_chart();
                }
            });
            jQuery('#chart5 .intelliboard-dropdown a').click(function (e) {
                e.preventDefault();
                jQuery('#chart5 .intelliboard-dropdown button span').html(jQuery(this).html());
                jQuery('#chart5 .intelliboard-dropdown button').val(jQuery(this).attr('dava-value'));
                jQuery('#chart5 .intelliboard-dropdown ul').hide();
                load_engagement_chart();
            });
            jQuery('.nav-item[data-tab="chart5"]').click(function (e) {
                load_engagement_chart();
            });

            jQuery("#chart3 .daterange").flatpickr({
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: ["<?php echo $timestart_date; ?>", "<?php echo $timefinish_date; ?>"],
                onClose: function(selectedDates, dateStr, instance) {
                    load_module_utilization_chart();
                }
            });
            jQuery('#chart3 .intelliboard-dropdown a').click(function (e) {
                e.preventDefault();
                jQuery('#chart3 .intelliboard-dropdown button span').html(jQuery(this).html());
                jQuery('#chart3 .intelliboard-dropdown button').val(jQuery(this).attr('dava-value'));
                jQuery('#chart3 .intelliboard-dropdown ul').hide();
                load_module_utilization_chart();
            });
            jQuery('.nav-item[data-tab="chart3"]').click(function (e) {
                load_module_utilization_chart();
            });

		});

		<?php if($n7): ?>
       	google.setOnLoadCallback(LearningProgress);
        function LearningProgress() {
        	jQuery.ajax({
				url: "<?php echo $PAGE->url; ?>&action=modules",
				dataType: "json"
			}).done(function( response ) {
				var data = google.visualization.arrayToDataTable(response);
	            var options = <?php echo format_string($factorInfo->LearningProgressCalculation); ?>;
	            var chart = new google.visualization.PieChart(document.getElementById('chart2'));
	            chart.draw(data, options);
			});
        }
        <?php endif; ?>

        <?php if($n6): ?>
        google.setOnLoadCallback(Correlations);
        function Correlations() {
        	jQuery.ajax({
				url: "<?php echo $PAGE->url; ?>&action=correlations",
				dataType: "json"
			}).done(function( response ) {
				//response = JSON.parse(response)
				console.log(response);
				var data = new google.visualization.DataTable();
	            data.addColumn('number', '<?php echo get_string('grade', 'local_intelliboard'); ?>');
	            data.addColumn('number', '<?php echo get_string('in13', 'local_intelliboard'); ?>');
	            data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
	            data.addRows(response);
	            var options = <?php echo format_string($factorInfo->CorrelationsCalculation); ?>;
	            var chart = new google.visualization.ScatterChart(document.getElementById('chart4'));
	            chart.draw(data, options);
			});
        }
        <?php endif; ?>

        <?php if($n5): ?>
		google.setOnLoadCallback(progressChart);
		function progressChart() {
			var data = google.visualization.arrayToDataTable([
				['<?php echo get_string('completed', 'local_intelliboard'); ?>', '<?php echo get_string('incomplete', 'local_intelliboard'); ?>'],
				['<?php echo get_string('completed', 'local_intelliboard'); ?>', <?php echo $stats->completed; ?>],
				['<?php echo get_string('incomplete', 'local_intelliboard'); ?>', <?php echo $stats->enrolled - $stats->completed; ?>]
			]);
			var options = {
			chartArea: {width: '100%',height: '90%',},
			  pieHole: 0.8,
			  pieSliceTextStyle: {
			    color: 'transparent',
			  },
			  colors:['#1db34f','#e74c3c'],
			  legend: 'none'
			};
			var chart = new google.visualization.PieChart(document.getElementById('summary-chart'));
			chart.draw(data, options);
		}
		<?php endif; ?>

		google.setOnLoadCallback(drawInsructorChart);
		function drawInsructorChart() {
			var options = {
				title:'',
				legend:{position: 'top', alignment: 'end'},
				vAxis: {title:'<?php echo get_string('learners', 'local_intelliboard'); ?>'},
				hAxis:{textPosition: 'none', title:'<?php echo get_string('courses'); ?>'},
				seriesType:'bars',
				series:{1:{type:'line'}},
				chartArea:{width:'90%',height: '76%',right:10 },
				colors:['#1d7fb3', '#1db34f'],
				backgroundColor:{fill:'transparent'}
			};
			<?php if($view == 'grades'): ?>
				options.vAxis.title = "<?php echo get_string('in19', 'local_intelliboard'); ?>";
				var data = google.visualization.arrayToDataTable([
				['Course', '<?php echo get_string('in19', 'local_intelliboard'); ?>', '<?php echo get_string('in25', 'local_intelliboard'); ?>'],
				<?php foreach($courses as $row):  ?>
				['<?php echo format_string($row->fullname); ?>', <?php echo (int)$row->data1; ?>, <?php echo (int)$row->data2; ?>],
				<?php endforeach; ?>
				]);
	        <?php elseif($view == 'activities'): ?>
	        	options.vAxis.title = "<?php echo get_string('in14', 'local_intelliboard'); ?>";
	        	options.vAxis.minValue = 0;
	        	options.vAxis.maxValue = 1;
	        	options.vAxis.format = 'percent';

	        	var data = google.visualization.arrayToDataTable([
	        	['<?php echo get_string('course'); ?>', '<?php echo get_string('in15', 'local_intelliboard'); ?>'],
	        	<?php foreach($courses as $row):  ?>
				['<?php echo format_string($row->fullname); ?>', {v: <?php echo $row->data1 / 100; ?>, f: '<?php echo (int)$row->data1; ?>%'} ],
				<?php endforeach; ?>
				]);
	        <?php elseif($view == 'course_overview'): ?>
	        	options.vAxis.title = "<?php echo get_string('s25', 'local_intelliboard'); ?>";
	        	options.hAxis.title = "<?php echo get_string('s45', 'local_intelliboard'); ?>";
	        	options.tooltip = {isHtml: true};
	        	options.chartArea.width = '96%';


                var data = [
                    ['<?php echo get_string('s45', 'local_intelliboard'); ?>', '<?php echo get_string('s25', 'local_intelliboard'); ?>', { type: 'string', role: 'tooltip', 'p': {'html': true}}],
                ];

                <?php foreach($courses as $row):  ?>
                    data.push(['<?php echo $row->activity; ?>', <?php echo $row->timespend; ?>, '<strong><?php echo $row->activity; ?></strong><br><?php echo get_string('s25', 'local_intelliboard'); ?> <strong><?php echo $row->timespend_str; ?></strong>']);
                <?php endforeach; ?>

                data = google.visualization.arrayToDataTable(data);
	        <?php else: ?>
	        	var data = google.visualization.arrayToDataTable([
	        	['<?php echo get_string('course'); ?>', '<?php echo get_string('enrolled', 'local_intelliboard'); ?>', '<?php echo get_string('completed', 'local_intelliboard'); ?>'],
	        	<?php foreach($courses as $row):  ?>
				['<?php echo format_string($row->fullname); ?>', <?php echo (int)$row->data1; ?>, <?php echo (int)$row->data2; ?>],
				<?php endforeach; ?>
				]);
	        <?php endif; ?>
			//var options = <?php echo $factorInfo->CourseProgressCalculation; ?>;
			var chart = new google.visualization.ComboChart(document.getElementById('instructor-chart<?php echo ($view)?"-".$view:""; ?>'));
			chart.draw(data, options);
			jQuery('.intelliboard-origin-head a:first').trigger('click');
		}

		function load_engagement_chart() {
            var daterange = jQuery('#chart5 .daterange').val();
            var course = jQuery('#chart5 .intelliboard-dropdown button').val();
            jQuery.ajax({
                url: "<?php echo $CFG->wwwroot; ?>/local/intelliboard/instructor/ajax.php?action=get_learner_engagement&daterange="+daterange+"&course="+course,
                dataType: "json"
            }).done(function( response ) {
                var data = google.visualization.arrayToDataTable(response);
                var options = <?php echo format_string($factorInfo->CorrelationsCalculation); ?>;
                options.vAxis.format = '#%';
                options.vAxis.title = '<?php echo get_string('s46', 'local_intelliboard');?>';
                var chart = new google.visualization.AreaChart(document.getElementById('chart5_area'));
                chart.draw(data, options);
            });
        }

		function load_module_utilization_chart() {
            var daterange = jQuery('#chart3 .daterange').val();
            var course = jQuery('#chart3 .intelliboard-dropdown button').val();
            jQuery.ajax({
                url: "<?php echo $CFG->wwwroot; ?>/local/intelliboard/instructor/ajax.php?action=get_module_utilization&daterange="+daterange+"&course="+course,
                dataType: "json"
            }).done(function( response ) {
                if(response.length > 0) {
                    var data = google.visualization.arrayToDataTable(response);
                    var options = <?php echo format_string($factorInfo->LearningProgressCalculation); ?>;
                    options.height = 300;
                    options.tooltip = {isHtml: true};
                    var chart = new google.visualization.PieChart(document.getElementById('chart3_area'));
                    chart.draw(data, options);
                }else{
                    jQuery('#chart3_area').html('<?php echo $OUTPUT->box(get_string('no_data', 'local_intelliboard'), 'generalbox alert');?>');
                }
            });
        }

        function summary_student_daterange_change() {
            var daterange = jQuery('#summary-student-daterange').val();
            jQuery.ajax({
                url: "<?php echo $CFG->wwwroot; ?>/local/intelliboard/instructor/ajax.php?action=get_total_students&daterange="+daterange,
                dataType: "json"
            }).done(function( response ) {
                jQuery('#avg-timespent-number').html(response.avg_timespend);
                jQuery('#total-student-number').html(response.enrolled_users);
                jQuery('#summary-chart-label').html(response.active_users);
                jQuery('#summary-users-chart').hide();


                if(response.total_users>0) {
                    jQuery('#summary-users-chart').show();
                    var data = [
                        ['<?php echo get_string('total', 'local_intelliboard'); ?>', '<?php echo get_string('in27', 'local_intelliboard'); ?>'],
                        ['<?php echo get_string('in29', 'local_intelliboard'); ?>', Number(response.active_users)],
                        ['<?php echo get_string('in30', 'local_intelliboard'); ?>', response.total_users - response.active_users],
                    ];
                    data = google.visualization.arrayToDataTable(data);
                    var options = {
                        chartArea: {width: '100%',height: '90%',},
                        pieHole: 0.8,
                        pieSliceTextStyle: {
                            color: 'transparent',
                        },
                        colors:['#1db34f','#e74c3c'],
                        legend: 'none'
                    };
                    var chart = new google.visualization.PieChart(document.getElementById('summary-users-chart'));
                    chart.draw(data, options);
                }
            });
        }
		</script>
		<?php else: ?>
			<br>
			<div class="alert alert-info alert-block fade in"><?php echo get_string('in23', 'local_intelliboard'); ?></div>
		<?php endif; ?>
	<?php include("../views/footer.php"); ?>
</div>

<?php endif; ?>
<?php echo $OUTPUT->footer();
