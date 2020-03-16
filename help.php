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

require('../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/instructor/lib.php');

require_login();

require_capability('local/intelliboard:view', context_system::instance());

$teacher_roles = get_config('local_intelliboard', 'filter10');
$learner_roles = get_config('local_intelliboard', 'filter11');

list($sql1, $params1) = intelliboard_filter_in_sql($teacher_roles, "roleid");
list($sql2, $params2) = intelliboard_filter_in_sql($learner_roles, "roleid");

$courses = $DB->count_records("course", ["visible" => 1]);
$instructors = $DB->count_records_sql("SELECT COUNT(*) FROM {role_assignments} WHERE id > 0 $sql1", $params1);
$learners = $DB->count_records_sql("SELECT COUNT(*) FROM {role_assignments} WHERE id > 0 $sql2", $params2);

$intelliboard = intelliboard([
    'task'             =>'help',
    'learners'         => $learners,
    'instructors'      => $instructors,
    'courses'          => $courses,
    'admins'           => json_encode(intelli_lms_admins()),
    'lms_url'          => $CFG->wwwroot
], 'help');

$event = optional_param('event', '', PARAM_RAW);
$connectlink = new \moodle_url("/local/intelliboard/setup.php");
$intelliboard = intelliboard(['task'=>'dashboard']);

$PAGE->set_pagetype('help');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url("/local/intelliboard/help.php"));
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');
$PAGE->requires->jquery();
echo $OUTPUT->header();
?>

<div class="intelliboard-support">
    <div class="intelliboard-support-menu">
        <?php if (empty($intelliboard->token) && is_siteadmin()): ?>
            <a href="<?php echo $connectlink->out(); ?>">
                <?php echo get_string('support_trial', 'local_intelliboard'); ?>
            </a>
        <?php elseif (!empty($intelliboard->token) and false): ?>
            <a href="<?php echo $CFG->wwwroot ?>/local/intelliboard/<?php echo $event ?>/index.php?action=<?php echo ($event)?'':'dashboard' ?>">
                <?php echo get_string('dashboard', 'local_intelliboard'); ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="intelliboard-support-logo">
        <a target="_blank" href="https://intelliboard.net/">
            <img src="<?php echo $CFG->wwwroot ?>/local/intelliboard/assets/img/logo@3x.png">
        </a>
    </div>

    <div class="clear"></div>

    <?php if (empty($intelliboard->token)): ?>
        <div class="intelliboard-support-text">
            <p>
                <span><?php echo get_string('support_text3', 'local_intelliboard'); ?></span>
                <span><?php echo get_string('support_text4', 'local_intelliboard'); ?></span>
                <span><?php echo get_string('support_connect', 'local_intelliboard', ["connect_link" => $connectlink->out()]); ?></span>
            </p>
        </div>

        <div class="intelliboard-support-links actions">
            <a class="intelliboard-support-large-btn" href="<?php echo $CFG->wwwroot ?>/local/intelliboard/<?php echo $event ?>/index.php?action=<?php echo ($event)?'':'dashboard' ?>">
                <span><?php echo get_string('dashboard_link', 'local_intelliboard'); ?></span>
            </a>
          </div>


        <div class="intelliboard-support-links initial-reports">
            <?php foreach(intelli_initial_reports() as $report): ?>
                <a class="intelliboard-support-btn" href="<?php echo $report["url"]; ?>">
                    <?php echo $report["name"]; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="intelliboard-support-links-header">
          <?php echo get_string('support_text6', 'local_intelliboard'); ?>
        </div>


        <div class="intelliboard-support-links actions">
            <a target="_blank" class="intelliboard-support-large-btn" href="https://intelliboard.acuityscheduling.com/schedule.php">
                <span><?php echo get_string('support_demo', 'local_intelliboard'); ?></span>
            </a>
            <a class="intelliboard-support-large-btn" target="_blank"
               href="https://support.intelliboard.net/hc/en-us/categories/360000794431-IntelliBoard-for-Moodle-">
                <span><?php echo get_string('support_page', 'local_intelliboard'); ?></span>
            </a>
            <a target="_blank" class="intelliboard-support-large-btn" href="https://intelliboard.net/events">
                <span><?php echo get_string('join_a_webinar', 'local_intelliboard'); ?></span>
            </a>
        </div>
    <?php else: ?>
        <div class="intelliboard-support-text"><p>
          <span><?php echo get_string('support_text3', 'local_intelliboard'); ?></span>
          <span><?php echo get_string('support_text4', 'local_intelliboard'); ?></span>
        </p></div>

        <div class="intelliboard-support-links actions">
            <a class="intelliboard-support-large-btn" href="<?php echo $CFG->wwwroot ?>/local/intelliboard/<?php echo $event ?>/index.php?action=<?php echo ($event)?'':'dashboard' ?>">
                <span><?php echo get_string('dashboard_link', 'local_intelliboard'); ?></span>
            </a>
          </div>


        <div class="intelliboard-support-links initial-reports">
            <?php foreach(intelli_initial_reports() as $report): ?>
                <a class="intelliboard-support-btn" href="<?php echo $report["url"]; ?>">
                    <?php echo $report["name"]; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="intelliboard-support-links-header">
          <?php echo get_string('support_text6', 'local_intelliboard'); ?>
        </div>


        <div class="intelliboard-support-links actions">
            <a target="_blank" class="intelliboard-support-large-btn" href="https://intelliboard.acuityscheduling.com/schedule.php">
                <span><?php echo get_string('support_demo', 'local_intelliboard'); ?></span>
            </a>
            <a target="_blank" class="intelliboard-support-large-btn" href="https://intelliboard.net/events">
                <span><?php echo get_string('join_a_webinar', 'local_intelliboard'); ?></span>
            </a>
            <a target="_blank" class="intelliboard-support-large-btn" href="https://support.intelliboard.net/hc/en-us/categories/360000794431-IntelliBoard-for-Moodle- ">
                <span><?php echo get_string('review_support_doc', 'local_intelliboard'); ?></span>
            </a>
        </div>
    <?php endif; ?>

    <div class="intelliboard-support-play"></div>
    <div class="intelliboard-support-player">
        <div style="padding:56.25% 0 0 0;position:relative;">
            <iframe id="intelliboardvideo" src="https://player.vimeo.com/video/390827913?title=0&byline=0&portrait=0"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        </div>
        <script src="https://player.vimeo.com/api/player.js"></script>

        <button type="button" class="btn btn-default">
            <?php echo get_string('support_close', 'local_intelliboard'); ?>
        </button>
    </div>


    <div class="intelliboard-support-bg"></div>
</div>

<div class="intelliboard-support-contacts">
    <strong>For additional information, please visit us at <a href="https://intelliboard.net/">www.IntelliBoard.net</a></strong>
    <strong>For questions, please contact us at <a href="mailto:Info@IntelliBoard.net">Info@IntelliBoard.net</a></strong>
</div>
<div class="intelliboard-support-terms">
    © 2014 - <?php echo date("Y") ?> IntelliBoard, Inc.<br>
    <?php echo get_string('support_terms', 'local_intelliboard'); ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function(){
        var iframe = document.getElementById('intelliboardvideo');
    var player = new Vimeo.Player(iframe);

        jQuery('.intelliboard-support-player button').click(function(){
            jQuery('.intelliboard-support-player').hide();
            player.pause();
        });
        jQuery('.intelliboard-support-play').click(function(){
            jQuery('.intelliboard-support-player').show();
            player.play();
        });
    });
</script>

<?php echo $OUTPUT->footer();
