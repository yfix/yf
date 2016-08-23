<?php

return [
	'versions' => [
		'master' => [
			'css' => ['
.left_area .bs-docs-sidenav, .right_area .bs-docs-sidenav { position:fixed; height: calc(100% - 80px); overflow-y: auto; }
.dl-horizontal dt { width:50%; margin-right:10px; }
ul#slide_menu .dropdown-divider { font-weight:bold; border-bottom:1px solid; }
			'],
			'jquery' => [
#				'$("select").not(".portlet select").not(".no-chosen").not(".no-select2").select2();',
//				'$("select").not(".portlet select").not(".no-chosen").chosen();',
			],
		],
	],
	'require' => [
		'asset' => [
			'yf_bootstrap_fixes',
#			'jq-select2',
#			'icheck',
#			'chosen',
		],
	],
];
