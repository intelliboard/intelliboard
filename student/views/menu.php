<div class="sheader clearfix">
	<div class="avatar">
		<?php echo $OUTPUT->user_picture($USER, array('size'=>75)); ?>
	</div>
	<div class="info">
		<h2><?php echo fullname($USER); ?> <i class="ion-checkmark-circled"></i></h2>
		<p><?php echo $USER->email; ?></p>
	</div>
	<div class="stats">
		<ul>
			<?php if(get_config('local_intelliboard', 't04')): ?>
			<li><?php echo (int)$totals->completed; ?><span>Completed courses</span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't05')): ?>
			<li><?php echo (int)$totals->inprogress; ?><span>Courses in progress</span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't06')): ?>
			<li><?php echo (int)$totals->grade; ?><span>Courses avg. grade</span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't07')): ?>
			<li><a href="<?php echo $CFG->wwwroot; ?>/message/index.php?viewing=unread&id=<?php echo $USER->id; ?>">
				<?php echo (int)$totals->messages; ?></a>
			<span>Messages</span></li>
			<?php endif; ?>
		</ul>
	</div>
</div>
<ul class="intelliboard-menu">
	<?php if(get_config('local_intelliboard', 't2')): ?>
		<li><a href="index.php" <?php echo ($PAGE->pagetype == 'home')?'class="active"':''; ?>><i class="ion-ios-pulse"></i> Dashboard</a></li>
	<?php endif; ?>
	<?php if(get_config('local_intelliboard', 't3')): ?>
		<li><a href="courses.php" <?php echo ($PAGE->pagetype == 'courses')?'class="active"':''; ?>>Courses</a></li>
	<?php endif; ?>
	<?php if(get_config('local_intelliboard', 't4')): ?>
		<li><a href="grades.php" <?php echo ($PAGE->pagetype == 'grades')?'class="active"':''; ?>>Grades</a></li>
	<?php endif; ?>
</ul>
