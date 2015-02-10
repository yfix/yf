#!/usr/bin/php
<?php

$path = 'yandex-money-sdk-php/';
$lib  = $path.'lib/';
$requires = array();
$git_urls = array('https://github.com/yandex-money/yandex-money-sdk-php.git' => $path);
$autoload_config = array($lib => 'YandexMoney');

require __DIR__.'/_config.php';

require_once $libs_root.$lib.'api.php';
require_once $libs_root.$lib.'external_payment.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$api = new YandexMoney\API($access_token);
	var_dump($api);

}
