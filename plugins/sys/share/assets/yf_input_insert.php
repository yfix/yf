<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(<<<END
var uagent    = navigator.userAgent.toLowerCase();
var is_safari = ( (uagent.indexOf('safari') != -1) || (navigator.vendor == "Apple Computer, Inc.") );
var is_ie     = ( (uagent.indexOf('msie') != -1) && (!is_opera) && (!is_safari) && (!is_webtv) );
var is_opera  = (uagent.indexOf('opera') != -1);
var is_webtv  = (uagent.indexOf('webtv') != -1);
var is_win    =  ( (uagent.indexOf("win") != -1) || (uagent.indexOf("16bit") !=- 1) );
var ua_vers   = parseInt(navigator.appVersion);
//insert_word("[test]", "[/test]", true);
// GENERAL INSERT FUNCTION
function insert_word(ibTag, ibClsTag, isSingle, force_id) {
	p_obj = document.getElementById(force_id || "tag_replace");
	var isClose = false;
	if ((ua_vers >= 4) && is_ie && is_win)	{
		if (p_obj.isTextEdit) {
			p_obj.focus();
			var sel = document.selection;
			var rng = sel.createRange();
			rng.colapse;
			if((sel.type == "Text" || sel.type == "None") && rng != null) {
				if (ibClsTag != "" && rng.text.length > 0) {
					ibTag += rng.text + ibClsTag;
				} else if(isSingle) {
					isClose = true;
				}
				rng.text = ibTag;
			}
		} else {
			if(isSingle) {
				isClose = true;
			}
			p_obj.value += ibTag;
		}
	}
	else if (p_obj.selectionEnd) { 
		var ss = p_obj.selectionStart;
		var st = p_obj.scrollTop;
		var es = p_obj.selectionEnd;
		if (es <= 2) {
			es = p_obj.textLength;
		}
		var start  = (p_obj.value).substring(0, ss);
		var middle = (p_obj.value).substring(ss, es);
		var end    = (p_obj.value).substring(es, p_obj.textLength);
		// text range
		if (p_obj.selectionEnd - p_obj.selectionStart > 0) {
			middle = ibTag + middle + ibClsTag;
		} else {
			middle = ibTag + middle;
			if (isSingle) {
				isClose = true;
			}
		}
		p_obj.value = start + middle + end;
		var cpos = ss + (middle.length);
		p_obj.selectionStart = cpos;
		p_obj.selectionEnd   = cpos;
		p_obj.scrollTop      = st;
	}
	else {
		if (isSingle) {
			isClose = true;
		}
		p_obj.value += ibTag;
	}
	p_obj.focus();
	return isClose;
}
END
			),
		),
	),
);
