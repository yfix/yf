<?php

return function() {

$bs_major_version = conf('css_framework') === 'bs3' ? '3' : '2';

return [
	'versions' => [
		'master' => [
			'js' => [
				'//rawgit.yfix.net/jschr/bootstrap-modal/master/js/bootstrap-modalmanager.js',
				'//rawgit.yfix.net/jschr/bootstrap-modal/master/js/bootstrap-modal.js',
			],
			'css' => [
				$bs_major_version == 3 ? '//rawgit.yfix.net/jschr/bootstrap-modal/master/css/bootstrap-modal-bs3patch.css' : '',
				'//rawgit.yfix.net/jschr/bootstrap-modal/master/css/bootstrap-modal.css',
			],
		],
	],
	'require' => [
		'asset' => [
			'jquery',
			'bootstrap-theme',
		],
	],
];

};