<?php

class yf_assets_filter_cssmin {

	/**
	*/
	public function apply($in, $params = array()) {
		if (!class_exists('\CssMin')) {
			throw new Exception('Assets: class \CssMin not found');
			return $in;
		}
		return \CssMin::minify($in);
	}
}
