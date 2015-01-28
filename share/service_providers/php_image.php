#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/kus/php-image.git' => 'php_image/');
$autoload_config = array('php_image/src/' => '');
require __DIR__.'/_config.php';

require_once $libs_root.'php_image/src/PHPImage.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$image = new PHPImage();
	var_dump($image);

}
