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

function clean_raw($value, $mode = true)
{
	$params = array("'","`");
	if($mode){
		$params[] = '"';
		$params[] = '(';
		$params[] = ')';
	}
	return str_replace($params, '', $value);
}

function intelliboard_clean($content){
	return trim($content);
}
function intelliboard_url(){
	$server = get_config('local_intelliboard', 'server');
	if($server == 2){
		$domain = 'eu.';
	}elseif($server == 1){
		$domain = 'au.';
	}else{
		$domain = '';
	}
	return 'https://'.$domain.'intelliboard.net';
}
function intelliboard($params){
	global $CFG;

	require_once($CFG->libdir . '/filelib.php');

	$tls12 = get_config('local_intelliboard', 'tls12');
	if($tls12){
		$options = array('CURLOPT_SSL_CIPHER_LIST'=>'ECDHE_ECDSA_AES_128_GCM_SHA_256');
	}else{
		$options = array();
	}
	$url = intelliboard_url();
	$curl = new curl;
	$json = $curl->post($url.'/dashboard/api', $params, $options);
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
	return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
}
