<?php

class yf_form2_datetime {

	/**
	* Datetimepicker, src: http://tarruda.github.io/bootstrap-datetimepicker/
	* params :  no_date // no date picker
	*			no_time // no time picker
	*			min_date // min available date
	*/
	function datetime_select($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'date');
		$extra['desc'] = $form->_prepare_desc($extra, $desc);

		$func = function($extra, $r, $form) {
			$form->_prepare_inline_error($extra);
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
			$_format_js      = implode(' ',$format_js);
			$_format_php  = implode(' ',$format_php);
			$_placeholder = implode(' ',$placeholder);
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
			$extra = $form->_input_assign_params_from_validate($extra);
			$extra['type'] = 'text';
			$extra['class'] = 'input-medium form-control';
			$attrs_names = array('name','type','id','class','style','placeholder','value','data','size','maxlength','pattern','disabled','required','autocomplete','accept','target','autofocus','title','min','max','step');

			$body = '
<div id="'.$extra['name'].'" data-date-format="'.$_format_js.'" class="input-append datetimepicker">
<div class="input-group">
    <input'._attrs($extra, $attrs_names).'></input>
    <span class="add-on input-group-addon">
		<i class="icon icon-calendar fa fa-calendar"></i>
    </span>
</div>
</div>
';
			asset('bootstrap-datetimepicker');
			jquery('
				$("#'.$extra['name'].'").datetimepicker({
					language: "ru"
					, icons: {
						time: "icon icon-time fa fa-clock-o",
						date: "icon icon-calendar fa fa-calendar",
						up:   "icon icon-arrow-up fa fa-arrow-up",
						down: "icon icon-arrow-down fa fa-arrow-down"
					}
					'.($extra['no_time'] == 1 ? ', pickTime: false' : '')
					. ($extra['no_date'] == 1 ? ', pickDate: false' : '')
			        . ($extra['min_date']? ', minDate: \''.date('d/m/Y', $extra['min_date']).'\'' : '')
			        . ($extra['max_date']? ', maxDate: \''.date('d/m/Y', $extra['max_date']).'\'' : '')
					.'
				});
			');
			return $form->_row_html($body, $extra, $r);
		};
		if ($form->_chained_mode) {
			$form->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $form;
		}
		return $func($extra, $replace, $form);
	}
}
