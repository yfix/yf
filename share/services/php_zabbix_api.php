#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/domibarton/PhpZabbixApi.git' => 'php_zabbix_api/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'php_zabbix_api/build/ZabbixApiAbstract.class.php';
require_once $libs_root.'php_zabbix_api/build/ZabbixApi.class.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	try {
		$api = new ZabbixApi('http://zabbix.dev/api_jsonrpc.php', 'zabbix', 'admin');
	} catch(Exception $e) {
		echo $e->getMessage();
	}

}
