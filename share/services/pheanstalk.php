#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/pda/pheanstalk.git' => 'pheanstalk/'),
	'autoload_config' => array('pheanstalk/src/' => 'Pheanstalk'),
	'example' => function() {
		$pheanstalk = new Pheanstalk\Pheanstalk('127.0.0.1');
		var_dump($pheanstalk);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
