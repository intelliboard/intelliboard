<ul class="intelliboard-list">
	<?php foreach($report44['data'] as $row):  ?>
	<li class="intelliboard-tooltip" title="<?php echo "Enrolled users: $row->users, Completed: $row->completed"; ?>">
		<?php echo $row->fullname; ?>
		<span class="pull-right"><?php echo (int) $row->completed; ?>/<?php echo (int) $row->users; ?></span>
		<div class="intelliboard-progress"><span style="width:<?php echo ($row->completed) ? (($row->completed / $row->users) * 100) : 0; ?>%"></span></div>
	</li>
	<?php endforeach; ?>
	<li class="clearfix"><a style="float:left" href="courses.php">More courses</a>
		<span style="float:right;color:#ddd;">Showing 1 to 10</span>
	</li>
</ul>
