<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array('
.left_area .bs-docs-sidenav, .right_area .bs-docs-sidenav { position:fixed; }
.dl-horizontal dt { width: 50%; margin-right: 10px; }
ul#slide_menu .dropdown-divider { font-weight: bold; border-bottom: 1px solid; }
			'),
		),
	),
	'require' => array(
		'css' => 'yf_bootstrap_fixes',
	),
);
