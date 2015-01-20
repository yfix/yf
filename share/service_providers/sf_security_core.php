#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/security-core.git' => 'sf_security_core/');
$autoload_config = array('sf_security_core/' => 'Symfony\Component\Security\Core');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

#	$history = new \Symfony\Component\BrowserKit\History();
#	var_dump($history);

}
