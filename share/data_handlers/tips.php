<?php

return function($params = []) {
	$data = [];
	foreach ((array)db()->from('tips')->get_all() as $a) {
		$data[$a['name']][$a['locale']] = $a;
	}
	return $data;
};
