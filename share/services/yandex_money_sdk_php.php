#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yandex-money/yandex-money-sdk-php.git' => 'yandex-money-sdk-php/'),
	'autoload_config' => array('yandex-money-sdk-php/lib/' => 'YandexMoney'),
	'require_once' => array(
		'yandex-money-sdk-php/lib/api.php',
		'yandex-money-sdk-php/lib/external_payment.php',
	),
	'example' => function() {
		$api = new YandexMoney\API($access_token);
		var_dump($api);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
