<?php

class test_cache {
	function show() {
		!cache()->get('var1') && cache()->set('var1', 'value_core_cache');
		!cache_tmp()->get('var1') && cache_tmp()->set('var1', 'value_tmp');
		!cache_files()->get('var1') && cache_files()->set('var1', 'value_files');

		$cache_db = clone _class('cache');
		$cache_db->_init(array('driver' => 'db'));
		!$cache_db->get('var1') && $cache_db->set('var1', 'value_db');

		return $this->_other_func().' | '.$cache_db->get('var1');
	}
	function _other_func() {
		return cache_tmp()->get('var1').' | '.cache()->get('var1'). ' | '.cache_files()->get('var1');
	}
}
