<?php

class yf_form2_validate {

	/**
	*/
	function _input_assign_params_from_validate($extra = array(), $form) {
		$name = $extra['name'];
		$is_html_array = (false !== strpos($name, '['));
		if ($is_html_array) {
			$name = str_replace(array('[',']'), array('.',''), trim($name,']['));
		}
		$vr = &$form->_validate_rules_names[$name];
/*
// TODO: move this into _class('validate')
		if ($vr['min_length']) {
		} elseif ($vr['max_length']) {
		} elseif ($vr['exact_length']) {
		} elseif ($vr['alpha']) {
		} elseif ($vr['alpha_numeric']) {
		} elseif ($vr['alpha_numeric_spaces']) {
		} elseif ($vr['alpha_dash']) {
		} elseif ($vr['exact_length']) {
		} elseif ($vr['numeric']) {
		} elseif ($vr['integer']) {
		} elseif ($vr['decimal']) {
		} elseif ($vr['is_natural']) {
		} elseif ($vr['is_natural_no_zero']) {
		} elseif ($vr['valid_email']) {
			$extra['type'] = 'email';
		} elseif ($vr['valid_url']) {
			$extra['type'] = 'url';
		} elseif ($vr['valid_ip']) {
		} elseif ($vr['regex_match']) {
		}
		# $extra['title'] is used in html5 validation suggesting messages
*/
/*
		if ($vr['numeric']) {
			$extra['pattern'] = isset($extra['pattern']) ? $extra['pattern'] : '^[\-+]?[0-9]*\.?[0-9]+$';
			$extra['title'] = isset($extra['title']) ? $extra['title'] : t('Field must contain only numbers');
		}
*/
/*
		// http://stackoverflow.com/questions/10281962/is-it-minlength-in-html5
		if (isset($vr['min_length']) && strlen($vr['min_length']) && !isset($extra['pattern'])) {
			$extra['pattern'] = '.{'.$vr['min_length'].','.($vr['max_length'] ?: '').'}';
		}
		if ($vr['max_length'] && !isset($extra['maxlength'])) {
			$extra['maxlength'] = $vr['max_length'][1];
		}
*/
#		$extra['feedback_icon'] = 'icon icon-ok fa fa-check';
#		$extra['feedback_icon'] = 'icon icon-remove fa fa-close';

		// Useful for testing server-side validation
		if (conf('form_no_js_validation')) {
			return $extra;
		}
		if (isset($vr['required']) || isset($vr['captcha'])) {
			$extra['required'] = 1;
			$extra['class_add_form_group'] = trim($extra['class_add_form_group'].' '.$form->CLASS_REQUIRED);
		}
		foreach (array('ajax_is_unique','ajax_is_unique_without','ajax_exists') as $rule) {
			$_rule = str_replace('ajax_', '', $rule);
			if (isset($vr[$rule])) {
				$extra['data-ajax-validate'][$_rule] = $vr[$rule];
			}
		}
		foreach (array('ajax_is_unique','ajax_is_unique_without','ajax_exists') as $rule) {
			$_rule = str_replace('ajax_', '', $rule);
			if (isset($vr[$rule])) {
				$extra['data-ajax-validate'][$_rule] = $vr[$rule];
			}
		}

		if (conf('form_advanced_js_validation') && $extra['required']) {
			$extra['data-fv-notempty'] = 1;
			$extra['class_add_form_group'] = trim($extra['class_add_form_group'].' has-feedback fv-has-tooltip');
		}

		return $extra;
	}
}