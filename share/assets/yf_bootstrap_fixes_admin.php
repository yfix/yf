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

.modal { width: auto; }
.tab-content { overflow: visible; }
.chzn-container { color: black; }

.controls>.radio:first-child { padding-top:inherit; }
.form-inline .radio:first-child { padding-left: 5px; }
.form-inline .radio input[type=radio] { float: none; }
.form-inline .form-group { width: 100%; }
.form-inline .form-group .controls { margin-left: 0; }

.cssfw-bs2 .icon-email:before { content: "@"; }
			'),
		),
	),
	'require' => array(
		'css' => 'yf_bootstrap_fixes',
	),
);
