<?php

return array(
	'versions' => array(
		'1.0.2' => array(
			'js' => array(
				'//oss.maxcdn.com/icheck/1.0.2/icheck.min.js',
				'$(function(){
					$("input").iCheck({
						checkboxClass: "icheckbox_square",
						radioClass: "iradio_square"
					});
				})',
			),
			'css' => array(
				'//oss.maxcdn.com/icheck/1.0.2/skins/square/square.min.css',
			),
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);
