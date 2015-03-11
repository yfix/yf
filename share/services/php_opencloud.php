#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('guzzle3'),
	'git_urls' => array('https://github.com/rackspace/php-opencloud.git' => 'php-opencloud/'),
	'autoload_config' => array('php-opencloud/lib/OpenCloud/' => 'OpenCloud'),
	'example' => function() {
		$client = new \OpenCloud\Rackspace(\OpenCloud\Rackspace::US_IDENTITY_ENDPOINT, array(
		    'username' => 'foo',
	    	'apiKey'   => 'bar'
		));
		var_dump($client);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
