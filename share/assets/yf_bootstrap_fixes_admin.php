<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array('
.left_area { margin-left: 20px; margin-right: 20px; width: 410px; float:left; word-wrap: break-word; }
@media (min-width: 1650px) {
	.left_area { margin-left: 1%; margin-right: 1%; width: 20%; }
	.center_area { margin-left:25%; margin-right: 1%; width: 74%; }
}
.cssfw-bs2 .modal { width: auto; }
.tab-content { overflow: visible; }
.chzn-container { color: black; }
			'),
		),
	),
	'require' => array(
		'css' => 'yf_bootstrap_fixes',
	),
);
