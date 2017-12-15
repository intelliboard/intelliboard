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

// In versions before Moodle 2.9, the supported callbacks have _extends_ (not imperative mood) in their names. This was a consistency bug fixed in MDL-49643.
function local_intelliboard_extends_navigation(global_navigation $nav){
	global $CFG, $USER;

	local_intelliboard_insert_tracking(false);
	$context = context_system::instance();
	if (isloggedin() and get_config('local_intelliboard', 't1') and has_capability('local/intelliboard:students', $context)) {
		$alt_name = get_config('local_intelliboard', 't0');
		$def_name = get_string('ts1', 'local_intelliboard');
		$name = ($alt_name) ? $alt_name : $def_name;
		$nav->add($name, new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php'));
	}

	if (isloggedin() and get_config('local_intelliboard', 'n10')){
	    //Check if user is enrolled to any courses with "instructor" role(s)
		$instructor_roles = get_config('local_intelliboard', 'filter10');
	    if (!empty($instructor_roles)) {
	    	$access = false;
		    $roles = explode(',', $instructor_roles);
		    if (!empty($roles)) {
			    foreach ($roles as $role) {
			    	if ($role and user_has_role_assignment($USER->id, $role)){
			    		$access = true;
			    		break;
			    	}
			    }
				if ($access) {
					$alt_name = get_config('local_intelliboard', 'n11');
					$def_name = get_string('n10', 'local_intelliboard');
					$name = ($alt_name) ? $alt_name : $def_name;
					$nav->add($name, new moodle_url($CFG->wwwroot.'/local/intelliboard/instructor/index.php'));
				}
			}
		}
	}
}
//call-back method to extend the navigation
function local_intelliboard_extend_navigation(global_navigation $nav){
	global $CFG, $DB, $USER, $PAGE;

	try {
		$mynode = $PAGE->navigation->find('myprofile', navigation_node::TYPE_ROOTNODE);
		$mynode->collapse = true;
		$mynode->make_inactive();

		local_intelliboard_insert_tracking(false);
		$context = context_system::instance();
		if (isloggedin() and get_config('local_intelliboard', 't1') and has_capability('local/intelliboard:students', $context)) {
			$alt_name = get_config('local_intelliboard', 't0');
			$def_name = get_string('ts1', 'local_intelliboard');
			$name = ($alt_name) ? $alt_name : $def_name;
			$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php');
			$nav->add($name, $url);
			$node = $mynode->add($name, $url, 0, null, 'intelliboard_student');
			$node->showinflatnavigation = true;
		}

		if (isloggedin() and get_config('local_intelliboard', 'n10')) {
		    //Check if user is enrolled to any courses with "instructor" role(s)
			$instructor_roles = get_config('local_intelliboard', 'filter10');
		    if (!empty($instructor_roles)) {
		    	$access = false;
			    $roles = explode(',', $instructor_roles);
			    if (!empty($roles)) {
				    foreach ($roles as $role) {
				    	if ($role and user_has_role_assignment($USER->id, $role)){
				    		$access = true;
				    		break;
				    	}
				    }
					if ($access) {
						$alt_name = get_config('local_intelliboard', 'n11');
						$def_name = get_string('n10', 'local_intelliboard');
						$name = ($alt_name) ? $alt_name : $def_name;
						$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/instructor/index.php');
						$nav->add($name, $url);

						$node = $mynode->add($name, $url, 0, null, 'intelliboard_instructor');
						$node->showinflatnavigation = true;
					}
				}
			}
		}
		if (isloggedin() and get_config('local_intelliboard', 'competency_dashboard') and has_capability('local/intelliboard:competency', $context)) {
			$alt_name = get_config('local_intelliboard', 'a11');
			$def_name = get_string('a0', 'local_intelliboard');
			$name = ($alt_name) ? $alt_name : $def_name;
			$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/competencies/index.php');
			$nav->add($name, $url);

			$node = $mynode->add($name, $url, 0, null, 'intelliboard_competency');
			$node->showinflatnavigation = true;
		}
	} catch (Exception $e) {}
}

function local_intelliboard_user_details()
{
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	} else {
		$user_agent = '';
	}

    $platform    =   "Unknown OS Platform";
	$os_array       =   array(
                            '/windows nt 10.0/i'    =>  'Windows 10',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win98/i'              =>  'Windows 98',
                            '/win95/i'              =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/linux/i'              =>  'Linux',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile'
                        );
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $platform    =   $value;
        }
    }
	$browser        =   "Unknown Browser";
    $browser_array  =   array(
                            '/msie|trident/i'   =>  'Internet Explorer',
                            '/firefox/i'        =>  'Firefox',
                            '/chrome/i'         =>  'Chrome',
                            '/safari/i'         =>  'Safari',
                            '/edge/i'           =>  'Microsoft Edge',
                            '/opera/i'          =>  'Opera',
                            '/opr/i'          =>  'Opera',
                            '/netscape/i'       =>  'Netscape',
                            '/maxthon/i'        =>  'Maxthon',
                            '/konqueror/i'      =>  'Konqueror',
                            '/mobile/i'         =>  'Mobile browser'
                        );
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
            break;
        }
    }

	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}elseif (isset($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}else{
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	$ip = ($ip) ? $ip : 0;
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$userlang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$userlang = 'Unknown';
	}

	return array(
        'useragent'  => $browser,
        'useros' => $platform,
        'userip' => $ip,
        'userlang'     => $userlang
    );
}
function local_intelliboard_insert_tracking($ajaxRequest = false){
    global $CFG, $PAGE, $SITE, $DB, $USER;

	$version = get_config('local_intelliboard', 'version');
	$enabled = get_config('local_intelliboard', 'enabled');
	$ajax = (int) get_config('local_intelliboard', 'ajax');
	$inactivity = (int) get_config('local_intelliboard', 'inactivity');
	$trackadmin = get_config('local_intelliboard', 'trackadmin');
	$trackcourses = get_config('local_intelliboard', 'trackcourses');
	$trackusers = get_config('local_intelliboard', 'trackusers');
	$trackpoint = get_config('local_intelliboard', 'trackpoint');
	$path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

	if(strpos($path,'cron.php') !== false){
		return false;
	}

	if ($enabled and isloggedin() and !isguestuser()){
		if(is_siteadmin() and !$trackadmin){
			return false;
		}
		$intelliboardPage = (isset($_COOKIE['intelliboardPage'])) ? clean_param($_COOKIE['intelliboardPage'], PARAM_ALPHANUMEXT) : '';
		$intelliboardParam = (isset($_COOKIE['intelliboardParam'])) ? clean_param($_COOKIE['intelliboardParam'], PARAM_INT) : 0;
		$intelliboardTime = (isset($_COOKIE['intelliboardTime'])) ? clean_param($_COOKIE['intelliboardTime'], PARAM_INT) : 0;

		if(!empty($intelliboardPage)){
			$userDetails = (object)local_intelliboard_user_details();
			if($data = $DB->get_record('local_intelliboard_tracking', array('userid' => $USER->id, 'page' => $intelliboardPage, 'param' => $intelliboardParam), 'id, visits, timespend, lastaccess')){
				if(!$ajaxRequest){
					$data->visits = $data->visits + 1;
					$data->lastaccess = time();
				}
				$data->timespend = $data->timespend + $intelliboardTime;
				$data->useragent = $userDetails->useragent;
				$data->useros = $userDetails->useros;
				$data->userlang = $userDetails->userlang;
				$data->userip = $userDetails->userip;
				$DB->update_record('local_intelliboard_tracking', $data);
			}else{
				$courseid = 0;
				if($intelliboardPage == "module"){
					$courseid = $DB->get_field_sql("SELECT c.id FROM {course} c, {course_modules} cm WHERE c.id = cm.course AND cm.id = $intelliboardParam");
				}elseif($intelliboardPage == "course"){
					$courseid = $intelliboardParam;
				}
				$data = new stdClass();
				$data->userid = $USER->id;
				$data->courseid = $courseid;
				$data->page = $intelliboardPage;
				$data->param = $intelliboardParam;
				$data->visits = 1;
				$data->timespend = $intelliboardTime;
				$data->firstaccess = time();
				$data->lastaccess = time();
				$data->useragent = $userDetails->useragent;
				$data->useros = $userDetails->useros;
				$data->userlang = $userDetails->userlang;
				$data->userip = $userDetails->userip;
				$data->id = $DB->insert_record('local_intelliboard_tracking', $data, true);
			}
			if($version >= 2016011300){
				$currentstamp  = strtotime('today');
				if($data->id){
					if($log = $DB->get_record('local_intelliboard_logs', array('trackid' => $data->id, 'timepoint' => $currentstamp))){
						if(!$ajaxRequest){
							$log->visits = $log->visits + 1;
						}
						$log->timespend = $log->timespend + $intelliboardTime;
						$DB->update_record('local_intelliboard_logs', $log);
					}else{
						$log = new stdClass();
						$log->trackid = $data->id;
						$log->visits = 1;
						$log->timespend = $intelliboardTime;
						$log->timepoint = $currentstamp;
						$log->id = $DB->insert_record('local_intelliboard_logs', $log, true);
					}

					if($version >= 2017072300 and isset($log->id)){
						$currenthour  = date('G');
						if($detail = $DB->get_record('local_intelliboard_details', array('logid' => $log->id, 'timepoint' => $currenthour))){
							if(!$ajaxRequest){
								$detail->visits = $detail->visits + 1;
							}
							$detail->timespend = $detail->timespend + $intelliboardTime;
							$DB->update_record('local_intelliboard_details', $detail);
						}else{
							$detail = new stdClass();
							$detail->logid = $log->id;
							$detail->visits = 1;
							$detail->timespend = $intelliboardTime;
							$detail->timepoint = $currenthour;
							$detail->id = $DB->insert_record('local_intelliboard_details', $detail, true);
						}
					}
				}

				$sessions = false; $courses = false;
				if($trackpoint != $currentstamp){
					set_config("trackpoint", $currentstamp, "local_intelliboard");
					set_config("trackusers", '', "local_intelliboard");
					set_config("trackcourses", '', "local_intelliboard");
				}
				if($intelliboardPage == 'course'){
					if($trackcourses){
						$instances = explode(',', $trackcourses);
						if(!in_array($intelliboardParam, $instances)){
							$courses = true;
							set_config("trackcourses", $trackcourses.",".$intelliboardParam, "local_intelliboard");
						}
					}else{
						$courses = true;
						set_config("trackcourses", $intelliboardParam, "local_intelliboard");
					}
				}
				if($trackusers){
					$users = explode(',', $trackusers);
					if(!in_array($USER->id, $users)){
						$sessions = true;
						set_config("trackusers", $trackusers.",".$USER->id, "local_intelliboard");
					}
				}else{
					$sessions = true;
					set_config("trackusers", $USER->id, "local_intelliboard");
				}
				if($data = $DB->get_record('local_intelliboard_totals', array('timepoint' => $currentstamp))){
					if(!$ajaxRequest){
						$data->visits = $data->visits + 1;
					}
					if($sessions){
						$data->sessions = $data->sessions + 1;
					}
					if($courses){
						$data->courses = $data->courses + 1;
					}
					$data->timespend = $data->timespend + $intelliboardTime;
					$DB->update_record('local_intelliboard_totals', $data);
				}else{
					$data = new stdClass();
					$data->sessions = 1;
					$data->courses = ($courses)?1:0;
					$data->visits = 1;
					$data->timespend = $intelliboardTime;
					$data->timepoint = $currentstamp;
					$DB->insert_record('local_intelliboard_totals', $data);
				}
			}
		}
		if($ajaxRequest){
			die("time ".$intelliboardTime);
		}
		if(isset($PAGE->cm->id)){
			$intelliboardPage = 'module';
			$intelliboardParam = $PAGE->cm->id;
		}elseif(isset($PAGE->course->id) and $SITE->id != $PAGE->course->id){
			$intelliboardPage = 'course';
			$intelliboardParam = $PAGE->course->id;
		}elseif(strpos($PAGE->url, '/profile/') !== false){
			$intelliboardPage = 'user';
			$intelliboardParam = $USER->id;
		}else{
			$intelliboardPage = 'site';
			$intelliboardParam = 0;
		}

		$params = new stdClass();
		$params->intelliboardAjax = $ajax;
		$params->intelliboardAjaxUrl = $ajax ? "$CFG->wwwroot/local/intelliboard/ajax.php" : "";
		$params->intelliboardInactivity = $inactivity;
		$params->intelliboardPeriod = 1000;
		$params->intelliboardPage = $intelliboardPage;
		$params->intelliboardParam = $intelliboardParam;
		$params->intelliboardTime = 0;

		$PAGE->requires->js('/local/intelliboard/module.js', false);
		$PAGE->requires->js_init_call('intelliboardInit', array($params), false);

		return true;
	}
}
