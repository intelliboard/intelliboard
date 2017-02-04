<?php $id = optional_param('id', 0, PARAM_INT); ?>
<div class="sheader clearfix">
	<div class="avatar">
		<?php echo $OUTPUT->user_picture($USER, array('size'=>75)); ?>
	</div>
	<div class="info">
		<h2><?php echo fullname($USER); ?> <i class="ion-checkmark-circled"></i></h2>
		<p><?php echo format_string($USER->email); ?></p>
	</div>
	<div class="stats">
		<ul>
			<?php if(get_config('local_intelliboard', 't04')): ?>
			<li><?php echo (int)$totals->completed; ?><span><?php echo get_string('completed_courses', 'local_intelliboard');?></span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't05')): ?>
			<li><?php echo (int)$totals->inprogress; ?><span><?php echo get_string('courses_in_progress', 'local_intelliboard');?></span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't06')): ?>
			<li><?php echo (int)$totals->grade; ?><span><?php echo get_string('courses_avg_grade', 'local_intelliboard');?></span></li>
			<?php endif; ?>

			<?php if(get_config('local_intelliboard', 't07')): ?>
			<li><a href="<?php echo $CFG->wwwroot; ?>/message/index.php?viewing=unread&id=<?php echo $USER->id; ?>">
				<?php echo (int)$totals->messages; ?></a>
			<span><?php echo get_string('messages', 'local_intelliboard');?></span></li>
			<?php endif; ?>
		</ul>
	</div>
</div>
<ul class="intelliboard-menu">
	<?php if(get_config('local_intelliboard', 't2')): ?>
		<li><a href="index.php" <?php echo ($PAGE->pagetype == 'home')?'class="active"':''; ?>><i class="ion-ios-pulse"></i> <?php echo get_string('dashboard', 'local_intelliboard');?></a></li>
	<?php endif; ?>
	<?php if(get_config('local_intelliboard', 't3')): ?>
		<li><a href="courses.php" <?php echo ($PAGE->pagetype == 'courses')?'class="active"':''; ?>><?php echo get_string('courses', 'local_intelliboard');?></a></li>
	<?php endif; ?>
	<?php if(get_config('local_intelliboard', 't4')): ?>
		<li><a href="grades.php" <?php echo ($PAGE->pagetype == 'grades')?'class="active"':''; ?>><?php echo get_string('grades', 'local_intelliboard');?></a></li>
	<?php endif; ?>

	<?php if(get_config('local_intelliboard', 't48') and isset($intelliboard->reports) and !empty($intelliboard->reports)): ?>
	<li class="submenu"><a href="#" <?php echo ($PAGE->pagetype == 'reports')?'class="active"':''; ?>><?php echo get_string('reports', 'local_intelliboard');?> <i class="arr ion-arrow-down-b"></i></a>
		<ul>
			<?php foreach($intelliboard->reports as $key=>$val): ?>
				<li><a href="reports.php?id=<?php echo $key; ?>" <?php echo ($id == $key)?'class="active"':''; ?>><?php echo format_string($val); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</li>
	<?php endif; ?>
</ul>
