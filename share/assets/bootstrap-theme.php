<?php

return function() {
	$bs_theme = common()->bs_current_theme();
	$bs_version = $CONF['css_framework'] === 'bs3' ? '3' : '2';
	$inherit_name = 'bootstrap'. $bs_version;

	if ($bs_theme === 'bootstrap') {
		return array(
			'inherit' => array(
				'js' => $inherit_name,
				'css' => $inherit_name,
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
		);
	} elseif ($bs_version == 2) {
		return array(
			'versions' => array(
				'2.3.2' => array(
					'css' => '//netdna.bootstrapcdn.com/bootswatch/2.3.2/'.$bs_theme.'/bootstrap.min.css',
				),
			),
			'require' => array(
				'js' => 'bootstrap2',
			),
		);
	} elseif ($bs_version == 3) {
		return array(
			'versions' => array(
				'3.3.0' => array(
					'css' => '//netdna.bootstrapcdn.com/bootswatch/3.3.0/'.$bs_theme.'/bootstrap.min.css',
				),
			),
			'require' => array(
				'js' => 'bootstrap3',
			),
		);
	}
};
