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
		$func = function($extra, $r, $_this) {
			$format = $format_php = $placeholder = array();
			$extra['no_time'] = $extra['no_time'] ?: 1;
			if ($extra['no_date']!=1) {
				$format[]      = 'DD.MM.YYYY';
				$format_php[]  = 'd.m.Y';
				$placeholder[] = 'ДД.ММ.ГГГГ';
			}
			if ($extra['no_time']!=1) {
				$format[]      = 'HH:mm';
				$format_php[]  = 'H:m';
				$placeholder[] = 'ЧЧ:ММ';
			}
			$_format      = implode(' ',$format);
			$_format_php  = implode(' ',$format_php);
			$_placeholder = implode(' ',$placeholder);
			$extra['placeholder'] = $extra['placeholder'] ?: $_placeholder;
			// Compatibility with filter
			if (!strlen($extra['value'])) {
				if (isset($extra['selected'])) {
					$extra['value'] = $extra['selected'];
				} elseif (isset($_this->_params['selected'])) {
					$extra['value'] = $_this->_params['selected'][$extra['name']];
				}
			}else{
				$extra['value'] = date($_format_php, $extra['value']);
			}

			_class('core_js')->add('//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/js/bootstrap.min.js', true);
			_class('core_js')->add('//cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment-with-langs.min.js', true);
			_class('core_js')->add('//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.0.0/js/bootstrap-datetimepicker.min.js', true);
			_class('core_css')->add('//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/3.0.0/css/bootstrap-datetimepicker.min.css', true);
			$body = "
<div id=\"{$extra['name']}\" data-date-format=\"$_format\" class=\"input-append datetimepicker\">
    <input name=\"{$extra['name']}\" value=\"{$extra['value']}\" type=\"text\" class=\"input-medium\" placeholder=\"{$extra['placeholder']}\"></input>
    <span class=\"add-on\">
		<i class=\"fa fa-calendar\"></i>
    </span>
</div>
";
			_class('core_js')->add("<script type=\"text/javascript\">
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
</script>", false);
			return $_this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}
}
