<?php

return function() {

return array(
	'versions' => array('master' => array(
		'css' => array('
		'),
		'js' => array('
		'),
		'jquery' => array(
<<<END
	// Prepare html for the tips inline editor
	$("body").append("" 
		+ "<div id='inline_edit_tip'>" 
		+ "<div id='inline_edit_tip_header'></div>"
		+ "<table border='0' cellspacing='0' cellpadding='0' width='500'><tr valign='top'><td width='90%'><textarea name='edit_tip' id='inline_edit_tip_text'></textarea></td>"
		+ "</tr></table>" 
		+ "<input type='button' value='SAVE' id='inline_edit_tip_save' />" 
		+ "<input type='button' value='CANCEL' id='inline_edit_tip_cancel' />" 
		+ "</div>"
	);

	// Add tooltip edit button
	$(".help_tooltip").each(function() {
		$(this).after("<span class='tooltip_edit_inline' yf:tip_id=" + $(this).attr("yf:tip_id") + ">Edit Tip</span>");
	});

	// Catch tip inline edit
	$("span.tooltip_edit_inline").on("contextmenu", function(e){
		_tip_id = $(this).attr("yf:tip_id");

		// Construct edit area
		$("#inline_edit_tip_header").html("EDIT TIP: " + _tip_id);
		$("#inline_edit_tip").css({"display" : "block"});
		$("#inline_edit_tip_text").val("loading...");

		$.post(_tip_text_url + "&id=help_" + _tip_id, function(data) {
			if (data.substring(0, 7) == "No info") {
				data = "";
			}
			txt_arr = data.split("<hr>");
			data = txt_arr[0];
			_old_tip_text = data;
			// determine if there is <br /> tags in a text
			var _regexp = new RegExp("<br />", "gi");
			var _matches = _regexp.exec(data);
			// split string into array by <br /> tag
			if (_matches) {
				data_array = data.split("<br />");
				var data2 = "";
				for (_key in data_array) {
					data2 += data_array[_key];
				}
				data = data2;
			}
 
			$("#inline_edit_tip_text").val(data);
			window.scrollTo(0,0);
		});

		return false;
	});

	// Catch cancel action
	$("#inline_edit_tip_cancel").on("click", function(e){
		e.stopPropagation(); e.preventDefault();
		// Check if text has changed but we are cancelling
		if (_old_tip_text != "" && _old_tip_text != $("#inline_edit_tip_text").val()) {
			if (!confirm("Tip text has changed, are you sure you wan't to quit editing?")) {
				return false;
			}
		}
		$("#inline_edit_tip").css({"display" : "none"});
		_old_tip_text = "";
		return false;
	});

	// Catch save action
	$("#inline_edit_tip_save").click(function(e){
		e.stopPropagation(); e.preventDefault();

		// Catch if nothing changed
		if (_old_tip_text == "" 
			|| $("#inline_edit_tip_text").val() == "loading..." 
			|| _old_tip_text == $("#inline_edit_tip_text").val()
		) {
			if (_old_tip_text != "") {
				alert("Nothing changed");
			}
			$("#inline_edit_tip").css({"display" : "none"});
			_old_tip_text = "";
			return false;
		}

		$.post(_edit_tip_url, {"text" : $("#inline_edit_tip_text").val(), "name" : _tip_id}, function(data) {
			$("#inline_edit_tip").css({"display" : "none"});
			alert("TIP: \n\n" + _tip_id + "\n\nSERVER SAID: \n\n" + data);
			window.location.reload();
			return false;				
		})
	});

	// Catch keyboard keys
	$(document).on("keyup", "input", function(e){
		if (e.keyCode != 27) { // "Esc" key (27) - cancel editing
			return true;
		}
		e.stopPropagation(); e.preventDefault();
		// Hide tips edit div
		if (_old_tip_text != "" && _old_tip_text != $("#inline_edit_tip_text").val()) {
			if (!confirm("Tip text has changed, are you sure you wan't to quit editing?")) {
				return false;
			}
		}
		$("#inline_edit_tip").css({"display" : "none"});
		_old_tip_text = "";
	});
END
	))),
	'require' => array(
		'asset' => 'jquery',
	),
	'config' => array(
		'no_cache' => true,
	),
);

};