<?php

class yf_form2_daterange {

	/**
	* Daterange picker http://www.daterangepicker.com/#examples
	*/
	function daterange_select($name = '', $desc = '', $extra = [], $replace = [], $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = [];
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'date');
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
/*
		$extra['limit_date_format'] = $extra['limit_date_format'] ? $extra['limit_date_format'] : 'm/d/Y H:i';
		$format = $format_php = $placeholder = array();
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
*/
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

		asset('bootstrap-daterangepicker');
		jquery('$("input#'.$extra['name'].'").daterangepicker({
			format: "'.($extra['format'] ?: 'DD.MM.YYYY').'",
			minDate: "'.$extra['min_date'].'",
			maxDate: "'.$extra['max_date'].'",
			startDate: "'.($extra['start_date'] ?: $extra['min_date']).'",
			endDate: "'.($extra['end_date'] ?: $extra['max_date']).'",
			ranges: {
			   "'.t('Today').'": [moment(), moment()],
			   "'.t('Yesterday').'": [moment().subtract(1, "days"), moment().subtract(1, "days")],
			   "'.t('Last 7 Days').'": [moment().subtract(6, "days"), moment()],
			   "'.t('Last 30 Days').'": [moment().subtract(29, "days"), moment()],
			   "'.t('This Month').'": [moment().startOf("month"), moment().endOf("month")],
			   "'.t('Last Month').'": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
			}
		});');

		$extra['type'] = 'text';
		$extra['class_add'] = 'input-medium'; // daterangepicker';
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="'.$form->CLASS_ICON_CALENDAR.'"></i>';
		return $form->input($extra['name'], $extra['desc'], $extra, $replace);
	}
}
