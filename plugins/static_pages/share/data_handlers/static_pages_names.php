<?php

return function() {
	$data = array();
	foreach ((array)db()->select('id, name')->from('static_pages')->where('active', '1')->get_2d() as $id => $name) {
		$name = _strtolower($name);
		if (strlen($name)) {
			$data[$name] = $name;
		}
	}	
	return $data;
};