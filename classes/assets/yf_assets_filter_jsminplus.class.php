<?php

class yf_assets_filter_jsminplus {

	/**
	*/
	public function apply($in, $params = array()) {
		require_php_lib('jsminplus');
		if (!class_exists('\JSMinPlus')) {
			throw new Exception('Assets: class \JSMinPlus not found');
			return $in;
		}
		return \JSMinPlus::minify($in);
	}
}
