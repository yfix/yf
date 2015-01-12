#!/usr/bin/php
<?php

$requires = array('guzzle3');
$git_urls = array('https://github.com/rackspace/php-opencloud.git' => 'php-opencloud/');
$autoload_config = array('php-opencloud/lib/OpenCloud/' => 'OpenCloud');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$client = new \OpenCloud\Rackspace(\OpenCloud\Rackspace::US_IDENTITY_ENDPOINT, array(
	    'username' => 'foo',
	    'apiKey'   => 'bar'
	));
	var_dump($client);
}
