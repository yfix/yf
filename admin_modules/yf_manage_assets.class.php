<?php

class yf_manage_assets {

	/**
	*/
	function show() {
		return $this->search_used();
#		return a('/@object/purge_cache', 'Purge cache');
	}

	/**
	*/
	function search_used() {
		$in_php = _class('dir')->grep('~[\s]asset\([^\(\)]+\)~ims', APP_PATH, '*.php');
#		$ts = microtime(true);
#		$in_php = _class('dir')->search(APP_PATH, '');
#		$in_php = _class('dir')->search(APP_PATH, '');
		return '<pre>'.print_r($in_php, 1).'</pre>';
	}
}
