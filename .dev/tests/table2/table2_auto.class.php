<?php

class table2_auto {
	function show() {
		$f = __DIR__.'/products_data.json';
		$data = json_decode(file_get_contents($f), true);
		return table($data)->auto();
	}
}
