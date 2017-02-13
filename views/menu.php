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
 * @website    https://intelliboard.net/
 */
?>
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
			<a target="_blank" href="<?php echo intelliboard_url(); ?>/dashboard/api?do=signin&view=<?php echo $PAGE->pagetype; ?>&param=<?php echo format_string($id); ?>&token=<?php echo format_string($intelliboard->token); ?>" class="ion-log-in"> <?php echo get_string('intelliboardnet', 'local_intelliboard');?></a>
		<?php endif; ?>
	</li>
</ul>
