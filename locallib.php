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


function intelliboard($params){
	global $CFG;

	require_once($CFG->libdir . '/filelib.php');

	$tls12 = get_config('local_intelliboard', 'tls12');
	if($tls12){
		$options = array('CURLOPT_SSL_CIPHER_LIST'=>'ECDHE_ECDSA_AES_128_GCM_SHA_256');
	}else{
		$options = array();
	}


	$curl = new curl;
	$json = $curl->post('https://intelliboard.net/dashboard/api', $params, $options);
	$data = (object)json_decode($json);

	$data->content = (isset($data->content))?$data->content:'';
	$data->token = (isset($data->token))?$data->token:'';
	$data->reports = (isset($data->reports))?$data->reports:null;
	$data->alert = (isset($data->alert))?$data->alert:'';

	return $data;
}
function seconds_to_time($t,$f=':'){
	if($t < 0){
		return "00:00:00";
	}
	$hours = floor($t/3600);
	$mins = ($t/60)%60;
	$secs = $t%60;
	return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
}
