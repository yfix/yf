<?php

return function($assets) {

$main_type = $assets->_override['main_type'] ?: MAIN_TYPE;
if (!(is_console() || $assets->_override['main_type'] || $main_type == 'user')) {
	$bs_theme = common()->bs_current_theme($main_type, $force_default = false);
} else {
	$bs_theme = common()->bs_current_theme($main_type, $force_default = true);
}
$html5fw = conf('css_framework');
$bs_major_version = $html5fw === 'bs3' ? '3' : '2';
$require_name = 'bootstrap'. $bs_major_version;
$fixes_name = 'yf_bootstrap_fixes_'.$main_type;

if ($bs_theme === 'bootstrap') {
	conf('bs3_no_default_theme', true);
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'asset' => array(
				'font-awesome4',
				$fixes_name,
			),
		),
	);
} elseif ($bs_theme === 'bootstrap_theme') {
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'asset' => array(
				'font-awesome4',
				$fixes_name,
			),
		),
	);
} elseif ($bs_theme === 'flatui') {
	conf('bs3_no_default_theme', true);
	return array(
		'versions' => array(
			'master' => array(
				'css' => array(
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/css/vendor/bootstrap/bootstrap.min.css',
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/css/flat-ui.min.css',
				),
				'js' => array(
					'//cdn.rawgit.com/yfix/Flat-UI/master/dist/js/flat-ui.min.js',
				),
			),
		),
	);
} elseif ($bs_theme === 'material_design') {
	conf('bs3_no_default_theme', true);
	return array(
		'versions' => array(
			'master' => array(
				'css' => array(
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.1/css/ripples.min.css',
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.1/css/bootstrap-material-design.min.css',
				),
				'js' => array(
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.1/js/ripples.min.js',
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.1/js/material.min.js',
					'$(function(){ $.material.init(); })',
				),
			),
		),
		'require' => array(
			'asset' => 'bootstrap3',
		),
		'add' => array(
			'asset' => $fixes_name,
		),
	);
} elseif ($bs_theme === 'todc_bootstrap') {
	conf('bs3_no_default_theme', true);
	return array(
		'versions' => array(
			'master' => array(
				'css' => array(
					'//cdn.rawgit.com/yfix/todc-bootstrap/master/dist/css/bootstrap.min.css',
					'//cdn.rawgit.com/yfix/todc-bootstrap/master/dist/css/todc-bootstrap.min.css',
				),
				'js' => array(
					'//cdn.rawgit.com/yfix/todc-bootstrap/master/dist/js/bootstrap.min.js',
				),
			),
		),
		'require' => array(
			'asset' => 'bootstrap3',
		),
		'add' => array(
			'asset' => $fixes_name,
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
			'asset' => 'bootstrap2',
		),
		'add' => array(
			'asset' => array(
				'font-awesome3',
#				'font-awesome4',
				$fixes_name,
			),
		),
	);
} elseif ($bs_major_version == 3) {
	conf('bs3_no_default_theme', true);
	return array(
		'versions' => array(
			'3.3.6' => array(
				'css' => '//netdna.bootstrapcdn.com/bootswatch/3.3.6/'.$bs_theme.'/bootstrap.min.css',
			),
		),
		'require' => array(
			'asset' => 'bootstrap3',
		),
		'add' => array(
			'asset' => array(
				'font-awesome4',
				$fixes_name,
			),
		),
	);
}

};