<?php

function seconds_to_time($t,$f=':'){
	if($t < 0){
		return "00:00:00";
	}
	$hours = floor($t/3600);
	$mins = ($t/60)%60;
	$secs = $t%60;
	return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
}
