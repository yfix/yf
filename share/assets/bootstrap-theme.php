<?php

return function() {

$bs_theme = common()->bs_current_theme();
$bs_major_version = conf('css_framework') === 'bs3' ? '3' : '2';
$require_name = 'bootstrap'. $bs_major_version;

$css_fixes[2] = '
	label.radio-horizontal { padding-left: inherit; display: inline-block; margin-bottom: inherit; }
	label.radio-horizontal input[type=radio] { float: none; margin-left:inherit; margin-top:0; }
	.dropdown-toggle .caret { margin: 0 !important; }
';
$css_fixes[3] = '
	.container-fixed input, .container-fixed textarea, .container-fixed select { max-width: 300px; }
	.container-fixed .input-mini { width:70px !important }
	.container-fixed .input-small { width:100px !important }
	.container-fixed .input-medium { width:160px !important }
	.container-fixed .input-large { width:220px !important }
	.container-fixed .input-xlarge { width:280px !important }
	.container-fixed .input-xxlarge { width:540px !important }
	.container-fixed .input-group[class*="col-"] { float:left !important; margin-right: 3px; }
	label.radio-horizontal { padding-left: inherit; display: inline-block; margin-right: 5px; }
	label.radio-horizontal input[type=radio] { float: left; }
	.form-horizontal .radio, .form-horizontal .checkbox { padding-left: 20px; }
';

if ($bs_theme === 'bootstrap') {
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'css' => $css_fixes[$bs_major_version],
		),
	);
} elseif ($bs_theme === 'flatui') {
	return array(
		'versions' => array(
			'master' => array(
				'css' => array(
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/css/vendor/bootstrap.min.css',
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/css/flat-ui.min.css',
				),
				'js' => array(
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/js/flat-ui.min.js',
				),
			),
		),
		'require' => array(
			'css' => 'bootstrap2',
		),
	);
} elseif ($bs_major_version == 2) {
	return array(
		'versions' => array(
			'2.3.2' => array(
				'css' => '//netdna.bootstrapcdn.com/bootswatch/2.3.2/'.$bs_theme.'/bootstrap.min.css',
			),
		),
		'require' => array(
			'js' => 'bootstrap2',
		),
		'add' => array(
			'css' => array(
				'font-awesome3',
				$css_fixes[$bs_major_version],
			),
		),
	);
} elseif ($bs_major_version == 3) {
	return array(
		'versions' => array(
			'3.3.0' => array(
				'css' => '//netdna.bootstrapcdn.com/bootswatch/3.3.0/'.$bs_theme.'/bootstrap.min.css',
			),
		),
		'require' => array(
			'js' => 'bootstrap3',
		),
		'add' => array(
			'css' => array(
				'font-awesome4',
				$css_fixes[$bs_major_version],
			),
		),
	);
}

};