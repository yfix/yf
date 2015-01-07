<?php

return array(
	'versions' => array(
		'1.0.2' => array(
			'js' => '//oss.maxcdn.com/icheck/1.0.2/icheck.min.js',
			'css' => array(
				'//oss.maxcdn.com/icheck/1.0.2/skins/square/red.min.css',
				// Set the radio/checkbox position properly, from: http://formvalidation.io/examples/icheck/
				'#icheckForm .radio label, #icheckForm .checkbox label { padding-left: 0; }'
			),
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);
