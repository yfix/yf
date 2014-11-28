<?php

return array(
	'versions' => array(
		'3.7.2' => array(
			'js' => '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js',
		),
	),
	'config' => array(
#		<!--[if lt IE 9]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js" class="yf_core"></script><![endif]-->
		'before_tag' => '<!--[if lt IE 9]>',
		'after_tag' => '<![endif]-->',
	),
);
