<?php

return DEBUG_MODE ? function() {
	$assets = _class('assets');
	$bs2_slate_css = '//netdna.bootstrapcdn.com/bootswatch/2.3.2/slate/bootstrap.min.css';
	return array(
		'versions' => array('master' => array(
			'js' => array(
				'content' => '
					var debug_console_override_head = [
						\'<l\' + \'ink href="'.$bs2_slate_css.'" rel="stylesheet">\',
						\'<l\' + \'ink href="'.$assets->get_asset('font-awesome3', 'css').'" rel="stylesheet">\',
						\'<sc\' + \'ript src="'.$assets->get_asset('jquery', 'js').'"></sc\' + \'ript>\',
						\'<sc\' + \'ript src="'.$assets->get_asset('bootstrap2', 'js').'"></sc\' + \'ript>\'
					];
				',
				'params' => array(
					'class' => 'yf_debug_console_asset',
				),
			),
		)),
		'config' => array(
			'no_cache' => true,
		),
	);
} : null;
