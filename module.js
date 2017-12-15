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

var intelliboardAjax = 30;
var intelliboardAjaxUrl = "";
var intelliboardAjaxCounter = 0;
var intelliboardCounter = 0;
var intelliboardInactivity = 60;
var intelliboardPeriod = 1000;
var intelliboardInterval = null;
var intelliboardPage = '';
var intelliboardParam = '';
var intelliboardTime = 0;

function intelliboardInit(Y, options){
	options = options || {};
	intelliboardAjax = options.intelliboardAjax || intelliboardAjax;
	intelliboardAjaxUrl = options.intelliboardAjaxUrl || intelliboardAjaxUrl;
	intelliboardInactivity = options.intelliboardInactivity  || intelliboardInactivity ;
	intelliboardPeriod = options.intelliboardPeriod || intelliboardPeriod;
	intelliboardInterval = setInterval(intelliboardProgress, intelliboardPeriod);

	intelliboardPage = options.intelliboardPage || intelliboardPage;
	intelliboardParam = options.intelliboardParam || intelliboardParam;
	intelliboardTime = options.intelliboardTime || intelliboardTime;
}

function intelliboardProgress(){
	if(intelliboardCounter <= intelliboardInactivity){
		intelliboardTime++;
		intelliboardCounter++;
		intelliboardAjaxCounter++;
		if(intelliboardAjaxCounter == intelliboardAjax && intelliboardAjaxUrl && intelliboardAjax){
			sendIntelliboardTime(intelliboardTime);
			intelliboardAjaxCounter = 0;
		}
	}
}
if (document.addEventListener) {
	document.addEventListener("mousemove", clearIntelliboardCounter);
	document.addEventListener("keypress", clearIntelliboardCounter);
	document.addEventListener("scroll", clearIntelliboardCounter);
	window.addEventListener("beforeunload", resetIntelliboardParams);
} else if (document.attachEvent) {
	document.attachEvent("mousemove", clearIntelliboardCounter);
	document.attachEvent("keypress", clearIntelliboardCounter);
	document.attachEvent("scroll", clearIntelliboardCounter);
	window.addEventListener("beforeunload", resetIntelliboardParams);
}
function sendIntelliboardTime(time){
	if(!time){
		return;
	}
	var xmlhttp;
	if (window.XMLHttpRequest) {
		xmlhttp = new XMLHttpRequest();
	} else {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState == 4 ){
		   if(xmlhttp.status == 200){
				intelliboardTime = intelliboardTime - time;
		   }
		}
	}
	resetIntelliboardParams();
	xmlhttp.open("GET", intelliboardAjaxUrl, false);
	xmlhttp.send();
}
function resetIntelliboardParams() {
	setIntelliboardCookie('intelliboardPage', intelliboardPage);
	setIntelliboardCookie('intelliboardParam', intelliboardParam);
	setIntelliboardCookie('intelliboardTime', intelliboardTime);
}
function clearIntelliboardCounter() {
	intelliboardCounter = 0;
	intelliboardWarningTime = 0;
	intelliboardLogoutTime = 0;
}
function getIntelliboardCookie(name) {
	var matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : 0;
}
function setIntelliboardCookie(name, value, options) {
	options = options || {};
	var expires = options.expires;
	if (typeof expires == "number" && expires) {
		var d = new Date();
		d.setTime(d.getTime() + expires*1000);
		expires = options.expires = d;
	}
	if (expires && expires.toUTCString) {
		options.expires = expires.toUTCString();
	}
	options.path = "/";
	value = encodeURIComponent(value);
	var updatedCookie = name + "=" + value;
	for(var propName in options) {
		updatedCookie += "; " + propName;
		var propValue = options[propName];
		if (propValue !== true) {
			updatedCookie += "=" + propValue;
		}
	}
	document.cookie = updatedCookie;
}
function deleteIntelliboardCookie(name) {
	setIntelliboardCookie(name, "", { expires: -1 })
}
