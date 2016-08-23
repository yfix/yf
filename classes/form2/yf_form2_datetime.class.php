<?php

class yf_form2_datetime {

	/**
	* Datetimepicker, src: http://tarruda.github.io/bootstrap-datetimepicker/
	* params :  no_date // no date picker
	*			no_time // no time picker
	*			min_date // min available date
	*/
	function datetime_select($name = '', $desc = '', $extra = [], $replace = [], $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = [];
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'date');
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$extra['limit_date_format'] = $extra['limit_date_format'] ? $extra['limit_date_format'] : 'm/d/Y H:i';

		$format = $format_php = $placeholder = [];
		$extra['no_time'] = $extra['with_time'] ? !$extra['with_time'] : $extra['no_time'];
		$extra['no_time'] = isset( $extra['no_time'] ) ? $extra['no_time'] : 1;
		if ($extra['no_date'] != 1) {
			$format_js[]   = !empty($extra['date_format_js']) ? $extra['date_format_js'] : 'DD.MM.YYYY';
			$format_php[]  = !empty($extra['date_format_php']) ? $extra['date_format_php'] : 'd.m.Y';
			$placeholder[] = !empty($extra['date_placeholder']) ? $extra['date_placeholder'] : 'ДД.ММ.ГГГГ';
		}
		if ($extra['no_time'] != 1) {
			$format_js[]   = !empty($extra['time_format_js']) ? $extra['time_format_js'] : 'HH:mm';
			$format_php[]  = !empty($extra['time_format_php']) ? $extra['time_format_php'] : 'H:i';
			$placeholder[] = !empty($extra['time_placeholder']) ? $extra['time_placeholder'] : 'ЧЧ:ММ';
		}
		$_format_js   = implode(' ', $format_js);
		$_format_php  = implode(' ', $format_php);
		$_placeholder = implode(' ', $placeholder);
		$extra['placeholder'] = $extra['placeholder'] ?: $_placeholder;

		// Compatibility with filter
		if (!strlen($extra['value'])) {
			if (isset($extra['selected'])) {
				$value = $extra['selected'];
			} elseif (isset($form->_params['selected'])) {
				$value = $form->_params['selected'][$extra['name']];
			} elseif (isset($form->_replace[$extra['name']])) {
				$value = $form->_replace[$extra['name']];
			}
			$extra['value'] = empty( $value ) || $value == '0000-00-00 00:00:00' ? null : strtotime( $value );
		}
		$extra['value'] = empty( $extra['value'] ) ? '' : date( $_format_php, $extra['value'] );

		$debug_picker = isset($extra['debug_picker']) ? $extra['debug_picker'] : (! MAIN_TYPE_ADMIN && (bool)@constant('DEBUG_MODE'));

		asset('bootstrap-datetimepicker');
		jquery('
			$("#'.$extra['name'].'").datetimepicker({
				locale: "'.conf('language').'"
				, icons: {
					time: "icon icon-time fa fa-clock-o",
					date: "icon icon-calendar fa fa-calendar",
					up:   "icon icon-arrow-up fa fa-arrow-up",
					down: "icon icon-arrow-down fa fa-arrow-down"
				}
				'
		        . ($extra['min_date'] ? ', minDate: \''.date($extra['limit_date_format'], $extra['min_date']).'\'' : '')
		        . ($extra['max_date'] ? ', maxDate: \''.date($extra['limit_date_format'], $extra['max_date']).'\'' : '')
				. ($extra['default_date'] ? ', defaultDate: \''.date($extra['limit_date_format'], $extra['default_date']).'\'' : '')
				. ($extra['side_by_side'] && $extra['no_time'] != 1 ? ', sideBySide: true' : '')
				. ($extra['stepping'] ? ', stepping: '.$extra['stepping'] : '')
				. ($extra['widgetPositioning'] ? ', widgetPositioning: '.$extra['widgetPositioning'] : '')
				. ($debug_picker ? ', debug: true' : '')
				.'
			});
		');
		$extra['data-date-format'] = $_format_js;

		$extra['type'] = 'text';
		$extra['class_add'] = 'input-medium datetimepicker';
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="'.$form->CLASS_ICON_CALENDAR.'"></i>';
		return $form->input($extra['name'], $extra['desc'], $extra, $replace);
	}
}
