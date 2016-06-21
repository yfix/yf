#!/usr/bin/php
<?php

$config = [
	'require_services' => ['guzzle'],
	'git_urls' => ['https://github.com/yfix/google-translate-php.git' => 'google-translate-php/'],
	'autoload_config' => ['google-translate-php/src/Stichoza/GoogleTranslate/' => 'Stichoza\GoogleTranslate'],
	'example' => function() {
		$tr = new Stichoza\GoogleTranslate\TranslateClient('en', 'ru');
		echo $tr->translate('Hello World!');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
