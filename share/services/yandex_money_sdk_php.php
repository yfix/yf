#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yandex-money/yandex-money-sdk-php.git' => 'yandex-money-sdk-php/');
$autoload_config = array('yandex-money-sdk-php/lib/' => 'YandexMoney');
require __DIR__.'/_config.php';

foreach(explode(' ', 'api.php base.php exceptions.php external_payment.php') as $f) {
	include_once $libs_root. 'yandex-money-sdk-php/lib/'. $f;
}

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$api = new YandexMoney\API($access_token);
	var_dump($api);

}
