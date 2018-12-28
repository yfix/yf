<?php

return function () {
    return [
    'versions' => [
        'master' => [
            'js' => ['
				var _t_are_you_sure = "' . t('Are you sure') . '?";
				var _t_modal_title = "' . t('Modal edit') . '";
				var _btn_active = \'<button class="btn btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> ' . t('Active') . '</button>\';
				var _btn_inactive = \'<button class="btn btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> ' . t('Disabled') . '</button>\';
				var _btn_yes = \'<button class="btn btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> ' . t('Yes') . '</button>\';
				var _btn_no = \'<button class="btn btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> ' . t('No') . '</button>\';
				var _btn_active_short_class = \'btn-success\';
				var _btn_inactive_short_class = \'btn-warning\';
				var _btn_active_short_icon = \'fa fa-check\';
				var _btn_inactive_short_icon = \'fa fa-ban\';
				var _btn_active_short_title = \'' . t('Active') . '\';
				var _btn_inactive_short_title = \'' . t('Disabled') . '\';
			',
<<<'END'
$(function(){
	window.yf_show_bs_modal = function (extra) {
		if (typeof $.fn.modal !== 'function') {
			console.error('modal bootstrap component not loaded');
			return '';
		}
		if (typeof extra !== 'object') {
			extra = { };
		}
		var undef // shortcut for comparing with 'undefined'
		, modal_class = 'modal fade' + (extra['class_add'] !== undef ? ' ' + extra['class_add'] : '')
		, modal_id = extra['id'] !== undef ? extra['id'] : 'edit_link_modal'
		, modal_data = extra['data'] !== undef ? extra['data'] : ''
		, modal_footer = extra['footer'] !== undef ? extra['footer'] : ''
		, modal_title = extra['title'] !== undef ? extra['title'] : _t_modal_title
		, modal_html = 
			'<div class="' + modal_class + '" id="' + modal_id + '">' 
				+ '<div class="modal-dialog">' 
					+ '<div class="modal-content">' 
						+ '<div class="modal-header">' 
							+ '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' 
							+ '<h4 class="modal-title">' + modal_title + '</h4>' 
						+ '</div>'
						+ (modal_data.length ? '<div class="modal-body">' + modal_data + '</div>' : '')
						+ (modal_footer.length ? '<div class="modal-footer">' + modal_footer + '</div>' : '')
					+ '</div>'
				+ '</div>'
			+ '</div>'
		, modal_opts = extra['opts'] !== undef ? extra['opts'] : {
			'keyboard'	: true,
			'backdrop'	: true,
			'show'		: true,
		}
		return $(modal_html).modal(modal_opts);
	}

	// Change activity status of different elements without page refresh
	$(document).on("click", ".change_active", function(){
		var _obj = this;
		$.post(_obj.href, {ajax_mode: 1}, function(data){
			var a = $(_obj).closest("a")
			var ok = (data == "1")
			if (a.hasClass("active_short")) {
				a.toggleClass(_btn_inactive_short_class).toggleClass(_btn_active_short_class);
				a.attr("title", ok ? _btn_active_short_title : _btn_inactive_short_title);
				a.find("i.fa").toggleClass(_btn_inactive_short_icon).toggleClass(_btn_active_short_icon);
			} else {
				_obj.innerHTML = (data == "1") ? _btn_active : _btn_inactive;
			}
		});
		return false; // Do not allow new page
	});

	// Change activity status of different elements without page refresh
	$(document).on("click", ".change_yes_no", function(){
		var _obj = this;
		$.post(_obj.href, {ajax_mode: 1}, function(data){
			_obj.innerHTML = (data == "1") ? _btn_yes : _btn_no;
		});
		return false; // Do not allow new page
	});

	// Delete record and delete interface row related to that record
	$(document).on("click", ".ajax_delete", function(){
		if (!confirm( _t_are_you_sure )) {
			return false;
		}
		var _obj = $(this);
		$.post(_obj.attr("href") || _obj.attr("yf:href"), {ajax_mode: 1}, function() {
			_obj.closest("tr").remove();
		});
		return false; // Do not allow new page
	});

	// Clone current TR, containing clicked element
	$(document).on("click", ".ajax_clone", function(){
//		var _obj = $(this);
//		var _row = _obj.closest("tr")[0];
//		$.post(_obj.attr("href") || _obj.attr("yf:href"), {ajax_mode: 1}, function() {
//			$(_row).clone().insertAfter(_row);
//		});
//		return false; // Do not allow new page
	});

	// Toggle checkboxes inside form, ruled by one with special class, limiting to only self form for checkbox, not touch outside
	$("form input[type=checkbox].yf_toggle_all_checkboxes").click(function(){
		$("input[type=checkbox]", $(this).parents("form")).not(".yf_toggle_all_checkboxes").prop("checked", $(this).is(":checked"))
	});
	$(document).on("hide.bs.modal","#edit_link_modal",  function (e) {
		$("#edit_link_modal").remove();
	})
	// Any pressed number or letter [0-9a-z] will case avoiding ajax action (edit)
	$(document).bind("keydown", function(e){ if (e.keyCode >= 48 && e.keyCode <= 90) { document.skip_ajax_pressed = true; }	})
	$(document).bind("keyup", function(e){ if (e.keyCode >= 48 && e.keyCode <= 90) { document.skip_ajax_pressed = false; } })

	// Modal edit/add form via AJAX
	$(document).on("click", ".ajax_edit, .ajax_add", function(){
		var _obj = $(this);
		// Avoid AJAX edit form
		if (document.skip_ajax_pressed || _obj.hasClass("no_ajax")) {
			return true;
		}
		$.get(_obj.attr("href"), function(data) {
			yf_show_bs_modal({
				'id'	: 'edit_link_modal',
				'data'	: data,
				'title'	: _t_modal_title,
			})
		}).success(function() {
			$("#edit_link_modal input:text:visible:first").focus();
		});
		return false; // Do not allow new page
	});
	$(document).on("submit", "#edit_link_modal form", function(e){
		e.preventDefault();
		var _form = $(this);
// TODO: fix bug when images are not uploaded from ajax form, due serialize() not supports this, maybe use jquery.form plugin?
		$.post(_form.attr("action"), _form.serialize(), function(data){
			$("#edit_link_modal").modal("hide").remove();
			window.location.reload();
		})
		return false;
	});

	$(document).on("click", ".insert_selected_word", function(){
		var _sel_box = $(this).prev("select");
		var _input = $(_sel_box).prev("input[type=text]");
		_input.val($("option:selected", _sel_box).val())
	})

	// inline hook (Ace editor connection (http://ace.c9.io/) is good example)
	$("form[data-onsubmit]").on("submit", function() {
		try { eval( $(this).data("onsubmit") ); } catch (_e) { console.log(e) }
		return true;
	})
	// inline hook
	$("[data-onclick]").on("click", function() {
		try { eval( $(this).data("onclick") ); } catch (_e) { console.log(e) }
		return true;
	})

	try {
		// Affix used in settings TOC, idea got from Bootstrap docs
		$(".bs-docs-sidenav").affix();
	} catch(e) {
		console.error("affix init failed for .bs-docs-sidenav", e);
	}
});
END
        ], ],
    ],
    'add' => [
        'asset' => [
            'yf_popover',
            'yf_js_shift_selectable',
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
    'config' => [
        'no_cache' => true,
        'main_type' => 'admin',
    ],
];
};
