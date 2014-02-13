<?php

class yf_form2_ui_range {

	/**
	*/
	function ui_range($name, $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
// TODO: upgrade look and feel and connect $field__and for filter
			$body = '
				<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
				<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
				<script>
				$(function() {
					$( "#slider-range" ).slider({
						range: true,
						min: 0,
						max: 500,
						values: [ 75, 300 ],
						slide: function( event, ui ) {
							$( "#'.$name.'" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
						}
					});
					$( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
						" - $" + $( "#slider-range" ).slider( "values", 1 ) );
				});
				</script>
				<div class="span10">
					<div id="slider-range"></div>
				</div>
				<input type="hidden" id="'.$name.'" name=".$name." value="'.$extra['value_min'].'" />
				<input type="hidden" id="'.$name.'__and" name=".$name." value="'.$extra['value_max'].'" />

<!--			<input type="text" id="amount" style="font-weight: bold;" class="input-small" /> -->
			';
			return $_this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}
}