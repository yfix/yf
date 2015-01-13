#!/usr/bin/php
<?php

$requires = array('guzzle3');
$git_urls = array('https://github.com/KnpLabs/php-github-api.git' => 'php_github_api/');
$autoload_config = array('php_github_api/lib/Github/' => 'Github');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$client = new \Github\Client();
	$repositories = $client->api('user')->repositories('yfix');
	foreach($repositories as $v) {
		$a[$v['full_name']] = $v['html_url'];
	}
	print_r($a);

}
