#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/domibarton/PhpZabbixApi.git' => 'php_zabbix_api/'],
	'require_once' => [
		'php_zabbix_api/build/2.2/ZabbixApiAbstract.class.php',
		'php_zabbix_api/build/2.2/ZabbixApi.class.php',
	],
	'example' => function() {
		try {
			$api = new ZabbixApi('http://zabbix.dev/api_jsonrpc.php', 'zabbix', 'admin');
		} catch(Exception $e) {
			echo $e->getMessage();
		}
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
