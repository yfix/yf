<?php

class yf_db_admin {

	private $tree_params = array(
		'opened_levels'	=> 0,
		'draggable'		=> false,
		'no_expand'		=> 1,
		'form_class'	=> 'span4 col-xs-4',
	);

	function show() {
		return $this->list_databases();
	}

	function list_databases() {
		foreach ((array)db()->utils()->list_databases() as $name) {
			$data[$i++] = array(
				'name'	=> $name,
				'link'	=> './?object='.$_GET['object'].'&action=show_database&id='.$name,
			);
		}
		return _class('html')->tree($data, $this->tree_params);
	}
}