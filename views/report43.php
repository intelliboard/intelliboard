<table class="table">
	<thead>
		<tr>
			<th>Name</th>
			<th align="center">Progress</th>
			<th align="center">Score</th>
			<th align="center">Visits</th>
			<th align="center">Time Spent</th>
			<th align="center">Registered</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($report43['data'] as $row): ?>
		<tr>
			<td><a href="<?php echo $CFG->wwwroot; ?>/user/profile.php?id=<?php echo $row->id; ?>"><?php echo $row->user; ?></a></td>
			<td align="center" class="intelliboard-tooltip" title="<?php echo "Enrolled: ".intval($row->courses).", Completed: ".intval($row->completed_courses); ?>">
				<div class="intelliboard-progress xl"><span style="width:<?php echo ($row->completed_courses) ? (($row->completed_courses / $row->courses) * 100) : 0; ?>%"></span></div>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo "$row->user grade: ".intval($row->grade).", Average grade: ".intval($avg->grade_site);} ?>">
				<span class='<?php if($avg){echo ($avg->grade_site > $row->grade) ? "down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo (int)$row->grade; ?>
				</span>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo "$row->user visits: ".intval($row->visits).", Average visits: ".intval($avg->visits_site);} ?>">
				<span class='<?php if($avg){echo ($avg->visits_site > $row->visits)?"down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo ($report_time)?'Disabled':intval($row->visits); ?>
				</span>
			</td>
			<td align="center" class="intelliboard-tooltip" title="<?php if($avg){echo "$row->user time: ".seconds_to_time($row->timespend).", Average time: ".seconds_to_time($avg->timespend_site);} ?>">
				<span class='<?php if($avg){echo ($avg->timespend_site > $row->timespend)?"down ion-arrow-graph-down-left":"up ion-arrow-graph-up-left";} ?>'>
					 <?php echo ($report_time)?'Disabled':seconds_to_time($row->timespend); ?>
				</span>
			</td>
			<td><?php echo date("m/d/Y", $row->timecreated); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6">
				<a style="float:left" href="learners.php">More users</a>
				<span style="float:right;color:#ddd;">Showing 1 to 10</span>
			</td>
		</tr>
	</tfoot>
</table>
