#!/usr/bin/php
<?php

$config = array(
	'git' => array('https://github.com/yfix/ApnsPHP.git' => 'apns_php/'),
	'require_once' => array('apns_php/ApnsPHP/Autoload.php'),
	'example' => function($obj) {
		$server = new ApnsPHP_Push_Server(ApnsPHP_Abstract::ENVIRONMENT_SANDBOX, 'server_certificates_bundle_sandbox.pem');
		var_dump($server);
	}
);
require __DIR__.'/_yf_autoloader.php';
