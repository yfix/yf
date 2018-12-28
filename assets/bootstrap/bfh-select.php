<?php

return [
    'versions' => [
        'master' => [
            'js' => '//rawgit.yfix.net/yfix/bootstrap-form-helpers/master/dist/js/bootstrap-formhelpers.min.js',
            'css' => '//rawgit.yfix.net/yfix/bootstrap-form-helpers/master/dist/css/bootstrap-formhelpers.min.css',
        ],
    ],
    'github' => [
        'name' => 'yfix/bootstrap-form-helpers',
        'version' => 'master',
        'js' => [
            'dist/js/bootstrap-formhelpers.min.js',
        ],
        'css' => [
            'dist/css/bootstrap-formhelpers.min.css',
            'dist/img/bootstrap-formhelpers-countries.flags.png',
            'dist/img/bootstrap-formhelpers-currencies.flags.png',
            'dist/img/bootstrap-formhelpers-googlefonts.png',
            'dist/img/eu.png',
            'dist/img/xcd.png',
        ],
    ],
    'add' => ['css' => '
		[class^="bfh-flag-"], [class*="bfh-flag-"] { display: inline-block; margin-right: 5px; }
		[class^="bfh-flag-"]:empty, [class*="bfh-flag-"]:empty { width: 16px; }
		.bfh-selectbox { max-width: 300px; }
		.bfh-selectbox-options a { padding-left: 10px; }
		a.bfh-selectbox-toggle:hover { color: #333; }
	'],
    'require' => [
        'asset' => 'jquery',
    ],
    'info' => [
        'url' => 'http://bootstrapformhelpers.com/',
        'name' => 'Bootstrap Form Helpers',
        'desc' => 'Extend Bootstrap\'s components with Bootstrap Form Helpers custom jQuery plugins.',
        'git' => 'https://github.com/yfix/bootstrap-form-helpers.git',
    ],
    'demo' => function () {
        $vals = ['k1' => 'v1', 'k2' => 'v2'];
        $body[] = '<div class="bfh-selectbox">';
        foreach ((array) $vals as $key => $val) {
            $body[] = '<div data-value="' . $key . '">' . $val . '</div>';
        }
        $body[] = '</div>';
        return implode(PHP_EOL, $body);
    },
];
