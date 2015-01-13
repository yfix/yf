#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/igorw/evenement.git' => 'evenement/');
$autoload_config = array('evenement/src/Evenement/' => 'Evenement');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$emitter = new Evenement\EventEmitter();
	var_dump($emitter);

}
