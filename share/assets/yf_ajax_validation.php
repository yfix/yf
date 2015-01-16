<?php

return function() {

return array(
	'versions' => array(
		'master' => array(
			'jquery' => 
	// http://test2.dev/dynamic/ajax_validate/?func=is_unique&param=user.login&data=test
'
	var yf_ajax_link_validate = "'.url_user('/dynamic/ajax_validate').'";
	var yf_ajax_validate_cache = { };
	var yf_css_class_error = "error has-error";
	var yf_css_icon_error = "icon icon-large icon-ban-circle fa fa-2x fa-times-circle text-error text-danger";
	var yf_css_icon_success = "icon icon-large icon-ok-circle fa fa-2x fa-check-circle text-success";
	var yf_html_help_block = "<span class=\"help-block pull-left\"></span>";
	var yf_title_error = "'.t('not good').'";
	var yf_title_success = "OK";

	$("input[data-ajax-validate]", "form").on("blur", function(i){
		var _this = $(this);
		var _val = _this.val();
		if (!_val.length) {
			return false;
		}
		if (typeof _this[0].validity == "object") {
			yf_ajax_validation_icon_clear(_this)
			if (!_this[0].validity.valid) {
				return false;
			}
		}
		var validate_rules_str = _this.attr("data-ajax-validate");
		var result = "";
		var cache_key = validate_rules_str + "-" + _val;
		if (typeof yf_ajax_validate_cache[cache_key] != "undefined") {
			result = yf_ajax_validate_cache[cache_key];
			yf_ajax_validation_icon_update(_this, result);
		} else {
			var validate_rules_arr = { }
			// is_unique=user.login&other_rule=other_param
			$.each(validate_rules_str.split("&"), function(i1, v1) {
				var _splitted = v1.split("=");
				validate_rules_arr[_splitted[0]] = _splitted[1];
			})
			var rules_to_post = { }
			$.each(validate_rules_arr, function(rule_name, rule_param) {
				rules_to_post[rule_name] = {
					"func" : rule_name,
					"param": rule_param,
					"data" : _val
				}
			})
			$.post( yf_ajax_link_validate, {"rules" : rules_to_post}, function(data) {
				result = data
				yf_ajax_validate_cache[cache_key] = result
				yf_ajax_validation_icon_update(_this, result);
			})
		}
		return result;
	})

	function yf_ajax_validation_icon_clear(_this) {
		_this.closest(".input-group").next("i.ajax-validation-status").remove()
	}

	function yf_ajax_validation_icon_update(_this, result) {
		var textfield = _this.get(0);
		var title = yf_title_success;
		var input_group = _this.closest(".input-group");
		var control_group = _this.closest(".control-group");
		var controls = _this.closest(".controls");
		var help_block = controls.find(".help-block");
		if (!help_block.length) {
			controls.append(yf_html_help_block);
			help_block = controls.find(".help-block");
		}
		var is_error = (!result || !result["ok"]);
		if (is_error) {
			title = result["error_msg"] || yf_title_error;
			controls.addClass(yf_css_class_error);
			control_group.addClass(yf_css_class_error);
		} else {
			controls.removeClass(yf_css_class_error);
			control_group.removeClass(yf_css_class_error);
		}

//		if (is_error) {
//			textfield.setCustomValidity(title);
//		} else {
//			textfield.setCustomValidity("");
//		}

		help_block.html(is_error ? title : "")

		yf_ajax_validation_icon_clear(_this)
		var icon_css_class = is_error ? yf_css_icon_error : yf_css_icon_success;
		input_group.after("&nbsp;<i class=\"ajax-validation-status " + icon_css_class + "\" title=\"" + title + "\"></i>");
	}

	// HTML5 custom validation messages
	$("input, textarea", "form, select").not("[type=submit]").not("[type=image]").not("[data-ajax-validate]").on("change keyup invalid valid", function() {
		var _this = $(this)
		var textfield = _this.get(0);
		var control_group = _this.closest(".control-group");
		var controls = _this.closest(".controls");
		var help_block = controls.find(".help-block");
		// setCustomValidity not only sets the message, but also marks the field as invalid. 
		// In order to see whether the field really is invalid, we have to remove the message first
		textfield.setCustomValidity("");
		if (!textfield.validity.valid) {
			textfield.setCustomValidity(help_block.html());
			controls.addClass(yf_css_class_error);
			control_group.addClass(yf_css_class_error);
			help_block.show()
		} else {
			controls.removeClass(yf_css_class_error);
			control_group.removeClass(yf_css_class_error);
			help_block.hide()
		}
	})
',
/*
	$("form").each(function() {
		var form = this;

		// Suppress the default bubbles
		form.addEventListener("invalid", function(event) {
			event.preventDefault();
		}, true);

		// Support Safari, iOS Safari, and the Android browser—each of which do not prevent form submissions by default
		$(form).on("submit", function(event) {
			if (!this.checkValidity()) {
				event.preventDefault();
			}
		});

		$("input, select, textarea", form)
			// Destroy the tooltip on blur if the field contains valid data
			.on("change keyup", function() {
//			.on("change keyup invalid valid", function() {
				yf_on_validate_change($(this));
			})
			.on("blur", function() {
				var field = $(this);
				if (this.validity.valid) {
					field.popover("destroy");
				} else {
					field.popover("hide");
				}
			})
			.on("focus", function() {
				$(this).popover("show");
			})
		;

		$("button:not([type=button]), input[type=submit]", form).on("click", function(event) {
			// Destroy any tooltips from previous runs
			$("input, select, textarea", form).each(function() {
				$(this).popover("destroy");
			});
			// Add a tooltip to each invalid field
			var invalidFields = $(":invalid", form).each(function() {
				var field = $(this).popover({
					trigger: "hover",
					placement: "bottom",
					html: true,
					content: function() {
						return "<i class=\"" + yf_css_icon_error + "\"></i>&nbsp;" + field[ 0 ].validationMessage;
					}
				});
			});
			// If there are errors, give focus to the first invalid field
			invalidFields.first().trigger("focus").eq(0).focus();
		});
	});
*/
		),
	),
);

};