<?php

return [
	'versions' => [
		'master' => [
			'css' => ['
.left_area { margin-left:20px; margin-right:20px; width:410px; float:left; word-wrap:break-word; }
@media (min-width:1650px) {
	.left_area { margin-left:1%; margin-right:1%; width:20%; max-width:300px; }
	.center_area { margin-left:25%; margin-right:1%; width:74%; }
}
.cssfw-bs2 .modal { width:auto; }
.tab-content { overflow:visible; }
.chzn-container { color:black; }
.portlet-content .pagination { margin-top: 0; margin-bottom: 0; }

.cssfw-bs3 .table .checkbox-inline { padding-top: 0; }
.cssfw-bs3 textarea { max-width:inherit; min-width: 100%; }
			'],
			'jquery' => [
#				'$("select").not(".portlet select").not(".no-chosen").not(".no-select2").select2();',
#				'$("select").not(".portlet select").not(".no-chosen").chosen();',
			],
		],
	],
	'require' => [
		'asset' => [
			'yf_bootstrap_fixes',
			'jq-select2',
#			'chosen',
		],
	],
	'config' => [
		'no_cache' => true,
		'main_type' => 'admin',
	],
];
