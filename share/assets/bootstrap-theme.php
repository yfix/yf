<?php

return function() {

$bs_theme = common()->bs_current_theme();
$bs_major_version = conf('css_framework') === 'bs3' ? '3' : '2';
$require_name = 'bootstrap'. $bs_major_version;
$fixes_name = 'yf_bootstrap_fixes_'.MAIN_TYPE;

if ($bs_theme === 'bootstrap') {
	conf('bs3_no_default_theme', true);
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'asset' => $fixes_name,
		),
	);
} elseif ($bs_theme === 'bootstrap_theme') {
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'asset' => $fixes_name,
		),
	);
} elseif ($bs_theme === 'flatui') {
	conf('bs3_no_default_theme', true);
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
	);
} elseif ($bs_theme === 'material_design') {
	conf('bs3_no_default_theme', true);
	return array(
		'versions' => array(
			'master' => array(
				'css' => array(
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/0.2.1/css/ripples.min.css',
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/0.2.1/css/material-wfont.min.css',
				),
				'js' => array(
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/0.2.1/js/ripples.min.js',
					'//cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/0.2.1/js/material.min.js',
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
				$fixes_name,
			),
		),
	);
} elseif ($bs_major_version == 3) {
	return array(
		'versions' => array(
			'3.3.2' => array(
				'css' => '//netdna.bootstrapcdn.com/bootswatch/3.3.2/'.$bs_theme.'/bootstrap.min.css',
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