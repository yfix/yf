#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/phpmailer.git' => 'phpmailer/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'phpmailer/PHPMailerAutoload.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$mail = new PHPMailer(true);
	var_dump($mail);

}
