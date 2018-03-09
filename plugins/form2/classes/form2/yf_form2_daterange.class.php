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
		$extra['placeholder'] = $extra['placeholder'] ?: $_placeholder;

		if ($extra['min_date'] && strlen($extra['min_date']) == 10) {
			$time = time();
			$extra += [
				'min_date'		=> date('Y-m-d', $extra['min_date'] ?: ($time - 86400 * 30)),
				'max_date'		=> date('Y-m-d', $time + 86400),
				'autocomplete'	=> 'off',
			];
		}
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
			format: "'.($extra['format'] ?: 'YYYY-MM-DD').'",
			minDate: "'.$extra['min_date'].'",
			maxDate: "'.$extra['max_date'].'",
			startDate: "'.($extra['start_date'] ?: $extra['min_date']).'",
			endDate: "'.($extra['end_date'] ?: $extra['max_date']).'",
			buttonClasses: "btn btn-xs",
			ranges: {
			   "'.t('Today').'": [moment(), moment()],
			   "'.t('Yesterday').'": [moment().subtract(1, "days"), moment().subtract(1, "days")],
			   "'.t('Last 7 Days').'": [moment().subtract(6, "days"), moment()],
			   "'.t('Last 30 Days').'": [moment().subtract(29, "days"), moment()],
			   "'.t('Last 90 Days').'": [moment().subtract(89, "days"), moment()],
			   "'.t('This Month').'": [moment().startOf("month"), moment().endOf("month")],
			   "'.t('Last Month').'": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
			}
		});');
		if (MAIN_TYPE_ADMIN) {
			css('.daterangepicker .ranges li { padding: 0px 12px; margin-bottom: 1px; }');
		}

		$extra['type'] = 'text';
		$extra['class_add'] = 'input-medium'; // daterangepicker';
		$extra['prepend'] = isset($extra['prepend']) ? $extra['prepend'] : '<i class="'.$form->CLASS_ICON_CALENDAR.'"></i>';
		return $form->input($extra['name'], $extra['desc'], $extra, $replace);
	}
}
