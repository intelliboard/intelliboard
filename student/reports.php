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
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/externallib.php');
require_once($CFG->dirroot .'/local/intelliboard/student/lib.php');

$id = required_param('id', PARAM_INT);
$trigger = optional_param('trigger', 1, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$length = optional_param('length', 20, PARAM_INT);
$filter = optional_param('filter', '', PARAM_RAW);
$daterange = optional_param('daterange', 3, PARAM_INT);

$custom = optional_param('custom', 0, PARAM_INT);
$custom2 = optional_param('custom2', 0, PARAM_INT);
$custom3 = optional_param('custom3', 0, PARAM_INT);
$users = optional_param('users', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if(!get_config('local_intelliboard', 't1') or !get_config('local_intelliboard', 't48')){
	throw new moodle_exception('invalidaccess', 'error');
}
$email = get_config('local_intelliboard', 'te1');

$mode_filter = true;
if($trigger){
	$params = array(
		'id'=> $id,
		'url'=>$CFG->wwwroot,
        'email'=>s($email),
        'firstname'=>s($USER->firstname),
        'lastname'=>s($USER->lastname),
		'reports'=>get_config('local_intelliboard', 'reports'),
		'type'=>'reports',
		'do'=>'reportform',
		'mode'=> 1
	);
	$intelliboard = intelliboard($params);

	if(!empty($intelliboard->content)){
		$mode_filter = false;
	}
}

if($mode_filter){
	switch ($daterange) {
		case 1: $timestart = strtotime('today'); $timefinish = time(); break;
		case 2: $timestart = strtotime('yesterday'); $timefinish = strtotime('today'); break;
		case 3: $timestart = strtotime('-7 days'); $timefinish = time(); break;
		case 4: $timestart = strtotime('-30 days'); $timefinish = time(); break;
		case 5: $timestart = strtotime('-90 days'); $timefinish = time(); break;
		case 6: $timestart = 0; $timefinish = time(); break;
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
		'filter_profile'=>0,
		'sizemode'=> get_config('local_intelliboard', 'sizemode'),
		'users'=> $USER->id,
		'custom'=> $custom,
		'custom2'=> $custom2,
		'custom3'=> $custom3,
		'length'=>$length,
		'start'=>$page,
		'userid'=>$userid,
		'courseid'=>$courseid,
		'cohortid'=>$cohortid,
		'filter'=> s($filter),
		'timestart'=> $timestart,
		'timefinish'=>$timefinish
	);

	$function = "report$id";
	$plugin = new local_intelliboard_external();
	$data = json_encode($plugin->{$function}($params));

	$params = array(
		'url'=>$CFG->wwwroot,
        'email'=>s($email),
        'firstname'=>s($USER->firstname),
        'lastname'=>s($USER->lastname),
		'reports'=>get_config('local_intelliboard', 'reports'),
		'filter'=>s($filter),
		'daterange'=>$daterange,
		'data'=>$data,
		'users'=> $USER->id,
		'id'=> $id,
		'length'=>$length,
		'page'=>$page,
		'type'=>'reports',
		'do'=>'reports',
		'mode'=> 1
	);

	$intelliboard = intelliboard($params);
}else{
	$data = '';
}

$totals = intelliboard_learner_totals($USER->id);

$PAGE->set_url(new moodle_url("/local/intelliboard/student/reports.php"));
$PAGE->set_pagetype('reports');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.circlechart.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>

<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-student">
	<?php include("views/menu.php"); ?>
	<div class="intelliboard-content"><?php echo intelliboard_clean($intelliboard->content); ?></div>
	<?php include("../views/footer.php"); ?>
</div>
<?php endif; ?>
<?php
echo $OUTPUT->footer();
