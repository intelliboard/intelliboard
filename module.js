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

function intelliboardInit(options){
	options = options || {};
	intelliboardAjax = options.intelliboardAjax || intelliboardAjax;
	intelliboardAjaxUrl = options.intelliboardAjaxUrl || intelliboardAjaxUrl;
	intelliboardInactivity = options.intelliboardInactivity  || intelliboardInactivity ;
	intelliboardPeriod = options.intelliboardPeriod || intelliboardPeriod;
	intelliboardInterval = setInterval(intelliboardProgress, intelliboardPeriod);
	
	intelliboardPage = getIntelliboardCookie('intelliboardPage');
	intelliboardParam = getIntelliboardCookie('intelliboardParam');
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