<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array('
.left_area .bs-docs-sidenav, .right_area .bs-docs-sidenav { position:fixed; }

.dl-horizontal dt { width: 50%; margin-right: 10px; }

ul#slide_menu .dropdown-divider { font-weight: bold; border-bottom: 1px solid; }

.form-inline legend { margin-bottom: 0; margin-top: 0; }
.form-inline .controls .radio:first-child, .form-inline .controls>.checkbox:first-child { padding-top:0 }
/*.form-inline .control-group label { margin-top: -5px; }*/
.form-inline .form-group { width: 100%; }
.form-inline .form-group .controls { margin-left: 0; }

.cssfw-bs2 .icon-email:before { content: "@"; }

.cssfw-bs2 .breadcrumb>li+li:before { content: "/\00a0"; padding: 0 5px; }
.cssfw-bs2 .navbar .breadcrumb { float:left; margin: 0px 10px; border: 0; }
.cssfw-bs3 .navbar .breadcrumb { float:left; margin: 7px 10px; }
			'),
		),
	),
	'require' => array(
		'css' => 'yf_bootstrap_fixes',
	),
);
