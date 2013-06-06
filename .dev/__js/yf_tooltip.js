var tipwidth			= '150px';	// default tooltip width
var tipbgcolor			= '';		// tooltip bgcolor
var appeardelay			= 100;		// tooltip appear speed onMouseover (in miliseconds)
var disappeardelay		= 100;		// tooltip disappear speed onMouseout (in miliseconds)
var vertical_offset		= "0px";	// horizontal offset of tooltip from anchor link
var horizontal_offset	= "0px";	// horizontal offset of tooltip from anchor link
var profy_tooltip_id	= "fixedtipdiv";

/////No further editting needed

var ie4 = document.all;
var ns6 = document.getElementById && !document.all;

if (ie4 || ns6) {
	document.write('<div id="' + profy_tooltip_id + '" style="visibility:hidden;width:'+tipwidth+';' + (tipbgcolor != '' ? 'background-color:'+tipbgcolor+';' : '') + '" onMouseover="clearhidetip()" onMouseout="hidetip(event)" ></div>');
	try {
		document.write('<scr' + 'ipt ty ' + 'pe="text/j' + 'avascr' + 'ipt" sr' + 'c="' + WEB_PATH + 'js/zpeffects/effects.js' + '"></s' + 'cript>');
	} catch (x) {}
}


//mouse capture
var IE = document.all ? true : false;
if (!IE) {
	document.captureEvents(Event.MOUSEMOVE);
}
// Set-up to use getMouseXY function onMouseMove
document.onmousemove = getMouseXY;

// Store mouse coordinates
var mouseX = 0;
var mouseY = 0;

var ajaxcache = new Array();

function getMouseXY(e) {
	try {
		if (IE) { // grab the x-y pos.s if browser is IE
			mouseX = event.clientX + document.body.scrollLeft
			mouseY = event.clientY + document.body.scrollTop
		} else {  // grab the x-y pos.s if browser is NS
			mouseX = e.pageX
			mouseY = e.pageY
		}  
		// catch possible negative values in NS4
		if (mouseX < 0){
			mouseX = 0
		}
		if (mouseY < 0){
			mouseY = 0
		}
	} catch (x) {}
}

function getposOffset(what, offsettype){
	var totaloffset=(offsettype=="left")? what.offsetLeft-what.scrollLeft : what.offsetTop;
	var parentEl=what.offsetParent;
	while (parentEl!=null){
	  totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft-parentEl.scrollLeft : totaloffset+parentEl.offsetTop;
	  parentEl=parentEl.offsetParent;
	}
	return totaloffset;
}

function showhide(obj, e, visible, hidden, tipwidth){
	if (ie4||ns6) {
		dropmenuobj.style.left = dropmenuobj.style.top = -500;
	}
	if (tipwidth!="") {
		dropmenuobj.widthobj = dropmenuobj.style;
		dropmenuobj.widthobj.width = tipwidth;
	}
	if (e.type=="click" && obj.visibility==hidden || e.type=="mouseover") {
		obj.visibility = visible;
		// Try to apply slide effect
		try {
			$(profy_tooltip_id).slideDown(20);
/*			Zapatec.Effects.show(profy_tooltip_id, 20, 'slide');*/
		} catch (x) {}
	} else if (e.type=="click") {
		obj.visibility = hidden;
	}
}

function iecompattest(){
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function clearbrowseredge(obj, whichedge){
	var edgeoffset = (whichedge == "rightedge") ? parseInt(horizontal_offset) * -1 : parseInt(vertical_offset) * -1;
	if (whichedge == "rightedge") {
		var windowedge = ie4 && !window.opera ? iecompattest().scrollLeft+iecompattest().clientWidth-15 : window.pageXOffset+window.innerWidth-15;
		dropmenuobj.contentmeasure = dropmenuobj.offsetWidth;
		if (windowedge-dropmenuobj.x < dropmenuobj.contentmeasure) {
			edgeoffset = dropmenuobj.contentmeasure-obj.offsetWidth;
		}
	} else {
		var windowedge=ie4 && !window.opera ? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18;
		dropmenuobj.contentmeasure=dropmenuobj.offsetHeight;
		if (windowedge-dropmenuobj.y < dropmenuobj.contentmeasure) {
			edgeoffset=dropmenuobj.contentmeasure+obj.offsetHeight;
		}
	}
	return edgeoffset;
}

function ajaxtooltip(id, obj, e, tipwidth, force_request_file) {
	if (window.event) {
		event.cancelBubble=true;
	} else if (typeof (e) != "undefined" && e.stopPropagation) {
		e.stopPropagation();
	}

	if (typeof (id) == "undefined" || id == 0 || id == "") {
		return false;
	}

	clearhidetip();

	dropmenuobj = document.getElementById? document.getElementById(profy_tooltip_id) : fixedtipdiv;
	dropmenuobj.innerHTML = "loading...";

	if (ajaxcache[String(id)] != null) {
		 dropmenuobj.innerHTML = ajaxcache[String(id)];
	} else {
		var _url = "";
		if (force_request_file) {
			_url = force_request_file;
		} else {
			if (typeof(TOOLTIP_REQUEST_URL) != "undefined") {
				_url = TOOLTIP_REQUEST_URL;
			} else {
				if (typeof(WEB_PATH) != "undefined") {
					_url = WEB_PATH;
				} else {
					_url = "";
				}
				_url += "?object=user_profile&action=compact_info";
			}
		}
		// JQuery AJAX post
		$.post(
			_url,
			{"id": id},
			function(data){
				dropmenuobj.innerHTML=data;
				ajaxcache[String(id)] = data;
				try {
					_debug_catch(dropmenuobj);
				} catch(e) {}
			}
		);
	}

	if (ie4||ns6){
		showhide(dropmenuobj.style, e, "visible", "hidden", tipwidth)
		dropmenuobj.y=getposOffset(obj, "top")
		dropmenuobj.x=mouseX;
		dropmenuobj.style.left=dropmenuobj.x-clearbrowseredge(obj, "rightedge")+"px"
		dropmenuobj.style.top=dropmenuobj.y-clearbrowseredge(obj, "bottomedge")+obj.offsetHeight+"px"
	}
}

function topic_repliers_ajaxtooltip(id, obj, e, tipwidth) {
	pause(500);
	ajaxtooltip("tr_" + id, obj, e, tipwidth, WEB_PATH + "?object=forum&action=compact_topic_repliers");
}

function post_preview_ajaxtooltip(id, obj, e, tipwidth) {
	pause(500);
	ajaxtooltip("pp_" + id, obj, e, tipwidth, WEB_PATH + "?object=forum&action=compact_post_preview");
}

function help_ajaxtooltip(id, obj, e, tipwidth) {
	ajaxtooltip("help_" + id, obj, e, tipwidth, WEB_PATH + "?object=help&action=show_tip");
}

function gallery_ajaxtooltip(id, obj, e, tipwidth) {
	ajaxtooltip("gallery_" + id, obj, e, tipwidth, WEB_PATH + "?object=gallery&action=compact_view");
}

function pause(numberMillis) {
	var now = new Date();
	var exitTime = now.getTime() + numberMillis;
	while (true) {
		now = new Date();
		if (now.getTime() > exitTime)
			return;
	}
}

function contains_ns6(a, b) {
	if(b) {
		while (b.parentNode)
			if ((b = b.parentNode) == a)
				return true;
	}
	return false;
}

function hidetip(e){
	try {
		if (typeof e != "undefined") {
			if (ie4 && !dropmenuobj.contains(e.toElement)){
				delayhidetip();
			} else if (ns6&&e.currentTarget!= e.relatedTarget&& !contains_ns6(e.currentTarget, e.relatedTarget)) {
				delayhidetip();
			}
		} else {
			delayhidetip();
		}
	} catch (x) {}
}

function realhidetip(e)	{
	if (typeof dropmenuobj != "undefined"){
		if (ie4||ns6)
			dropmenuobj.style.visibility="hidden";
	}
}

function delayhidetip(){
	if (ie4||ns6)
		delayhide = setTimeout("realhidetip()", disappeardelay);
}

function clearhidetip(){
	if (typeof delayhide != "undefined")
		clearTimeout(delayhide);
}