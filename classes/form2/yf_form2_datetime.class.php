<?php

class yf_form2_datetime {

	/**
	* Datetimepicker, src: http://tarruda.github.io/bootstrap-datetimepicker/
	* params :  no_date // no date picker
	*			no_time // no time picker
	*/
	function datetime_select($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}

		$extra['name'] = $extra['name'] ?: ($name ?: 'date');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $__this) {
			$format = $format_php = $placeholder = array();
			$extra['no_time'] = $extra['with_time'] ? !$extra['with_time'] : $extra['no_time'];
			$extra['no_time'] = isset( $extra['no_time'] ) ? $extra['no_time'] : 1;
			if ($extra['no_date']!=1) {
				$format_js[]      = 'DD.MM.YYYY';
				$format_php[]  = 'd.m.Y';
				$placeholder[] = 'ДД.ММ.ГГГГ';
			}
			if ($extra['no_time']!=1) {
				$format_js[]      = 'HH:mm';
				$format_php[]  = 'H:i';
				$placeholder[] = 'ЧЧ:ММ';
			}
			$_format_js      = implode(' ',$format_js);
			$_format_php  = implode(' ',$format_php);
			$_placeholder = implode(' ',$placeholder);
			$extra['placeholder'] = $extra['placeholder'] ?: $_placeholder;
			// Compatibility with filter
			if (!strlen($extra['value'])) {
				if (isset($extra['selected'])) {
					$value = $extra['selected'];
				} elseif (isset($__this->_params['selected'])) {
					$value = $__this->_params['selected'][$extra['name']];
				} elseif (isset($__this->_replace[$extra['name']])) {
					$value = $__this->_replace[$extra['name']];
				}
				$extra['value'] = empty( $value ) || $value == '0000-00-00 00:00:00' ? null : strtotime( $value );
			}
			$extra['value'] = empty( $extra['value'] ) ? '' : date( $_format_php, $extra['value'] );
			// error
			$extra['errors'] = common()->_get_error_messages();
			$extra['inline_help'] = isset($extra['errors'][$extra['name']]) ? $extra['errors'][$extra['name']] : $extra['inline_help'];
			// js lib
			require_js('//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/js/bootstrap.min.js');
			require_js('//cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment-with-langs.min.js');
			require_js('//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.0.0/js/bootstrap-datetimepicker.min.js');
			require_css('//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.0.0/css/bootstrap-datetimepicker.min.css');
			$body = "
<style>
.bootstrap-datetimepicker-widget .picker-switch {
	width: 90%;
}
</style>
<div id=\"{$extra['name']}\" data-date-format=\"$_format_js\" class=\"input-append datetimepicker\">
<div class=\"input-group\">
    <input name=\"{$extra['name']}\" value=\"{$extra['value']}\" type=\"text\" class=\"input-medium form-control\" placeholder=\"{$extra['placeholder']}\"></input>
    <span class=\"add-on input-group-addon\">
		<i class=\"fa fa-calendar\"></i>
    </span>
</div>
</div>
";
			require_js("<script type=\"text/javascript\">
$(function() {
	$('#{$extra['name']}').datetimepicker({
		language: 'ru'
		, icons: {
			time: 'fa fa-clock-o',
			date: 'fa fa-calendar',
			up:   'fa fa-arrow-up',
			down: 'fa fa-arrow-down'
		}
		".($extra['no_time']==1 ? ", pickTime: false" : "")."".($extra['no_date']==1 ? ", pickDate: false" : "")."
	});
});
</script>");
			return $__this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}
}
