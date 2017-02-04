<ul class="intelliboard-list">
	<?php foreach($report44['data'] as $row):  ?>
	<li class="intelliboard-tooltip" title="<?php echo get_string('enrolled_users_completed', 'local_intelliboard', $row); ?>">
		<?php echo format_string($row->fullname); ?>
		<span class="pull-right"><?php echo (int) $row->completed; ?>/<?php echo (int) $row->users; ?></span>
		<div class="intelliboard-progress"><span style="width:<?php echo ($row->completed) ? (($row->completed / $row->users) * 100) : 0; ?>%"></span></div>
	</li>
	<?php endforeach; ?>
	<li class="clearfix"><a style="float:left" href="courses.php"><?php echo get_string('more_courses', 'local_intelliboard'); ?></a>
		<span style="float:right;color:#ddd;"><?php echo get_string('showing_1_to_10', 'local_intelliboard'); ?></span>
	</li>
</ul>
