<?php
// IntelliBoard.net
//
// IntelliBoard.net is built to work with any LMS designed in Moodle
// with the goal to deliver educational data analytics to single dashboard instantly.
// With power to turn this analytical data into simple and easy to read reports,
// IntelliBoard.net will become your primary reporting tool.
//
// Moodle
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// IntelliBoard.net is built as a local plugin for Moodle.

/**
 * IntelliBoard.net
 *
 *
 * @package    	intelliboard
 * @copyright  	2015 IntelliBoard, Inc
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @created by	IntelliBoard, Inc
 * @website		www.intelliboard.net
 */

function getUserDetails()
{
    $user_agent     =   $_SERVER['HTTP_USER_AGENT'];
    $platform    =   "Unknown OS Platform";
    $os_array       =   array(
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
                            '/msie/i'       =>  'Internet Explorer',
                            '/firefox/i'    =>  'Firefox',
                            '/safari/i'     =>  'Safari',
                            '/chrome/i'     =>  'Chrome',
                            '/opera/i'      =>  'Opera',
                            '/netscape/i'   =>  'Netscape',
                            '/maxthon/i'    =>  'Maxthon',
                            '/konqueror/i'  =>  'Konqueror',
                            '/mobile/i'     =>  'Mobile browser'
                        );
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
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
	$userlang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	$userlang = ($userlang) ? $userlang : 'Unknown';

	return array(
        'useragent'  => $browser,
        'useros' => $platform,
        'userip' => $ip,
        'userlang'     => $userlang
    );
}
function insert_intelliboard_tracking($ajaxRequest = false){
    global $CFG, $PAGE, $SITE, $DB, $USER;

	$version = get_config('local_intelliboard', 'version');
	$intelliboard = optional_param('intelliboard', 0, PARAM_INT);
	$enabled = get_config('local_intelliboard', 'enabled');
	$ajax = (int) get_config('local_intelliboard', 'ajax');
	$inactivity = (int) get_config('local_intelliboard', 'inactivity');
	$trackadmin = get_config('local_intelliboard', 'trackadmin');
	$trackcourses = get_config('local_intelliboard', 'trackcourses');
	$trackusers = get_config('local_intelliboard', 'trackusers');
	$trackpoint = get_config('local_intelliboard', 'trackpoint');
	$path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

	if(strpos($path,'cron.php') !== false){
		return;
	}

	//die('guest');

	if ($enabled and isloggedin() and !isguestuser()){
		if(is_siteadmin() and !$trackadmin){
			return;
		}


		$intelliboardPage = (isset($_COOKIE['intelliboardPage'])) ? clean_param($_COOKIE['intelliboardPage'], PARAM_ALPHANUMEXT) : '';
		$intelliboardParam = (isset($_COOKIE['intelliboardParam'])) ? clean_param($_COOKIE['intelliboardParam'], 0, PARAM_INT) : 0;
		$intelliboardTime = (isset($_COOKIE['intelliboardTime'])) ? clean_param($_COOKIE['intelliboardTime'], 0, PARAM_INT) : 0;

		if(!empty($intelliboardPage)){
			$userDetails = (object)getUserDetails();
			if($data = $DB->get_record('local_intelliboard_tracking', array('userid' => $USER->id, 'page' => $intelliboardPage, 'param' => $intelliboardParam), 'id, visits, timespend, lastaccess')){
				if(!$ajaxRequest){
					$data->visits = $data->visits + 1;
				}
				$data->timespend = $data->timespend + $intelliboardTime;
				$data->lastaccess = time();
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
				$data->id = $DB->insert_record('local_intelliboard_tracking', $data);
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
						$DB->insert_record('local_intelliboard_logs', $log);
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

		SetCookie('intelliboardPage', $intelliboardPage, time()+3600, "/");
		SetCookie('intelliboardParam', $intelliboardParam, time()+3600, "/");
		SetCookie('intelliboardTime', 0, time()+3600, "/");

		$params = new stdClass();
		$params->intelliboardAjax = $ajax;
		$params->intelliboardAjaxUrl = $ajax ? "$CFG->wwwroot/local/intelliboard/ajax.php" : "";
		$params->intelliboardInactivity = $inactivity;
		$params->intelliboardPeriod = 1000;

		$PAGE->requires->js('/local/intelliboard/assets/js/module.js', false);
		$PAGE->requires->js_function_call('intelliboardInit', array($params), false);
	}
}
$ajaxRequest = (isset($ajaxRequest)) ? $ajaxRequest : false;
insert_intelliboard_tracking($ajaxRequest);
