<?php

class test_main_get_data {
	function show() {
		conf('data_handlers::testme', function($params) {
			return print_r($params, 1);
		});
		return main()->get_data('testme', 0, array('test' => 'me'));
	}
}
