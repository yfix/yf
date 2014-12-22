<?php

class yf_assets_filter_jsmin {

	/**
	*/
	public function apply($in, $params = array()) {
		if (!class_exists('\JSMin')) {
			throw new Exception('Assets: class \JSMin not found');
			return $in;
		}
		return \JSMin::minify($in);
	}
}
