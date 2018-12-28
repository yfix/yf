<?php

return function () {
    return [
    'versions' => ['master' => [
        'css' => ['
		'],
        'js' => ['
		'],
        'jquery' => [
<<<END
	// Prepare html for the templates inline editor
	$("body").append("" 
		+ "<div id='inline_edit_stpl'>" 
		+ "<div id='inline_edit_header'></div>"
		+ "<table border='0' cellspacing='0' cellpadding='0' width='99%'><tr valign='top'><td width='85%'><textarea name='edit_stpl' id='inline_edit_text'></textarea></td><td width='15%'>"
		+ "<b>HTML Entities</b>:<br /><small>_ => &amp;#95;<br />' => &amp;prime;<br />&quot; => &amp;quot;<br />&frasl; => &amp;frasl;<br />&#92; => &amp;#92;<br />[ => &amp;#91;<br />] => &amp;#93;<br />( => &amp;#40;<br />) => &amp;#41;<br />{ => &amp;#123;<br />} => &amp;#125;<br />? => &amp;#63;<br />! => &amp;#33;<br />| => &amp;#124;<br />&bull; => &amp;bull;<br />&copy; => &amp;copy;<br />$ => &amp;#36;<br />@ => &amp;#64;<br /></small>"
		+ "</td></tr></table>" 
		+ "<input type='button' value='SAVE' id='inline_edit_save' />" 
		+ "<input type='button' value='CANCEL' id='inline_edit_cancel' />" 
		+ "</div>"
	);

	if (_USE_EDITAREA) {
		var _editarea_path = WEB_PATH + "js/editarea/edit_area/edit_area_full.js";
		$.getScript(_editarea_path, function(){
			if (typeof editAreaLoader == "undefined") {
				return false;
			}
			editAreaLoader.init({
				id: "inline_edit_text"	// id of the textarea to transform		
				,start_highlight: true	// if start with highlight
				,allow_resize: "both"
				,allow_toggle: true
				,min_width: 700
				,min_height: 480
				,language: "en"
				,syntax: "html"
			});
		})
	}

	// Catch inline template edit
	$("span.stpl_name_inline").on("contextmenu", function(e) {
		e.stopPropagation(); e.preventDefault();
		_stpl_name = $(this).html();
		// Construct edit area
		$("#inline_edit_header").html("EDIT: " + _stpl_name);
		$("#inline_edit_stpl").css({"display" : "block"});
		$("#inline_edit_text").val("loading...");
		$.get(_edit_stpl_url + "&id=" + _stpl_name, function(data) {
			_old_text = data;
			$("#inline_edit_text").val(data);
			if (_USE_EDITAREA) {
				editAreaLoader.setValue("inline_edit_text", data);
			}
		})
		window.scrollTo(0,0);
		return false;
	});

	// Catch save action
	$("#inline_edit_save").on("click", function(e2) {
		e2.stopPropagation(); e2.preventDefault();
		// Catch if nothing changed
		if (_old_text == "" 
			|| $("#inline_edit_text").val() == "loading..." 
			|| _old_text == $("#inline_edit_text").val()
		) {
			if (_old_text != "") {
				alert("Nothing changed");
			}
			$("#inline_edit_stpl").css({"display" : "none"});
			_old_text = "";
			return false;
		}
		$.post(_edit_stpl_url + "&id=" + _stpl_name, {"text" : $("#inline_edit_text").val()}, function(data) {
			$("#inline_edit_stpl").css({"display" : "none"});
			alert("STPL: \n\n" + _stpl_name + "\n\nSERVER SAID: \n\n" + data);
			window.location.reload();
			return false;				
		})
		return false;
	});

	// Catch cancel action
	$("#inline_edit_cancel").on("click", function(e){
		e.stopPropagation(); e.preventDefault();
		// Check if text has changed but we are cancelling
		if (_old_text != "" && _old_text != $("#inline_edit_text").val()) {
			if (!confirm("Text has changed, are you sure you wan't to quit editing?")) {
				return false;
			}
		}
		$("#inline_edit_stpl").css({"display" : "none"});
		_old_text = "";
		return false;
	});

	// Add special class for the all elements that have template names attributes
	$("[stpl_name]").each(function(i) {
		var _stpl_name	= $(this).attr("stpl_name");
		var _tag_name	= $(this).get(0).tagName.toLowerCase();
		try {
			$(this).prepend("<span class='stpl_name_inline'>" + _stpl_name + "</span> ");
		} catch (e) {
		}
	});
END
    ], ]],
    'require' => [
        'asset' => 'jquery',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
};
