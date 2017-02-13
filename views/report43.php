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
?>
<table class="table">
	<thead>
		<tr>
			<th>Name</th>
			<th align="center"><?php echo get_string('progress', 'local_intelliboard');?></th>
			<th align="center"><?php echo get_string('score', 'local_intelliboard');?></th>
			<th align="center"><?php echo get_string('visits', 'local_intelliboard');?></th>
			<th align="center"><?php echo get_string('time_spent', 'local_intelliboard');?></th>
			<th align="center"><?php echo get_string('registered', 'local_intelliboard');?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($report43['data'] as $row): ?>
		<?php
			//clean variables
			$row->courses = intval($row->courses);
			$row->completed_courses = intval($row->completed_courses);
			$row->grade = intval($row->grade);
			$row->avg_grade_site = ($avg)?intval($avg->grade_site):0;
			$row->avg_visits_site = ($avg)?intval($avg->visits_site):0;
			$row->avg_timespend_site = ($avg)?seconds_to_time($avg->timespend_site):0;
			$row->visits = intval($row->visits);
			$row->timespend = seconds_to_time($row->timespend);
			$row->user = format_string($row->user);
            ?>
		<tr>
			<td><a href="<?php echo $CFG->wwwroot; ?>/user/profile.php?id=<?php echo $row->id; ?>"><?php echo $row->user; ?></a></td>
			<td align="center" class="intelliboard-tooltip" title="<?php echo get_string('enrolled_completed', 'local_intelliboard', $row); ?>">
				<div class="intelliboard-progress xl"><span style="width:<?php echo ($row->completed_courses) ? (($row->completed_courses / $row->courses) * 100) : 0; ?>%"></span></div>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo get_string('user_grade_avg', 'local_intelliboard', $row);} ?>">
				<span class='<?php if($avg){echo ($avg->grade_site > $row->grade) ? "down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo $row->grade; ?>
				</span>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo get_string('user_visit_avg', 'local_intelliboard', $row);} ?>">
				<span class='<?php if($avg){echo ($avg->visits_site > $row->visits)?"down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo ($report_time)?get_string('disabled', 'local_intelliboard'):$row->visits; ?>
				</span>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo get_string('user_time_avg', 'local_intelliboard', $row);} ?>">
				<span class='<?php if($avg){echo ($avg->timespend_site > $row->timespend)?"down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo ($report_time)?get_string('disabled', 'local_intelliboard'):$row->timespend; ?>
				</span>
			</td>
			<td><?php echo date("m/d/Y", $row->timecreated); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6">
				<a style="float:left" href="learners.php"><?php echo get_string('more_users', 'local_intelliboard'); ?></a>
				<span style="float:right;color:#ddd;"><?php echo get_string('showing_1_to_10', 'local_intelliboard'); ?></span>
			</td>
		</tr>
	</tfoot>
</table>
