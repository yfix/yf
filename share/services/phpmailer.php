#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpmailer.git' => 'phpmailer/'),
	'require_once' => array('phpmailer/PHPMailerAutoload.php'),
	'example' => function() {
		$mail = new PHPMailer(true);
		var_dump($mail);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
