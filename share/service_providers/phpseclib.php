#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/phpseclib.git' => 'phpseclib/');
// TODO: implement pear-style autoload
#$autoload_config = array('phpseclib/phpseclib/' => 'phpseclib');
$autoload_config = array();
require __DIR__.'/_config.php';

set_include_path($libs_root. 'phpseclib/phpseclib/'. PATH_SEPARATOR. get_include_path());
require_once('Crypt/RSA.php');
require_once('Net/SSH2.php');

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$key = new Crypt_RSA();
	var_dump($key);
}
