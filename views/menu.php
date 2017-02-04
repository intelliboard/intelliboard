<?php
	$id = optional_param('id', 0, PARAM_INT);
	echo (!isset($USER->noalert) and $intelliboard->alert) ? $intelliboard->alert : '';
?>
<ul class="intelliboard-menu">
	<li><a href="index.php" <?php echo ($PAGE->pagetype == 'home')?'class="active"':''; ?>><i class="ion-ios-pulse"></i> <?php echo get_string('dashboard', 'local_intelliboard');?></a></li>
	<li><a href="learners.php" <?php echo ($PAGE->pagetype == 'learners')?'class="active"':''; ?>><?php echo get_string('learners', 'local_intelliboard');?></a></li>
	<li><a href="courses.php" <?php echo ($PAGE->pagetype == 'courses')?'class="active"':''; ?>><?php echo get_string('courses', 'local_intelliboard');?></a></li>
	<li><a href="load.php" <?php echo ($PAGE->pagetype == 'load')?'class="active"':''; ?>><?php echo get_string('load', 'local_intelliboard');?></a></li>
	<?php if(isset($intelliboard->reports) and !empty($intelliboard->reports)): ?>
	<li class="submenu"><a href="#" <?php echo ($PAGE->pagetype == 'reports')?'class="active"':''; ?>><?php echo get_string('reports', 'local_intelliboard');?> <i class="arr ion-arrow-down-b"></i></a>
		<ul>
			<?php foreach($intelliboard->reports as $key=>$val): ?>
				<li><a href="reports.php?id=<?php echo format_string($key); ?>" <?php echo ($id == $key)?'class="active"':''; ?>><?php echo format_string($val); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</li>
	<?php endif; ?>
	<li><a href="config.php" <?php echo ($PAGE->pagetype == 'settings')?'class="active"':''; ?>><?php echo get_string('settings', 'local_intelliboard');?></a></li>
	<li class="sso">
		<?php if($intelliboard->token): ?>
			<a target="_blank" href="http://intelliboard.net/dashboard/api?do=signin&view=<?php echo $PAGE->pagetype; ?>&param=<?php echo format_string($id); ?>&token=<?php echo format_string($intelliboard->token); ?>" class="ion-log-in"> <?php echo get_string('intelliboardnet', 'local_intelliboard');?></a>
		<?php endif; ?>
	</li>
</ul>
