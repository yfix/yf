<?php

return array(
	'versions' => array(
		'master' => array(
			'jquery' => 
"
	// http://test2.dev/dynamic/ajax_validate/?func=is_unique&param=user.login&data=test
	var yf_ajax_link_validate = '".url_user('/dynamic/ajax_validate')."';
	var yf_ajax_validate_cache = { };
	$('input[data-ajax-validate]', 'form').on('blur', function(i){
		var _this = $(this);
		var _val = _this.val();
		if (!_val.length) {
			return false;
		}
		if (typeof _this[0].validity == 'object') {
			yf_ajax_validation_icon_clear(_this)
			if (!_this[0].validity.valid) {
				return false;
			}
		}
		var validate_rules_str = _this.attr('data-ajax-validate');
		var result = '';
		var cache_key = validate_rules_str + '-' + _val;
		if (typeof yf_ajax_validate_cache[cache_key] != 'undefined') {
			result = yf_ajax_validate_cache[cache_key];
			yf_ajax_validation_icon_update(_this, result);
		} else {
			var validate_rules_arr = { }
			// is_unique=user.login&other_rule=other_param
			$.each(validate_rules_str.split('&'), function(i1, v1) {
				var _splitted = v1.split('=');
				validate_rules_arr[_splitted[0]] = _splitted[1];
			})
			var rules_to_post = { }
			$.each(validate_rules_arr, function(rule_name, rule_param) {
				rules_to_post[rule_name] = {
					'func' : rule_name,
					'param': rule_param,
					'data' : _val
				}
			})
			$.post( yf_ajax_link_validate, {'rules' : rules_to_post}, function(data) {
				result = data
				yf_ajax_validate_cache[cache_key] = result
				yf_ajax_validation_icon_update(_this, result);
			})
		}
		return result;
	})

	// HTML5 custom validation messages
	$('input[type=text]', 'form').not('[data-ajax-validate]').on('change invalid valid', function() {
		var _this = $(this)
		var textfield = _this.get(0);
		var control_group = _this.closest('.control-group');
		var controls = _this.closest('.controls');
		var help_block = controls.find('.help-block');
//		if (!help_block.length) {
//			controls.append('<span class=\"help-block pull-left\"></span>')
//			help_block = controls.find('.help-block');
//		}
		// setCustomValidity not only sets the message, but also marks the field as invalid. 
		// In order to see whether the field really is invalid, we have to remove the message first
		textfield.setCustomValidity('');
		if (!textfield.validity.valid) {
			textfield.setCustomValidity(help_block.html());
			controls.addClass('error has-error');
			control_group.addClass('error has-error');
			help_block.show()
		} else {
			controls.removeClass('error has-error');
			control_group.removeClass('error has-error');
			help_block.hide()
		}
	})

	function yf_ajax_validation_icon_clear(_this) {
		_this.closest('.input-group').next('i.ajax-validation-status').remove()
	}

	function yf_ajax_validation_icon_update(_this, result) {
		var icon = 'icon-ok-circle fa-check-circle';
		var color = 'green';
		var title = 'OK';
		var input_group = _this.closest('.input-group');
		var control_group = _this.closest('.control-group');
		var controls = _this.closest('.controls');
		var help_block = controls.find('.help-block');
		if (!help_block.length) {
			controls.append('<span class=\"help-block pull-left\"></span>')
			help_block = controls.find('.help-block');
		}
		if (!result || !result['ok']) {
			icon = 'icon-ban-circle fa-times-circle';
			color = 'red';
			title = result['error_msg'] || '".t('not good')."';
			controls.addClass('error has-error');
			control_group.addClass('error has-error');
		} else {
			controls.removeClass('error has-error');
			control_group.removeClass('error has-error');
		}
		yf_ajax_validation_icon_clear(_this)
		input_group.after('&nbsp;<i class=\"ajax-validation-status icon icon-large fa fa-2x ' + icon + '\" style=\"color:' + color + ';\" title=\"' + title + '\"></i>');
		help_block.html(!result || !result['ok'] ? title : '')
	}
"
		),
	),
);
