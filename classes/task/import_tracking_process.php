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
 * @package    local_intelliboard
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intelliboard\task;

use local_intelliboard\repositories\tracking_storage_repository;

/**
 * Task to sync data with attendance
 *
 * @copyright  2020 Intelliboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_tracking_process extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('importtrackingtask', 'local_intelliboard');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \Exception
     */
    public function execute() {
        global $DB;

        $compresstracking = get_config('local_intelliboard', 'compresstracking');
        if (!$compresstracking){
            mtrace("IntelliBoard Compress Tracking disabled.");
            return true;
        }

        mtrace("IntelliBoard Tracking Files Export CRON started!");
        $trackingstorage = new tracking_storage_repository();
        $files = $trackingstorage->get_files();

        foreach ($files as $filename) {
            list($userid, $extension) = explode('.', $filename);

            if (!is_numeric($userid) || $extension != $trackingstorage::STORAGE_FILE_TYPE) {
                mtrace("Incorrect file " . $filename);
                $trackingstorage->delete_file($filename);
                continue; // something wrong
            }

            $tempfilepath = $trackingstorage->rename_file($filename);

            if (!$tempfilepath){
                mtrace("Error rename file " . $filename);
                continue; // something wrong
            }

            $data = [];
            $handle = @fopen($tempfilepath, "r");
            if ($handle) {
                while (($buffer = fgets($handle)) !== false) {
                    $record = json_decode($buffer);

                    if($record->table == 'tracking') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table] = (array)$record;
                        }
                    } else if($record->table == 'logs') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint] = (array)$record;
                        }
                    } else if($record->table == 'details') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint] = (array)$record;
                        }
                    }
                }
                if (!feof($handle)) {
                    mtrace("Error reading file " . $filename);
                }
                fclose($handle);
            }

            foreach ($data as $user) {
                foreach ($user as $page) {
                    foreach ($page as $param) {
                        $tr_record = (object)$param['tracking'];

                        if ($tracking = $DB->get_record('local_intelliboard_tracking', array('userid' => $tr_record->userid, 'page' => $tr_record->page, 'param' => $tr_record->param), 'id, visits, timespend, lastaccess')) {
                            if ($tracking->lastaccess < strtotime('today') || $tr_record->ajaxrequest == 0) {
                                $tracking->lastaccess = $tr_record->lastaccess;
                            }
                            if (isset($tr_record->visits)) {
                                $tracking->visits += $tr_record->visits;
                            }
                            $tracking->timespend += $tr_record->timespend;
                            $DB->update_record('local_intelliboard_tracking', $tracking);
                        } else {
                            $tracking = new \stdClass();
                            $tracking->id = $DB->insert_record('local_intelliboard_tracking', $tr_record, true);
                        }

                        $log_records = $param['logs'];
                        foreach ($log_records as $log_record) {
                            $log_record = (object)$log_record;
                            if ($log = $DB->get_record('local_intelliboard_logs', array('trackid' => $tracking->id, 'timepoint' => $log_record->timepoint))) {
                                if (isset($log_record->visits)) {
                                    $log->visits += $log_record->visits;
                                }
                                $log->timespend += $log_record->timespend;
                                $DB->update_record('local_intelliboard_logs', $log);
                            } else {
                                $log = new \stdClass();
                                $log->trackid = $tracking->id;
                                $log->visits = $log_record->visits;
                                $log->timespend = $log_record->timespend;
                                $log->timepoint = $log_record->timepoint;
                                $log->id = $DB->insert_record('local_intelliboard_logs', $log, true);
                            }

                            $detail_records = $param['details'][$log_record->timepoint];
                            foreach ($detail_records as $detail_record) {
                                $detail_record = (object)$detail_record;
                                if ($detail = $DB->get_record('local_intelliboard_details', array('logid' => $log->id, 'timepoint' => $detail_record->timepoint))) {
                                    if (isset($detail_record->visits)) {
                                        $detail->visits += $detail_record->visits;
                                    }
                                    $detail->timespend += $detail_record->timespend;
                                    $DB->update_record('local_intelliboard_details', $detail);
                                } else {
                                    $detail = new \stdClass();
                                    $detail->logid = $log->id;
                                    $detail->visits = $detail_record->visits;
                                    $detail->timespend = $detail_record->timespend;
                                    $detail->timepoint = $detail_record->timepoint;
                                    $detail->id = $DB->insert_record('local_intelliboard_details', $detail, true);
                                }
                            }
                        }
                    }
                }
            }

            $trackingstorage->delete_filepath($tempfilepath);
            mtrace("Successfull imported for user: " . $userid);
        }

        mtrace("IntelliBoard Tracking Files Export CRON completed!");

        return true;
    }

}