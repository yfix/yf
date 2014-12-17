<?php

return array(
	'versions' => array('master' => array(
		'css' => '#debug_console { display: none; }',
		'jquery' => array(
			'params' => array('class' => 'yf_debug_console_asset'),
			'content' => 
<<<END
	var debug_window_name = "yf_debug_frame"
	var debug_console_use_iframe = false;
	var debug_console_head = [ ];
	// Allow to override JS, CSS as array of html elements strings
	if (typeof debug_console_override_head == "object") {
		$.each(debug_console_override_head, function(i, _html){
			debug_console_head.push( _html )
		})
		// Add debug console specific .yf_core items, not all, as in other case
		$('script[type="text/javascript"], link[rel=stylesheet], style', '#debug_console').not('.yf_debug_console_asset').each(function(){
			debug_console_head.push( $(this).clone(true)[0].outerHTML )
		})
	} else {
//		$('script[type="text/javascript"], link[rel=stylesheet], style', 'head').not('.yf_debug_console_asset').each(function(){
		$('link[rel=stylesheet], style', 'head').not('.yf_debug_console_asset').each(function(){
			debug_console_head.push( $(this).clone(true)[0].outerHTML )
		})
		$('script[type="text/javascript"]', 'body').not('.yf_debug_console_asset').each(function(){
			debug_console_head.push( $(this).clone(true)[0].outerHTML )
		})
	}
	if (debug_console_use_iframe) {
// TODO: need to do popup div display of tab contents in this mode
		$('<iframe src="about:blank" id="' + debug_window_name + '" style="position:fixed; bottom:0; height:10%; width:100%; border:0;" border="0"></iframe>').appendTo("body");
		var debug_frame = $("iframe#dbgif")[0]
	} else {
		var debug_frame = window.open('', debug_window_name, 'width=800,height=600,location=no,menubar=no,status=no,top=100,left=100,titlebar=no,toolbar=no')
	}
	// We need the iframe document object, different browsers different ways
	if (typeof debug_frame != 'undefined') {
		var frame_doc = debug_frame.document;
	} else {
		console.error('Debug console popup: debug_frame not created, maybe popup blocker killed it');
	}
	if (typeof frame_doc != 'undefined') {
		if (debug_frame.contentDocument) {
			frame_doc = debug_frame.contentDocument;
		} else if (debug_frame.contentWindow) {
			frame_doc = debug_frame.contentWindow.document;
		}
		// We open the document of the empty frame and we write desired content.
		frame_doc.open();
		// Copy JS, CSS from original frame
		$.each(debug_console_head, function(i, _html){
			frame_doc.writeln( _html )
		})
		frame_doc.writeln( $("#debug_console").clone(true)[0].outerHTML );
		frame_doc.close();
		$("#debug_console", frame_doc).show()
		$("#debug_console").hide().remove()
	} else {
		console.error('Debug console popup: cannot access frame document');
	}
END
	))),
);
