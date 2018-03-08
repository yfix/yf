<?php

class yf_form2_ui_range {

	/**
	*/
	function ui_range($name, $desc = '', $extra = [], $replace = [], $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['min'] = $extra['min'] ?: 1;
		$extra['max'] = $extra['max'] ?: 500;
		$extra['max'] = $extra['max'] ?: 500;
		$extra['step'] = $extra['step'] ?: 1;
		$extra['value_min'] = $extra['value_min'] ?: $extra['min'];
		$extra['value_max'] = $extra['value_max'] ?: $extra['max'];
		$extra['value'] = $extra['value_min'] ?: '75';
		$extra['orientation'] = $extra['orientation'] ?: 'horizontal'; //horizontal", "vertical".
		$extra['range'] = isset($extra['range']) ? $extra['range'] : 'true'; // true , false, min,  max
		$extra['animate'] = isset($extra['animate']) ? $extra['animate'] : 'false'; // boolean, string("slow", "normal", "fast), int 
		$extra['disabled'] = isset($extra['disabled']) ? $extra['disabled'] : 'false'; // boolean
		$extra['hidden_inputs'] = isset($extra['hidden_inputs']) ? $extra['hidden_inputs'] : true;
		$extra['create_inputs'] = isset($extra['create_inputs']) ? $extra['create_inputs']: true;

		// range can be  true , false, min,  max	
		if( $extra['range'] === true ){
			$extra['range'] = 'true';
		}elseif( $extra['range'] === false ){
			$extra['range'] = 'false';
		}elseif (in_array($extra['range'], ["min", "max"]) ){
			$extra['range'] = '"'.$extra['range'].'"';
		} 
 
		if($extra['disabled'] === false){
			$extra['disabled'] = 'false';
		}elseif($extra['disabled'] === true){
			$extra['disabled'] = 'true';
		}
// boolean, string("slow", "normal", "fast), int 	
		if($extra['animate'] === false){
			$extra['animate'] = 'false';
		}elseif($extra['animate'] === true){
			$extra['animate'] = 'true';
		}elseif(in_array($extra['animate'], ["slow", "normal", "fast"]) ){
			$extra['animate'] = '"'.$extra['animate'].'"';
		}
		
		if($extra['range']  == 'true'){
			$extra['values'] = 'values: [ '.$extra['value_min'].', '.$extra['value_max'].' ], ';
			$extra['feedback'] = '
				$("input#'.$extra['name'].'").on("change", function() {
			//	$("input#'.$extra['name'].'").on("keyup change", function() {
					var value1 = $("input#'.$extra['name'].'").val();
					var value2 = $("input#'.$extra['name'].'_and").val();
				
					if (value1 < '. $extra['min'].') {
						value1 = '.$extra['min'].';
						$("input#'.$extra['name'].'").val(value1)
					}
				    if(parseInt(value1) > parseInt(value2)){
						value1 = value2;
						$("input#'.$extra['name'].'").val(value1);
					}
					$("#slider-'.$extra['name'].'").slider("values",0,value1);	
				});
	
		
			//	$("input#'.$extra['name'].'_and").on("keyup change", function() {
				$("input#'.$extra['name'].'_and").on("change", function() {
					var value1 = $("input#'.$extra['name'].'").val();
					var value2 = $("input#'.$extra['name'].'_and").val();
				
					if (value2 > '. $extra['max'].') {
						value2 = '. $extra['max'].'; 
						$("input#'.$extra['name'].'_and").val(value2)
					}
					if(parseInt(value1) > parseInt(value2)){
						value2 = value1;
						$("input#'.$extra['name'].'_and").val(value2);
					}
					$("#slider-'.$extra['name'].'").slider("values",1,value2);
				});
			';

						
		}else{
			$extra['values'] = 'value: '.$extra['value_min'].' , ';
			$extra['feedback'] = '
				$("input#'.$extra['name'].'").on("change", function() {
			//	$("input#'.$extra['name'].'").on("keyup change", function() {
					var value = $("input#'.$extra['name'].'").val();
					if (value < '. $extra['min'].') {
						value = '.$extra['min'].';
						$("input#'.$extra['name'].'").val(value)
					}
					$("#slider-'.$extra['name'].'").slider("value",value);
				});
			';


			//$extra['values'] = 'value: [ '.$extra['value_min'].' ], ';
	//		$values = 'value: '.$extra['value_min'].' ],
		}
		//events:
		$extra['start_event'] = $extra['start_event']    ? 'start: function( event, ui ) {'.$extra['start_event'].'	},': '';
		$extra['create_event'] = $extra['create_event']  ? 'create: function( event, ui ) {'.$extra['create_event'].'},': '';
		$extra['slide_event'] = $extra['slide_event']    ? 'slide: function( event, ui ) {'.$extra['slide_event'].'},': '';
		$extra['change_event'] = $extra['change_event']  ? 'chsnge: function( event, ui ) {'.$extra['change_event'].'},': '';
		$extra['stop_event'] = $extra['stop_event']      ? 'stop: function( event, ui ) {'.$extra['stop_event'].'},': '';
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $form) {
// TODO: upgrade look and feel and connect $field__and for filter
			$form->_prepare_inline_error($extra);
			asset('jquery-ui');
			jquery('
				$( "#slider-'.$extra['name'].'" ).slider({
					range: '.$extra['range'].', 
					min: '.$extra['min'].',
					max: '.$extra['max'].',
					disabled: '.$extra['disabled'].',
					orientation: "'.$extra['orientation'].'",
					animate: '.$extra['animate'].',
					'
					. $extra['values'].
					'
					' . $extra['start_event'].	'
					' . $extra['create_event'].	'
					' . $extra['slide_event'].	'
					' . $extra['change_event'].	'
					' . $extra['stop_event'].	'
				});

				'.$extra['feedback'].'
			//	$( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
			//		" - $" + $( "#slider-range" ).slider( "values", 1 ) );
			');
			$inputs = '';
			if($extra['create_inputs']){
				$hide =  $extra['hidden_inputs'] ? ' type="hidden"' : 'type="text"';
				$inputs ='<input '.$hide.' id="'.$extra['name'].'" name="'.$extra['name'].'" value="'.$extra['value_min'].'" />';
				if ($extra['range']){
					$inputs .= '<input '.$hide.' id="'.$extra['name'].'_and" name="'.$extra['name'].'_and" value="'.$extra['value_max'].'" />';
				}	
	

			}
			$body = '
				<div class="span10">
					<div class="ui-slider " id="slider-'.$extra['name'].'"></div>
				</div>
				'.$inputs.'
				';
			return $form->_row_html($body, $extra, $r);
		};
		if ($form->_chained_mode) {
			$form->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
			return $form;
		}
		return $func($extra, $replace, $form);
	}
}
