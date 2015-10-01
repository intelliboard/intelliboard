<?php 
	$id = optional_param('id', 0, PARAM_INT);
	echo (!isset($USER->noalert) and $intelliboard->alert) ? $intelliboard->alert : '';
?>
<ul class="intelliboard-menu">
	<li><a href="index.php" <?php echo ($PAGE->pagetype == 'home')?'class="active"':''; ?>><i class="ion-ios-pulse"></i> Dashboard</a></li>
	<li><a href="learners.php" <?php echo ($PAGE->pagetype == 'learners')?'class="active"':''; ?>>Learners</a></li>
	<li><a href="courses.php" <?php echo ($PAGE->pagetype == 'courses')?'class="active"':''; ?>>Courses</a></li>
	<li><a href="load.php" <?php echo ($PAGE->pagetype == 'load')?'class="active"':''; ?>>Load</a></li>
	<li class="submenu"><a href="#" <?php echo ($PAGE->pagetype == 'reports')?'class="active"':''; ?>>Reports <i class="arr ion-arrow-down-b"></i></a>
		<ul>
			<?php if(isset($intelliboard->reports) and !empty($intelliboard->reports)): ?>
				<?php foreach($intelliboard->reports as $key=>$val): ?>
					<li><a href="reports.php?id=<?php echo $key; ?>" <?php echo ($id == $key)?'class="active"':''; ?>><?php echo $val; ?></a></li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</li>
	<li><a href="config.php" <?php echo ($PAGE->pagetype == 'settings')?'class="active"':''; ?>>Settings</a></li>
	<li class="sso">
		<?php if($intelliboard->token): ?>
			<a target="_blank" href="http://intelliboard.net/dashboard/api?do=signin&view=<?php echo $PAGE->pagetype; ?>&param=<?php echo $id; ?>&token=<?php echo $intelliboard->token; ?>" class="ion-log-in"> IntelliBoard.net</a>
		<?php endif; ?>
	</li>
</ul>