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
	if ($server == 2) {
		$domain = 'eu.';
	} elseif($server == 1) {
		$domain = 'au.';
	} else {
		$domain = '';
	}
	return 'https://'.$domain.'intelliboard.net';
}
function intelliboard_compl_sql($prefix = "", $sep = true)
{
    $completions = get_config('local_intelliboard', 'completions');
    $prefix = ($sep) ? " AND ".$prefix : $prefix;
    if (!empty($completions)) {
        return $prefix . "completionstate IN($completions)";
    } else {
        return $prefix . "completionstate IN(1,2)"; //Default completed and passed
    }
}
function intelliboard_grade_sql($avg = false, $params = null, $alias = 'g.', $round = 2)
{
    $scales = get_config('local_intelliboard', 'scales');
    $raw = get_config('local_intelliboard', 'scale_raw');
    $total = get_config('local_intelliboard', 'scale_total');
    $value = get_config('local_intelliboard', 'scale_value');
    $percentage = get_config('local_intelliboard', 'scale_percentage');

    if ((isset($params->scale_raw) and $params->scale_raw) or ($raw and !isset($params->scale_raw))) {
         if ($avg) {
            return "ROUND(AVG({$alias}finalgrade), $round)";
        } else {
            return "ROUND({$alias}finalgrade, $round)";
        }
    } elseif (isset($params->scales) and $params->scales) {
        $total = $params->scale_total;
        $value = $params->scale_value;
        $percentage = $params->scale_percentage;
        $scales = true;
    } elseif (isset($params->scales) and !$params->scales) {
        $scales = false;
    }

    if ($scales and $total and $value and $percentage) {
        $dif = $total - $value;
        if ($avg) {
            return "ROUND(AVG(CASE WHEN ({$alias}finalgrade - $value) < 0 THEN ((({$alias}finalgrade / $value) * 100) / 100) * $percentage ELSE ((((({$alias}finalgrade - $value) / $dif) * 100) / 100) * $percentage) + $percentage END), $round)";
        } else {
            return "ROUND((CASE WHEN ({$alias}finalgrade - $value) < 0 THEN ((({$alias}finalgrade / $value) * 100) / 100) * $percentage ELSE ((((({$alias}finalgrade - $value) / $dif) * 100) / 100) * $percentage) + $percentage END), $round)";
        }
    }
    if ($avg) {
        return "ROUND(AVG(CASE WHEN {$alias}rawgrademax > 0 THEN ({$alias}finalgrade/{$alias}rawgrademax)*100 ELSE {$alias}finalgrade END), $round)";
    } else {
        return "ROUND((CASE WHEN {$alias}rawgrademax > 0 THEN ({$alias}finalgrade/{$alias}rawgrademax)*100 ELSE {$alias}finalgrade END), $round)";
    }
}
function intelliboard_filter_in_sql($sequence, $column, $params = array(), $prfx = 0, $sep = true, $equal = true)
{
	global $DB;

	$sql = '';
	if($sequence){
		$items = explode(",", clean_param($sequence, PARAM_SEQUENCE));
		if(!empty($items)){
			$key = clean_param($column.$prfx, PARAM_ALPHANUM);
			list($sql, $sqp) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED, $key, $equal);
			$params = array_merge($params, $sqp);
			$sql = ($sep) ? " AND $column $sql ": " $column $sql ";
		}
	}
	return array($sql, $params);
}
function intelliboard($params){
	global $CFG;

	require_once($CFG->libdir . '/filelib.php');

	$tls12 = get_config('local_intelliboard', 'tls12');
	$params['firstname'] = get_config('local_intelliboard', 'te12');
	$params['lastname'] = get_config('local_intelliboard', 'te13');
	$params['email'] = get_config('local_intelliboard', 'te1');
    $params['url'] = $CFG->wwwroot;
	$params['lang'] = current_language();

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


function intelliboard_csv_quote($value) {
	return '"'.str_replace('"',"'",$value).'"';
}
function intelliboard_export_report($json, $itemname, $format = 'csv')
{
	$name =  clean_filename($itemname . '-' . gmdate("Y-m-d"));

	if($format == 'csv'){
		intelliboard_export_csv($json, $name);
	}elseif($format == 'xls'){
		intelliboard_export_xls($json, $name);
	}elseif($format == 'pdf'){
		intelliboard_export_pdf($json, $name);
	}else{
        intelliboard_export_html($json, $name);
	}
}

function intelliboard_export_html($json, $filename)
{
    $html = '<h2>'.$filename.'</h2>';
    $html .= '<table width="100%">';
    $html .= '<tr>';
    foreach ($json->header as $col) {
        $html .= '<th>'. $col->name.'</th>';
    }
    $html .= '</tr>';
    foreach ($json->body as $row) {
    	$html .= '<tr>';
        foreach($row as $col) {
        	$value = str_replace('"', '', $col);
			$value = strip_tags($value);
            $html .= '<td>'. $value.'</td>';
        }
    	$html .= '</tr>';
    }
    $html .= '</table>';
    $html .= '<style>table{border-collapse: collapse; width: 100%;} table tr th {font-weight: bold;} table th, table td {border:1px solid #aaaaaa; padding: 7px 10px; font: 13px/13px Arial;} table tr:nth-child(odd) td {background-color: #f5f5f5;}</style>';

    die($html);
}
function intelliboard_export_csv($json, $filename)
{
	global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');

    $data = array(); $line = 0;
	foreach($json->header as $col){
		$value = str_replace('"', '', $col->name);
		$value = strip_tags($value);
		$data[$line][] = intelliboard_csv_quote($value);
	}
	$line++;
	foreach($json->body as $row){
		foreach($row as $col){
			$value = str_replace('"', '', $col);
			$value = strip_tags($value);
			$data[$line][] = intelliboard_csv_quote($value);
		}
		$line++;
	}
    $delimiters = array('comma'=>',', 'semicolon'=>';', 'colon'=>':', 'tab'=>'\\t');
    csv_export_writer::download_array($filename, $data, $delimiters['tab']);
}

function intelliboard_export_xls($json, $filename)
{
    global $CFG;
    require_once("$CFG->libdir/excellib.class.php");

    $filename .= '.xls';
    $filearg = '-';
    $workbook = new MoodleExcelWorkbook($filearg);
    $workbook->send($filename);
    $worksheet = array();
    $worksheet[0] = $workbook->add_worksheet('');
    $rowno = 0; $colno = 0;
    foreach ($json->header as $col) {
        $worksheet[0]->write($rowno, $colno, $col->name);
        $colno++;
    }
    $rowno++;
    foreach ($json->body as $row) {
        $colno = 0;
        foreach($row as $col) {
        	$value = str_replace('"', '', $col);
			$value = strip_tags($value);
            $worksheet[0]->write($rowno, $colno, $value);
            $colno++;
        }
        $rowno++;
    }
    $workbook->close();
    exit;
}
function intelliboard_export_pdf($json, $name)
{
    global $CFG, $SITE;

	require_once($CFG->libdir . '/pdflib.php');

    $fontfamily = PDF_FONT_NAME_MAIN;

    $doc = new pdf();
    $doc->SetTitle($name);
    $doc->SetAuthor('Moodle ' . $CFG->release);
    $doc->SetCreator('local/intelliboard/reports.php');
    $doc->SetKeywords($name);
    $doc->SetSubject($name);
    $doc->SetMargins(15, 30);
    $doc->setPrintHeader(true);
    $doc->setHeaderMargin(10);
    $doc->setHeaderFont(array($fontfamily, 'b', 10));
    $doc->setHeaderData('', 0, $SITE->fullname, $name);
    $doc->setPrintFooter(true);
    $doc->setFooterMargin(10);
    $doc->setFooterFont(array($fontfamily, '', 8));
    $doc->AddPage();
    $doc->SetFont($fontfamily, '', 8);
    $doc->SetTextColor(0,0,0);
    $name .= '.pdf';
    $html = '<table width="100%">';
    $html .= '<tr>';
    foreach ($json->header as $col) {
        $html .= '<th>'. $col->name.'</th>';
    }
    $html .= '</tr>';
    foreach ($json->body as $row) {
    	$html .= '<tr>';
        foreach($row as $col) {
        	$value = str_replace('"', '', $col);
			$value = strip_tags($value);
            $html .= '<td>'. $value.'</td>';
        }
    	$html .= '</tr>';
    }
    $html .= '</table>';
    $html .= '<style>';
    $html .= 'td{border:0.1px solid #000; padding:10px;}';
    $html .= '</style>';
    $doc->writeHTML($html);
    $doc->Output($name);
    exit();
}
