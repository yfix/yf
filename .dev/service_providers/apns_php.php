#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/ApnsPHP.git' => $libs_root. 'apns_php/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}

require_once $lib_dir. 'ApnsPHP/Autoload.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$server = new ApnsPHP_Push_Server(ApnsPHP_Abstract::ENVIRONMENT_SANDBOX, 'server_certificates_bundle_sandbox.pem');
	var_dump($server);
}
