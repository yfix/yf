#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/pda/pheanstalk.git' => 'pheanstalk/');
$autoload_config = array('pheanstalk/src/' => 'Pheanstalk');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$pheanstalk = new Pheanstalk\Pheanstalk('127.0.0.1');
	var_dump($pheanstalk);

}
