<?php

class sample_cache {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = [];
		$url = url('/@object');
		$methods = get_class_methods(cache());
		$sample_methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$sample_methods as $name) {
			if (in_array($name, $methods)) {
				continue;
			}
			$methods[] = $name;
		}
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$items[] = [
				'name'	=> $name. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
				'link'	=> url('/@object/@action/'.$name), // '#head_'.$name,
			];
		}
		return _class('html')->navlist($items);
	}

	/**
	*/
	function show() {
		if ($_GET['id']) {
			return _class('docs')->_show_for($this);
		}
		!cache()->get('var1') && cache()->set('var1', 'value_core_cache');
		!cache_tmp()->get('var1') && cache_tmp()->set('var1', 'value_tmp');
		!cache_files()->get('var1') && cache_files()->set('var1', 'value_files');

		$cache_db = clone _class('cache');
		$cache_db->_init(['driver' => 'db']);
		!$cache_db->get('var1') && $cache_db->set('var1', 'value_db');

		return $this->_other_func().' | '.$cache_db->get('var1');
	}

	/**
	*/
	function _other_func() {
		return cache_tmp()->get('var1').' | '.cache()->get('var1'). ' | '.cache_files()->get('var1');
	}
}
