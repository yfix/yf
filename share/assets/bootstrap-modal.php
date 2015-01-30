<?php

return function() {

$bs_major_version = conf('css_framework') === 'bs3' ? '3' : '2';

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/jschr/bootstrap-modal/master/js/bootstrap-modalmanager.js',
				'//cdn.rawgit.com/jschr/bootstrap-modal/master/js/bootstrap-modal.js',
			),
			'css' => array(
				$bs_major_version == 3 ? '//cdn.rawgit.com/jschr/bootstrap-modal/master/css/bootstrap-modal-bs3patch.css' : '',
				'//cdn.rawgit.com/jschr/bootstrap-modal/master/css/bootstrap-modal.css',
			),
		),
	),
	'require' => array(
		'asset' => array(
			'jquery',
			'bootstrap-theme',
		),
	),
);

};