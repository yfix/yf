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

	function yf_ajax_validation_icon_clear(_this) {
		_this.closest('.input-group').next('i.ajax-validation-status').remove()
	}

	function yf_ajax_validation_icon_update(_this, result) {
		var icon = 'icon-ok-circle fa-check-circle';
		var color = 'green';
		var title = 'OK';
		if (!result || !result['ok']) {
			icon = 'icon-ban-circle fa-times-circle';
			color = 'red';
			title = result['error_msg'] || '".t('not good')."';
		}
		yf_ajax_validation_icon_clear(_this)
		_this.closest('.input-group').after('&nbsp;<i class=\"ajax-validation-status icon icon-large fa-lg ' + icon + '\" style=\"color:' + color + ';\" title=\"' + title + '\"></i>');
		_this.closest('.controls').find('.help-block').html(!result || !result['ok'] ? title : '')
	}
"
		),
	),
);
