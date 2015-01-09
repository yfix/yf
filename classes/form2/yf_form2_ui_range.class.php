<?php

class yf_form2_ui_range {

	/**
	*/
	function ui_range($name, $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $form) {
// TODO: upgrade look and feel and connect $field__and for filter
			$form->_prepare_inline_error($extra);

			asset('jquery-ui');
			jquery('
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
			');
			$body = '
				<div class="span10">
					<div id="slider-range"></div>
				</div>
				<input type="hidden" id="'.$name.'" name=".$name." value="'.$extra['value_min'].'" />
				<input type="hidden" id="'.$name.'__and" name=".$name." value="'.$extra['value_max'].'" />

<!--			<input type="text" id="amount" style="font-weight: bold;" class="input-small" /> -->
			';
			return $form->_row_html($body, $extra, $r);
		};
		if ($form->_chained_mode) {
			$form->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $form;
		}
		return $func($extra, $replace, $form);
	}
}