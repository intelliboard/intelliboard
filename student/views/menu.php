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

$id = optional_param('id', 0, PARAM_INT);
$alt_name = get_config('local_intelliboard', 'grades_alt_text');
$def_name = get_string('grades', 'local_intelliboard');
$grade_name = ($alt_name) ? $alt_name : $def_name;

?>

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
		<li><a href="grades.php" <?php echo ($PAGE->pagetype == 'grades')?'class="active"':''; ?>><?php echo $grade_name;?></a></li>
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
