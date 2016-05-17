<?php

return function() {
	conf('data_handlers::testme', function($params) {
		return print_r($params, 1);
	});
	return main()->get_data('testme', 0, array('test' => 'me'));
};
