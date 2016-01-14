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
require_capability('local/intelliboard:manage', context_system::instance());
admin_externalpage_setup('intelliboardlogs');

$action = optional_param('action', '', PARAM_RAW);
$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 50, PARAM_INT);


$PAGE->set_url(new moodle_url("/local/intelliboard/logs.php"));
$PAGE->set_pagetype('logs');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
echo $OUTPUT->header();
echo $OUTPUT->heading("IntelliBoard Migration Tool");


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
	$html .= '<h2>Importing totals</h2>';
	$r = '';
	if($data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS FROM_UNIXTIME(timecreated, '%Y%m%d') as timeday, COUNT(userid) as visits, COUNT(DISTINCT (userid)) as sessions, COUNT(DISTINCT (courseid)) as courses
				FROM {logstore_standard_log}
					WHERE userid NOT IN ($ids)
						GROUP BY timeday HAVING timeday NOT IN (SELECT FROM_UNIXTIME(timepoint, '%Y%m%d') as timepoint FROM {local_intelliboard_totals}) LIMIT 0, $length")){
		$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
		$size = key($size);
		foreach($data as $item){
			$data = new stdClass();
			$data->sessions = $item->sessions;
			$data->courses = $item->sessions;
			$data->visits = $item->visits;
			$data->timespend = $item->visits * 10;
			$data->timepoint = strtotime($item->timeday."000000");
			$DB->insert_record("local_intelliboard_totals", $data);
			$r .= "<p class='log'>Date: $data->timepoint, Sessions: $data->sessions, Visits: $data->visits,  Time Spent: $data->timespend</p>";
		}
	}else{
		$size = 0;
	}

	$html .= '<strong>Logs to process '.$size.'</strong>';
	if($size > $length){
		$html .= '<p>Please wait to continue or <a href="'.$PAGE->url.'">Cancel</a></p>';
		$html .= '<meta http-equiv="refresh" content="1; url='.$PAGE->url.'?action=totals&length='.$length.'" />';
	}else{
		$html .= '<p>Done!</p>';
		$html .= '<p><a href="'.$PAGE->url.'">Return to home</a></p>';
	}
	$html .= $r;
}elseif($action == 'logs'){
	$html .= '<h2>Importing logs</h2>';
	$r = '';
	if($data = $DB->get_records_sql("SELECT SQL_CALC_FOUND_ROWS
			id,
			component,
			target,
			contextinstanceid as cid,
			userid,
			count(id) as visits,
			floor(timecreated / 86400) * 86400 as timecreated,
			ip FROM {logstore_standard_log}
		WHERE
			contextinstanceid > 0 and userid NOT IN ($ids)
		GROUP BY contextinstanceid, userid, component, target, floor(timecreated / 86400) * 86400
		LIMIT $start, $length")){
			$size = $DB->get_records_sql("SELECT FOUND_ROWS()");
			$size = key($size);

			$collector = array();
			foreach($data as $item){
				$item->timespend = 0;

				if(strpos($item->component, 'mod_') !== false){
					$item->page = 'module';
				}elseif($item->component == 'core' and $item->target == 'course'){
					$item->page = 'course';
				}elseif(strpos($item->component, 'user_profile') !== false){
					$item->page = 'user';
				}else{
					$item->page = 'site';
				}
				$item->key = "$item->cid-$item->userid-$item->component-$item->target";
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
					$obj->courseid = $item->cid;
					$obj->param = $item->cid;
					$obj->page = $item->page;
					$obj->ip = $item->ip;
					$collector[$item->key] = $obj;
				}
			}
			//print("<pre>"); print_r($size); exit;

			foreach($collector as $item){
				if($data = $DB->get_record('local_intelliboard_tracking', array('userid' => $item->userid, 'page' => $item->page, 'param' => $item->param), '*')){

					//$data->visits = $data->visits + $item->visits;
					//$data->timespend = $data->timespend + $item->timespend;
					//$DB->update_record('local_intelliboard_tracking', $data);
				}else{
					$data = new stdClass();
					$data->userid = $item->userid;
					$data->courseid = $item->courseid;
					$data->param = $item->courseid;
					$data->page = $item->page;
					$data->firstaccess = $item->firstaccess;
					$data->lastaccess = $item->lastaccess;
					$data->userip = $item->ip;
					$data->visits = $item->visits;
					$data->timespend = $item->timespend;

					$data->id =$DB->insert_record("local_intelliboard_tracking", $data);
					$r .= "<p class='log'>USER: $data->userid, Page: $data->page, Param:$data->param, Visits: $data->visits,  Time Spent: $data->timespend</p>";
				}
				if($data->id and !empty($item->logs)){
					foreach($item->logs as $row){
						if($log = $DB->get_record('local_intelliboard_logs', array('trackid' => $data->id, 'timepoint' => $row->timecreated))){
							continue; //stop

							//$log->visits = $log->visits + $row->visits;
							//$log->timespend = $log->timespend + $row->timespend;
							//$DB->update_record('local_intelliboard_logs', $log);
						}else{
							$log = new stdClass();
							$log->trackid = $data->id;
							$log->visits = $row->visits;
							$log->timespend = $row->timespend;
							$log->timepoint = $row->timecreated;
							$DB->insert_record('local_intelliboard_logs', $log);
						}
						$r .= "<p class='log'>----Date: ".date('m/d/Y', $row->timecreated).", Track ID: $log->trackid, Visits: $row->visits,  Time Spent: $row->timespend</p>";
					}
				}
			}
	}else{
		$size = 0;
	}



	$html .= '<strong>Logs to process '.((($size - $start)>0)?($size - $start):0).'</strong>';
	if($size > $length){
		$html .= '<p>Please wait to continue or <a href="'.$PAGE->url.'">Cancel</a></p>';
		$html .= '<meta http-equiv="refresh" content="1; url='.$PAGE->url.'?action=logs&length='.$length.'&start='.($start + $length).'" />';
	}else{
		$html .= '<p>Done!</p>';
		$html .= '<p><a href="'.$PAGE->url.'">Return to home</a></p>';
	}
	$html .= $r;
}
if(!$html){
	$result = $DB->get_record_sql("SELECT
		(SELECT count(*) FROM {logstore_standard_log}  WHERE userid NOT IN ($ids)) as logs,
		(SELECT count(*) FROM {local_intelliboard_tracking}) as intelliboard_tracking,
		(SELECT count(*) FROM {local_intelliboard_totals}) as intelliboard_totals,
		(SELECT count(*) FROM {local_intelliboard_logs}) as intelliboard_logs,
		(SELECT min(firstaccess) FROM {local_intelliboard_tracking}) as startdate
		");
}
?>
<div class="intelliboard-page">
	<div class="intelliboard-content">
		<p>IntelliBoard migration tool is used to migrate historical data from Moodle logs table into new format. Please note, Moodle logs storing procedure will not change. Once historical data migrated to new format, historical values like 'Time Spent' and 'Visits' will be available for preview at IntelliBoard.net.</p>
		<br>
		<br>
		<?php if(!$html): ?>
		<table class="table">
			<tr>
				<td><strong>Moodle logs</strong></td>
				<td><?php echo $result->logs; ?></td>
			</tr>
			<tr>
				<td><strong>IntelliBoard tracking</strong></td>
				<td><?php echo $result->intelliboard_tracking; ?></td>
			</tr>
			<tr>
				<td><strong>IntelliBoard logs</strong></td>
				<td><?php echo $result->intelliboard_logs; ?></td>
			</tr>
			<tr>
				<td><strong>IntelliBoard totals</strong></td>
				<td><?php echo $result->intelliboard_totals; ?></td>
			</tr>
			<tr>
				<td><strong>IntelliBoard start tracking</strong></td>
				<td><?php echo ($result->startdate)?date("m/d/Y",$result->startdate):''; ?></td>
			</tr>

		</table>
		<br>
		<br>
		<form action="<?php echo $PAGE->url; ?>">
			<fieldset>
				<legend>Total Values include unique sessions, courses, visits, time spent.</legend>
				<label>Items per-query</label><br>
				<input type="text" value="300" name="length">
				<input type="hidden" value="totals" name="action">
				<button>Import</button>
			</fieldset>
		</form>
		<hr>
		<form action="<?php echo $PAGE->url; ?>">
			<fieldset>
				<legend>Log values include logs for each user per day.</legend>
				<label>Items per-query</label><br>
				<input type="text" value="300" name="length">
				<input type="hidden" value="logs" name="action">
				<button>Import</button>
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
