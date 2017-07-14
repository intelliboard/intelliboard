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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
require_capability('local/intelliboard:manage', context_system::instance());
admin_externalpage_setup('intelliboardlogs');

$action = optional_param('action', '', PARAM_RAW);
$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 50, PARAM_INT);


$PAGE->set_url(new moodle_url("/local/intelliboard/logs_legacy.php"));
$PAGE->set_pagetype('logs');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('intelliBoard_migration_tool', 'local_intelliboard'));


if($admins = get_admins()){
	$ids = array();
	foreach ($admins as $admin) {
		$ids[] = $admin->id;
	}
	$ids = implode(",", $ids);
}else{
	$ids = 2;
}
$html = '';


if($action == 'totals'){
	$html .= '<h2>'.get_string('importing_totals', 'local_intelliboard').'</h2>';
	$r = '';
	$query = "SELECT FROM_UNIXTIME(time, '%Y%m%d') as timeday, COUNT(userid) as visits, COUNT(DISTINCT (userid)) as sessions, COUNT(DISTINCT (course)) as courses
				FROM {log}
					WHERE userid NOT IN ($ids)
						GROUP BY timeday HAVING timeday NOT IN (SELECT FROM_UNIXTIME(timepoint, '%Y%m%d') as timepoint
							FROM {local_intelliboard_totals})";
	if($data = $DB->get_records_sql($query, array(), 0, $length)){
		$size = $DB->count_records_sql("SELECT COUNT(*) FROM ($query) AS x", array());
		foreach($data as $item){
			$data = new stdClass();
			$data->sessions = $item->sessions;
			$data->courses = $item->sessions;
			$data->visits = $item->visits;
			$data->timespend = $item->visits * 10;
			$data->timepoint = strtotime($item->timeday."000000");
			$DB->insert_record("local_intelliboard_totals", $data);
			$r .= "<p class='log'>".get_string('total_numbers', 'local_intelliboard',$data)."</p>";
		}
	}else{
		$size = 0;
	}

	$html .= '<strong>'.get_string('logs_to_process', 'local_intelliboard',$size).'</strong>';
	if($size > $length){
		$html .= '<p>'.get_string('please_wait_or_cancel', 'local_intelliboard',$PAGE->url).'</p>';
		$html .= '<meta http-equiv="refresh" content="1; url='.$PAGE->url.'?action=totals&length='.$length.'" />';
	}else{
		$html .= '<p>'.get_string('done', 'local_intelliboard').'</p>';
		$html .= '<p><a href="'.$PAGE->url.'">'.get_string('return_to_home', 'local_intelliboard').'</a></p>';
	}
	$html .= $r;
}elseif($action == 'logs'){
	$html .= '<h2>'.get_string('importing_logs', 'local_intelliboard').'</h2>';
	$r = '';
	$query = "SELECT
			id,
			module,
			cmid,
			userid,
			count(id) as visits,
			floor(time / 86400) * 86400 as timecreated,
			ip FROM {log}
		WHERE
			action = 'view' and userid NOT IN ($ids)
		GROUP BY course, userid, module, cmid, floor(time / 86400) * 86400";

	$modules = $DB->get_records_menu("modules");


	if($data = $DB->get_records_sql($query, array(), $start, $length)){

			$size = $DB->count_records_sql("SELECT COUNT(*) FROM ($query) AS x", array());

			$collector = array();
			foreach($data as $item){
				$item->timespend = 0;

				if($item->module == 'course'){
					$item->page = 'course';
				}elseif($item->module == 'user'){
					$item->page = 'user';
				}elseif(in_array($item->module, $modules)){
					$item->page = 'module';
				}else{
					$item->page = 'site';
				}
				$item->key = "$item->course-$item->cmid-$item->userid-$item->module";
				if(isset($collector[$item->key])){
					$obj = $collector[$item->key];
					$obj->logs[] = $item;
					$obj->visits = (isset($obj->visits))?($obj->visits + $item->visits):$item->visits;
					$obj->timespend = (isset($obj->timespend))?($obj->timespend + $item->timespend):$item->timespend;
					$obj->firstaccess = $item->timecreated;
					$obj->lastaccess = $item->timecreated;
				}else{
					$obj = new stdClass();
					$obj->logs[] = $item;
					$obj->visits = $item->visits;
					$obj->timespend = $item->timespend;
					$obj->firstaccess = $item->timecreated;
					$obj->lastaccess = $item->timecreated;
					$obj->userid = $item->userid;
					$obj->courseid = $item->course;
					$obj->param = $item->cmid;
					$obj->page = $item->page;
					$obj->ip = $item->ip;
					$collector[$item->key] = $obj;
				}
			}

			foreach($collector as $item){
				if($data = $DB->get_record('local_intelliboard_tracking', array('userid' => $item->userid,'courseid' => $item->courseid, 'page' => $item->page, 'param' => $item->param), '*')){
				}else{
					$data = new stdClass();
					$data->userid = $item->userid;
					$data->courseid = $item->courseid;
					$data->param = $item->param;
					$data->page = $item->page;
					$data->firstaccess = $item->firstaccess;
					$data->lastaccess = $item->lastaccess;
					$data->userip = $item->ip;
					$data->visits = $item->visits;
					$data->timespend = $item->timespend;

					$data->id =$DB->insert_record("local_intelliboard_tracking", $data, true);
					$r .= "<p class='log'>".get_string('total_numbers2', 'local_intelliboard', $data)."</p>";
				}
				if($data->id and !empty($item->logs)){
					foreach($item->logs as $row){
						if($log = $DB->get_record('local_intelliboard_logs', array('trackid' => $data->id, 'timepoint' => $row->timecreated))){
							continue; //stop
						}else{
							$log = new stdClass();
							$log->trackid = $data->id;
							$log->visits = $row->visits;
							$log->timespend = $row->timespend;
							$log->timepoint = $row->timecreated;
							$DB->insert_record('local_intelliboard_logs', $log);
						}

						$a = new stdClass();
						$a->timecreated = date('m/d/Y', $row->timecreated);
						$a->trackid = $log->trackid;
						$a->visits = $row->visits;
						$a->timespend = $row->timespend;
						$r .= "<p class='log'>".get_string('total_numbers3', 'local_intelliboard', $a)."</p>";
					}
				}
			}
	}else{
		$size = 0;
	}



	$html .= '<strong>'.get_string('logs_to_process', 'local_intelliboard', ((($size - $start)>0)?($size - $start):0)).'</strong>';
	if($size > $length){
		$html .= '<p>'.get_string('please_wait_or_cancel', 'local_intelliboard',$PAGE->url).'</p>';
		$html .= '<meta http-equiv="refresh" content="1; url='.$PAGE->url.'?action=logs&length='.$length.'&start='.($start + $length).'" />';
	}else{
        $html .= '<p>'.get_string('done', 'local_intelliboard').'</p>';
        $html .= '<p><a href="'.$PAGE->url.'">'.get_string('return_to_home', 'local_intelliboard').'</a></p>';
	}
	$html .= $r;
}
if(!$html){
	$result = $DB->get_record_sql("SELECT
		(SELECT count(*) FROM {log}  WHERE userid NOT IN ($ids)) as logs,
		(SELECT count(*) FROM {local_intelliboard_tracking}) as intelliboard_tracking,
		(SELECT count(*) FROM {local_intelliboard_totals}) as intelliboard_totals,
		(SELECT count(*) FROM {local_intelliboard_logs}) as intelliboard_logs,
		(SELECT min(firstaccess) FROM {local_intelliboard_tracking}) as startdate
		");
}
?>
<div class="intelliboard-page">
	<div class="intelliboard-content">
		<p><?php echo get_string('intelliBoard_migration_tool_info', 'local_intelliboard'); ?></p>
		<br>
		<br>
		<?php if(!$html): ?>
		<table class="table">
			<tr>
				<td><strong><?php echo get_string('moodle_logs', 'local_intelliboard'); ?></strong></td>
				<td><?php echo format_string($result->logs); ?></td>
			</tr>
			<tr>
				<td><strong><?php echo get_string('intelliboard_tracking', 'local_intelliboard'); ?></strong></td>
				<td><?php echo format_string($result->intelliboard_tracking); ?></td>
			</tr>
			<tr>
				<td><strong><?php echo get_string('intelliboard_logs', 'local_intelliboard'); ?></strong></td>
				<td><?php echo format_string($result->intelliboard_logs); ?></td>
			</tr>
			<tr>
				<td><strong><?php echo get_string('intelliboard_totals', 'local_intelliboard'); ?></strong></td>
				<td><?php echo format_string($result->intelliboard_totals); ?></td>
			</tr>
			<tr>
				<td><strong><?php echo get_string('intelliboard_start_tracking', 'local_intelliboard'); ?></strong></td>
				<td><?php echo ($result->startdate)?format_string(date("m/d/Y",$result->startdate)):''; ?></td>
			</tr>

		</table>
		<br>
		<br>
		<form action="<?php echo $PAGE->url; ?>">
			<fieldset>
				<legend><?php echo get_string('total_values_include', 'local_intelliboard'); ?></legend>
				<label><?php echo get_string('items_per_query', 'local_intelliboard'); ?></label><br>
				<input type="text" value="300" name="length">
				<input type="hidden" value="totals" name="action">
				<button><?php echo get_string('import', 'local_intelliboard'); ?></button>
			</fieldset>
		</form>
		<hr>
		<form action="<?php echo $PAGE->url; ?>">
			<fieldset>
				<legend><?php echo get_string('log_values_include', 'local_intelliboard'); ?></legend>
				<label><?php echo get_string('items_per_query', 'local_intelliboard'); ?></label><br>
				<input type="text" value="300" name="length">
				<input type="hidden" value="logs" name="action">
				<button><?php echo get_string('import', 'local_intelliboard'); ?></button>
			</fieldset>
		</form>
		<?php else: ?>
			<?php echo $html; ?>
		<?php endif; ?>
	</div>

	<?php include("views/footer.php"); ?>
</div>
<?php
echo $OUTPUT->footer();
