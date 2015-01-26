<?php

$js = <<<'EOS'
(function () { 'use strict';
angular.element( document ).ready( function() {
	angular.bootstrap( document, __ANGULAR_MODULES__ );
});
})();
EOS;

$css = <<<'EOS'
[ng\:cloak], [ng-cloak], [data-ng-cloak], [x-ng-cloak], .ng-cloak, .x-ng-cloak {
  display: none !important;
}
EOS;

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'content' => $js,
				'params' => array(
					'is_last' => true,
				),
			),
			'css' => array(
				'content' => $css,
			),
		),
	),
	'require' => array(
		'asset' => array(
			'ng-modules',
			'angular-full',
		),
	),
);
