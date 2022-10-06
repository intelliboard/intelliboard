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
$meetinglink = "https://intelliboard.net/scheduledemo";
$joinwebinarlink = "https://intelliboard.net/events";
$connectivityissue = "https://support.intelliboard.net/hc/en-us/articles/360012709012-Connectivity-Issue-";
$supportemail = "HelpDesk@IntelliBoard.net";
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
    <div class="intelliboard-support-logo">
        <a target="_blank" href="https://intelliboard.net/">
            <img src="<?php echo $CFG->wwwroot ?>/local/intelliboard/assets/img/logo@3x.png">
        </a>
    </div>

    <div class="clear"></div>

    <div class="intelliboard-support-text">
        <span><?php echo get_string('support_text3', 'local_intelliboard'); ?></span>
        <span><?php echo get_string('support_text4', 'local_intelliboard'); ?></span>
    </div>
    <div class="intelliboard-support-text">
        <span><?php echo get_string('support_text7', 'local_intelliboard', ["meeting_link" => $meetinglink, "join_webinars_link" => $joinwebinarlink]); ?></span>
    </div>
    <div class="intelliboard-support-text">
        <span><?php echo get_string('support_text8', 'local_intelliboard', ["connectivity_issue" => $connectivityissue, "email" => $supportemail]); ?></span>
    </div>
    <div class="intelliboard-support-play"></div>
    <div class="intelliboard-support-player">
        <div style="padding:56.25% 0 0 0;position:relative;">
            <iframe id="intelliboardvideo" src="https://player.vimeo.com/video/725229030?title=0&byline=0&portrait=0"
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
