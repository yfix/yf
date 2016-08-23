<?php

class yf_form2_google_maps {

	function google_maps($name = '', $desc = '', $extra = [], $replace = [], $form) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = [];
		}
		
		asset('google-maps-api');

		$extra['name'] = $extra['name'] ?: ($name ?: 'map');
		$extra['markers_limit'] = $extra['markers_limit'] ?: 5;
		$extra['start_zoom'] = $extra['start_zoom'] ?: 5;		
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $form) {
			$form->_prepare_inline_error($extra);
			// Compatibility with filter			
			$start_lat = 49;
			$start_lng = 32;
			$replace = [
				'start_lat' => $start_lat,
				'start_lng' => $start_lng,
				'start_zoom' => $extra['start_zoom'],
				'markers_limit' => $extra['markers_limit'],
				'name' => $extra['name'],
				'value' => $r[$extra['name']],
			];
			if ($extra['disable_edit_mode']) {
				$body = tpl()->parse('form2/google_maps_view',$replace);
			} else {
				$body = tpl()->parse('form2/google_maps',$replace);
			}
			return $form->_row_html($body, $extra, $r);
		};
		if ($form->_chained_mode) {
			$form->_body[] = ['func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__];
			return $form;
		}
		return $func($extra, $replace, $form);
	}	
}