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
		
		require_js("http://maps.google.com/maps/api/js?sensor=false");
		
		$extra['name'] = $extra['name'] ?: ($name ?: 'map');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {
			// Compatibility with filter
			
			$replace = array(
				'name' => $extra['name'],
				'value' => $r[$extra['name']],
			);
			$body = tpl()->parse('form2/google_maps',$replace);			
			return $_this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}	
}