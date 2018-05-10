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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/local/intelliboard/locallib.php');

class local_intelliboard_observer
{

    protected $event_queue_table = 'logstore_standart_log';

    public static function role_assigned(core\event\role_assigned $event)
    {
        $data = $event->get_data();
        $relatedUser = $data['relateduserid'];

        $excluded = exclude_not_owners(array($relatedUser, $data['courseid'], $relatedUser));

        self::process_event(2, $event, array(), $excluded);
    }

    public static function role_unassigned(core\event\role_unassigned $event)
    {
        $data = $event->get_data();
        $relatedUser = $data['relateduserid'];

        $excluded = exclude_not_owners(array($relatedUser, $data['courseid'], $relatedUser));

        self::process_event(2, $event, array(), $excluded);
    }

    public static function post_created(mod_forum\event\post_created $event)
    {

        $eventData = $event->get_data();

        $excluded = exclude_not_owners(array($eventData['userid'], $eventData['courseid'], $eventData['userid']));

        $data = array(
            'forums' => $eventData['other']['forumid'],
            'course' => $eventData['courseid']
        );

        self::process_event(12, $event, $data, $excluded);
    }

    public static function user_graded(\core\event\user_graded $event)
    {
        global $DB;

        $allowedTypes = array('assign', 'quiz');
        $eventData = $event->get_data();

        $itemid = $eventData['other']['itemid'];
        $item = $DB->get_record('grade_items', array('id' => $itemid), "itemmodule");

        if (in_array($item->itemmodule, $allowedTypes)) {
            $excluded = exclude_not_owners(array($eventData['userid'], $eventData['courseid'], $eventData['userid']));
            $data = array(
                'course' => $eventData['courseid']
            );

            self::process_event(13, $event, $data, $excluded);
        }

    }

    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event)
    {
        global $DB;

        $eventData = $event->get_data();

        $isNeededGrading = $DB->get_record_sql("SELECT 
            COUNT(qas.id) AS checking 
            FROM {question_attempt_steps} qas
            INNER JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
            INNER JOIN {quiz_attempts} q ON q.uniqueid = qa.questionusageid
            WHERE q.id = ? AND qas.state = 'needsgrading'
        ", array($eventData['objectid']))->checking;
        
        if (!$isNeededGrading) {
            return;
        }

        $excluded = exclude_not_owners(array($eventData['userid'], $eventData['courseid'], $eventData['userid']));

        self::process_event(15, $event, array(), $excluded);
    }

    public static function assign_attempt_submitted(\mod_assign\event\assessable_submitted $event)
    {
        $eventData = $event->get_data();
        $excluded = exclude_not_owners(array($eventData['userid'], $eventData['courseid'], $eventData['userid']));

        self::process_event(15, $event, array(), $excluded);
    }

    protected static function process_event($type, $event, $filter = array(), $excluded = null)
    {
        $notification = new local_intelliboard_notification();

        $notifications = $notification->get_instant_notifications($type, $filter, $excluded);

        $notification->send_notifications($notifications, $event);
    }

}
