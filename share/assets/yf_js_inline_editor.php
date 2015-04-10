<?php

return function() {

#{include("system/js_inline_editor_locale_vars")}
#{{--include("system/js_inline_editor_templates")--}}
#{{--include("system/js_inline_editor_tips")--}}

// TODO
$i18n_for_page = array();
$i18n_not_translated = array();

return array(
	'versions' => array('master' => array(
		'css' => array('
			span.locale_tr, .input-append span.locale_tr, .input-prepend span.locale_tr { background: yellow; color: black; font-weight: bold; font-size: 13px; z-index: 1000; margin: 1px; }
			span.locale_not_tr { background: #F19AF4; z-index: 1000; margin: 1px; }
			span.stpl_name_inline { border:1px dashed black; font-size: 9px; color:black; font-weight: bold; background:#E8CD92; z-index: 1000; }
			#inline_edit_stpl { width:800px; height:570px; position:absolute; left:20px; top:20px; z-index:1000; background:#ddd; border: 2px ridge black; display: none; }
			#inline_edit_header { width:800px; height: 20px; border-bottom: 2px ridge black; background:yellow; font-weight: bold; }
			textarea#inline_edit_text { overflow:scroll; width:98%; height:505px; }
			span.var_old_value { background:black; color: white; font-size: 10px; }
			span.tooltip_edit_inline { background:blue; color: white; font-weight: bold; font-size: 10px; cursor: hand; }
			#inline_edit_tip { width:500px; height:250px; position:absolute; left:20px; top:20px; z-index:1000; background:#ddd; border: 2px ridge black; display: none; }
			#inline_edit_tip_header { width:99%; height: 20px; padding-top: 5px; padding-left: 5px; border-bottom: 2px ridge black; background:blue; color:white; font-weight: bold; }
			#inline_edit_tip_text { overflow:scroll; width:99%; height:200px; }
		'),
		'js' => array('
			var _form_action		= "'.url('/dynamic/save_locale_var').'"
			, _edit_stpl_url		= "'.url('/dynamic/edit_locale_stpl').'"
			, _edit_tip_url			= "'.url('/dynamic/edit_tip').'"
			, _tip_text_url			= "'.url('/help/show_tip/0/no_debug').'"
			, WEB_PATH				= "'.(MAIN_TYPE_USER ? WEB_PATH : ADMIN_WEB_PATH).'"
			, _i18n_for_page		= '.json_encode($i18n_for_page).';
			. _i18n_not_translated	= '.json_encode($i18n_not_translated).';
		'),
		'jquery' => array(
<<<END
			var _last_tr_item	= null
			, _last_tr_html		= null
			, _edited_value		= null
			, _source_var		= null
			, _stpl_name		= ""
			, _old_clicks		= { }
			, _old_text			= ""
			, _just_saved		= 0
			, _old_tip_text		= ""
			, _tip_just_saved	= 0
			, _tip_id			= 0
			, _USE_EDITAREA		= 0

	$("input[value*=locale_tr]").not("[type=text]").each(function(){
		var _old_val = $(this).val();
		$(this).val( remove_locale_tr_span(_old_val) ).after( _old_val );
	});
	$("input[placeholder]").each(function(){
		var _old_placeholder = $(this).attr("placeholder").replace(/&lt;/ig, "<").replace(/&gt;/ig, ">");
		$(this).attr("placeholder", remove_locale_tr_span( _old_placeholder )).after( _old_placeholder )
	});
	$("button").each(function(){
		var _old_html = $(this).html().replace(/&lt;/ig, "<").replace(/&gt;/ig, ">");
		$(this).html( remove_locale_tr_span(_old_html) ).after( _old_html );

		var _old_val = $(this).val();
		$(this).val( remove_locale_tr_span(_old_val) );
	});

	// Highlight not translated vars
	if (_i18n_not_translated) {
		$("span.locale_tr").each(function(){
			if (_i18n_not_translated[($(this).html()).toLowerCase()]) {
				$(this).addClass("locale_not_tr");
			}
		});
	}

	// Save edited var on double click
	$(document).on("dblclick", function(e){
		_my_save_var();
		return false;
	});

	// Catch keyboard keys Enter and Esc
	$(document).on("keyup", function(e){
		var _key_code = e.keyCode;
		if (_key_code != 13 && _key_code != 27) {
			return true;
		}
//		e.stopPropagation(); e.preventDefault();
		// "Enter"
		if (_key_code == 13) {
			_my_save_var();
		}
		// "Esc" -> cancel editing
		if (_key_code == 27) {
			if (_last_tr_item != null) {
				_last_tr_item.html(_last_tr_html);
				_last_tr_item.parent().off("click");
				_last_tr_item = null;
			}
			// Hide stpl edit div
			if (_old_text != "" && _old_text != $("#inline_edit_text").val()) {
				if (!confirm("Text has changed, are you sure you want to quit editing?")) {
					return false;
				}
			}
			$("#inline_edit_stpl").css({"display" : "none"});
			_old_text = "";
		}
		return false;

	});

	// Revert last state of the previous element
	function _my_save_var () {
		if (_last_tr_item == null) {
//			console.log('_last_tr_item:', _last_tr_item)
//			console.log('_i18n_for_page:', _i18n_for_page)
			return false;
		}
		var _edited_value = _last_tr_item.find("input.editable_tr").val();
		var _source_var = "";
		if (_last_tr_html != "") {
			_source_var = _last_tr_item.find("input.editable_tr").attr("s_var");
		}
		if (_edited_value != "" && _source_var != "" && $.trim(_last_tr_html) != $.trim(_edited_value)
			&& confirm("Are you sure you want to save your changes?")
		) {
			$.post(_form_action, { "source_var": _source_var, "edited_value":_edited_value }, function(data) {
				// Translate all same elements on page
				$("span.locale_tr").each(function(i){
					if (_last_tr_html != "" && $(this).html() == _last_tr_html) {
						$(this).html(_edited_value);
					}
				});
				alert("NEW:\n\n" + _edited_value + "\n\nOLD:\n\n" + _last_tr_html + "\n\nSERVER SAID:\n\n" + data);
				_last_tr_html = "";
				// Allow further edit this var without page refresh
				_i18n_for_page[_edited_value] = _source_var;
			});
			_last_tr_item.html(_edited_value);
		} else {
			if (_last_tr_html != "") {
				_last_tr_item.html(_last_tr_html);
			}
		}
		_last_tr_item.parent().off("click");
		_last_tr_item = null;
	}

	// Fix "input" fields
	function remove_locale_tr_span(text) {
		return text.replace(/<span class=locale_tr[^>]*>|<\/span>/ig, "").replace(/<span class=\'locale_tr\'[^>]*>|<\/span>/ig, "").replace(/<span class="locale_tr"[^>]*>|<\/span>/ig, "");
	}

	// Display edit var dialog on "context menu" mouse click over editable var
	$(document).on("contextmenu", "span.locale_tr", function(e) {
		// Special case for the hyperlinks
		$(this).closest("a").on("click", function(){ return false; });

		$(this).attr("s_var", $(this).attr("s_var").replace("%20", " "));

		_my_save_var();

		// Save last edited element
		_last_tr_item = $(this);
		_last_tr_html = $(this).html();
		$(this).html(
			"<input type=\"text\" value=\"" + $(this).text().replace(/\'/, "&#39;") + "\" class=\"editable_tr\" s_var=\"" + $(this).attr("s_var").replace(/\'/, '&#39;') + "\" />" 
			+ " <span class=\"var_old_value\">" + $(this).attr("s_var") + "</span>"
		);

		// Set current field focused and selected
		$(this).find("input.editable_tr").focus().select();

		return false;
	});
END
	))),
	'require' => array(
		'asset' => 'jquery',
	),
);

};