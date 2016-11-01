#!/usr/bin/php
<?php

$config = [
	'require_services' => ['phphttp_httplug', 'phphttp_message_factory', 'phphttp_message', 'sf_options_resolver'],
	'git_urls' => ['https://github.com/php-http/client-common.git' => 'phphttp_client_common/'],
	'autoload_config' => ['phphttp_client_common/src/' => 'Http\Client\Common'],
	'example' => function() {
		var_dump(class_exists('Http\Client\Common\BatchResult'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
