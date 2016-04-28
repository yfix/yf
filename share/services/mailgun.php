#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('guzzlehttp_psr7', 'phphttp_httplug', 'phphttp_discovery'),
	'git_urls' => array('https://github.com/mailgun/mailgun-php.git' => 'mailgun/'),
	'autoload_config' => array('mailgun/src/Mailgun/' => 'Mailgun'),
	'example' => function() {
		$mg_client = new Mailgun\Mailgun('key-58a678f54f1ed41f0ab36f791e5b6384');
		var_dump($mg_client);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
