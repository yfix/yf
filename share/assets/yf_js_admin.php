<?php

return array(
	'versions' => array(
		'master' => array(
			'jquery' => 
<<<END
	var _t_active = "{t(Active)}";
	var _t_inactive = "{t(Disabled)}";
	var _t_yes = "{t(Yes)}";
	var _t_no = "{t(No)}";
	var _t_are_you_sure = "{t(Are you sure)}?";
	var _btn_active = '<button class="btn btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> ' + _t_active + '</button>';
	var _btn_inactive = '<button class="btn btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> '+ _t_inactive + '</button>';
	var _btn_yes = '<button class="btn btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> ' + _t_yes + '</button>';
	var _btn_no = '<button class="btn btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> '+ _t_no + '</button>';

	try {
		$('select').not('.portlet select').not('.no-chosen').chosen();
	} catch (e) { console.log(e); }

	// Change activity status of different elements without page refresh
	$(document).on("click", ".change_active", function(){
		var _obj = this;
		$.post(_obj.href, {ajax_mode: 1}, function(data){
			_obj.innerHTML = (data == "1") ? _btn_active : _btn_inactive;
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
/*
		var _obj = $(this);
		var _row = _obj.closest('tr')[0];
		$.post(_obj.attr("href") || _obj.attr("yf:href"), {ajax_mode: 1}, function() {
			$(_row).clone().insertAfter(_row);
		});
		return false; // Do not allow new page
*/
	});

	// Toggle checkboxes inside form, ruled by one with special class, limiting to only self form for checkbox, not touch outside
	$("form input[type=checkbox].yf_toggle_all_checkboxes").click(function(){
		$("input[type=checkbox]", $(this).parents("form")).not(".yf_toggle_all_checkboxes").prop("checked", $(this).is(":checked"))
	});
	 $(document).on('hide.bs.modal','#edit_link_modal',  function (e) {
		$('#edit_link_modal').remove();
	})
	// Any pressed number or letter [0-9a-z] will case avoiding ajax action (edit)
	$(document).bind('keydown', function(e){ if (e.keyCode >= 48 && e.keyCode <= 90) { document.skip_ajax_pressed = true; }	})
	$(document).bind('keyup', function(e){ if (e.keyCode >= 48 && e.keyCode <= 90) { document.skip_ajax_pressed = false; } })

	// Modal edit/add form via AJAX
	$(document).on("click", ".ajax_edit, .ajax_add", function(){
		var _obj = $(this);
		// Avoid AJAX edit form
		if (document.skip_ajax_pressed || _obj.hasClass('no_ajax')) {
			return true;
		}
		$.get(_obj.attr("href"), function(data) {
			$('<div class="modal fade" id="edit_link_modal"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button></div>' + data + '</div></div></div>').modal({
				keyboard: true,
				backdrop: true,
				show: true
			});
		}).success(function() {
			$('#edit_link_modal input:text:visible:first').focus();
		});
		return false; // Do not allow new page
	});

	$(document).on("submit", "#edit_link_modal form", function(e){
		e.preventDefault();
		var _form = $(this);
// TODO: fix bug when images are not uploaded from ajax form, due serialize() not supports this, maybe use jquery.form plugin?
		$.post(_form.attr("action"), _form.serialize(), function(data){
			$('#edit_link_modal').modal('hide').remove();
			window.location.reload();
		})
		return false;
	});

	$(document).on("click", ".insert_selected_word", function(){
		var _sel_box = $(this).prev('select');
		var _input = $(_sel_box).prev('input[type=text]');
		_input.val($('option:selected', _sel_box).val())
	})

	$('.yf_tip').popover({
		'trigger' : 'hover',
		'delay'   : { 'show' : 0, 'hide' : 0 }
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

	// Affix used in settings TOC, idea got from Bootstrap docs
	$(".bs-docs-sidenav").affix();
END
		),
	),
);
