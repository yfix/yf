<?php

return function () {
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

    return [
    'versions' => [
        'master' => [
            'js' => [
                'content' => $js,
                'params' => [
                    'is_last' => true,
                ],
            ],
            'css' => [
                'content' => $css,
            ],
        ],
    ],
    'require' => [
        'asset' => [
            'ng-modules',
            'angular-full',
        ],
    ],
];
};
