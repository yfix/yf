<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array('
.left_area .bs-docs-sidenav, .right_area .bs-docs-sidenav { position:fixed; }
.dl-horizontal dt { width:50%; margin-right:10px; }
ul#slide_menu .dropdown-divider { font-weight:bold; border-bottom:1px solid; }
			'),
			'jquery' => array(
#				'$("select").not(".portlet select").not(".no-chosen").not(".no-select2").select2();',
//				'$("select").not(".portlet select").not(".no-chosen").chosen();',
			),
		),
	),
	'require' => array(
		'asset' => array(
			'yf_bootstrap_fixes',
#			'jq-select2',
#			'icheck',
#			'chosen',
		),
	),
);
