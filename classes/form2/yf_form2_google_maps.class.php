<?php

class yf_form2_google_maps {

	function google_maps($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		
		js("http://maps.google.com/maps/api/js?sensor=false");
		$extra['name'] = $extra['name'] ?: ($name ?: 'map');
		$extra['markers_limit'] = $extra['markers_limit'] ?: 5;
		$extra['start_zoom'] = $extra['start_zoom'] ?: 5;		
		$extra['desc'] = $__this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			// Compatibility with filter			
			$start_lat = 49;
			$start_lng = 32;
			$replace = array(
				'start_lat' => $start_lat,
				'start_lng' => $start_lng,
				'start_zoom' => $extra['start_zoom'],
				'markers_limit' => $extra['markers_limit'],
				'name' => $extra['name'],
				'value' => $r[$extra['name']],
			);
			if ($extra['disable_edit_mode']) {
				$body = tpl()->parse('form2/google_maps_view',$replace);
			} else {
				$body = tpl()->parse('form2/google_maps',$replace);
			}
			return $_this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}	
}