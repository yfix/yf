#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/ApnsPHP.git' => 'apns_php/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root. 'apns_php/ApnsPHP/Autoload.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$server = new ApnsPHP_Push_Server(ApnsPHP_Abstract::ENVIRONMENT_SANDBOX, 'server_certificates_bundle_sandbox.pem');
	var_dump($server);
}
