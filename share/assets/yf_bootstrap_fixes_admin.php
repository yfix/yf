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
.cssfw-bs3 .navbar-header { width:99%; padding-right:1%; }

.cssfw-bs3 .stacked-item .form-control { display: inline-block; }
.cssfw-bs3 .stacked-item input[type=number], 
.cssfw-bs3 .form-group .small-number { width: 120px; }
.cssfw-bs3 .form-group select[name=order_by] { width:150px; }
.cssfw-bs3 .form-group select[name=order_direction] { width:40px; font-weight:bold; padding:0; font-size: 16px; }

.form-horizontal .radio, .form-horizontal .checkbox { min-height: 10px; }
			'],
			'jquery' => [
#				'$("select").not(".portlet select").not(".no-chosen").not(".no-select2").select2();',
#				'$("select").not(".portlet select").not(".no-chosen").chosen();',
				'var filter_timeout;
				$(".left_area form").on("change", function(e){
					var form = $(this)
					clearTimeout(filter_timeout);
					// do stuff when user has been idle for selected time
					filter_timeout = setTimeout(function() {
						console.log("filter submit")
						form.submit()
					}, 1000);
				})',
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
