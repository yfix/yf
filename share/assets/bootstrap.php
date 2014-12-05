<?php

// Virtual package, depends on globally selected bootstap version
#$inherit_name = 'bootstrap'. (conf('css_framework') === 'bs3' ? '3' : '2');
return array(
	'inherit' => array(
#		'js' => $inherit_name,
#		'css' => $inherit_name,
		'js' => 'bootstrap-theme',
		'css' => 'bootstrap-theme',
	),
);
