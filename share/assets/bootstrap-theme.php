<?php

return function() {

$bs_theme = common()->bs_current_theme();
$bs_major_version = conf('css_framework') === 'bs3' ? '3' : '2';
$require_name = 'bootstrap'. $bs_major_version;
$fixes_name = 'yf_bootstrap_fixes_'.MAIN_TYPE;

if ($bs_theme === 'bootstrap') {
	return array(
		'require' => array(
			'asset' => $require_name,
		),
		'add' => array(
			'css' => $fixes_name,
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
			'css' => 'bootstrap3',
		),
		'add' => array(
			'css' => $fixes_name,
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
				$fixes_name,
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
				$fixes_name,
			),
		),
	);
}

};