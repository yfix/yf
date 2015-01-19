<?php

return function() {
	return array(
		'require' => array(
			'asset' => conf('form_advanced_js_validation') ? 'yf_validation_jquery_fv' : 'yf_validation_simple',
		),
	);
};