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
admin_externalpage_setup('intelliboardreports');

$id = optional_param('id', 4, PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$length = optional_param('length', 20, PARAM_INT);
$filter = optional_param('filter', '', PARAM_RAW);
$daterange = optional_param('daterange', 3, PARAM_INT);

if($id){
	switch ($daterange) {
		case 1: $timestart = strtotime('today'); $timefinish = time(); break;
		case 2: $timestart = strtotime('yesterday'); $timefinish = strtotime('today'); break;
		case 3: $timestart = strtotime('-7 days'); $timefinish = time(); break;
		case 4: $timestart = strtotime('-30 days'); $timefinish = time(); break;
		case 5: $timestart = strtotime('-90 days'); $timefinish = time(); break;
		case 6: $timestart = 0; $timefinish = time(); break;
	}

	$params = (object) array(
		'length'=>$length, 
		'start'=>$page, 
		'userid'=>0, 
		'courseid'=>0, 
		'cohortid'=>0, 
		'filter'=>$filter, 
		'timestart'=> $timestart,
		'timefinish'=>$timefinish
	);

	$function = "report$id";
	$class = 'local_intelliboard_external';
	$plugin = new $class();
	$plugin->teacher_roles = '3,4';
	$plugin->learner_roles = '5'; 

	$data = json_encode($plugin->{$function}($params));
}else{
	$data = '';
}

$params = array(
	'url'=>$CFG->wwwroot,
	'email'=>$USER->email,
	'firstname'=>$USER->firstname,
	'lastname'=>$USER->lastname,
	'reports'=>get_config('local_intelliboard', 'reports'),
	'filter'=>$filter, 
	'daterange'=>$daterange, 
	'data'=>$data,
	'id'=> $id,
	'length'=>$length, 
	'page'=>$page,
	'type'=>'reports',
	'do'=>'reports'
);
$c = new curl;
$intelliboard = json_decode($c->post('http://intelliboard.net/dashboard/api', $params));
$PAGE->set_url(new moodle_url("/local/intelliboard/reports.php", array('id'=>$id)));
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('reports');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
?>
<div class="intelliboard-page">
	<?php include("views/menu.php"); ?>
	<div class="intelliboard-content"><?php echo $intelliboard->content; ?></div>
	<?php include("views/footer.php"); ?>
</div>
<?php
echo $OUTPUT->footer();