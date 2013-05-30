function _fix_width(){
	// Fix for the min-width
	var minwidth = 940;
	if (document.documentElement.clientWidth < minwidth) {
		document.body.style.width = minwidth + "px";
	} else {
		document.body.style.width = "auto";
	}
	// Fix for the margin-left for #contentwrapper
	var _content_left_margin = "30px";
	var _new_value = parseInt($("#container").css("width")) - parseInt($("#left_column").css("width")) - parseInt(_content_left_margin);

	$("#contentwrapper").css("width", _new_value + "px");
}
// JQuery on DOM Ready
$(function(){
	if (!$.browser.msie) {
		return false;
	}
	_fix_width();
	$(window).resize(function(e){
		_fix_width();
	});
});
