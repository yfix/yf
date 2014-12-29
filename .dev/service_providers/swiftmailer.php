#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/swiftmailer.git' => $libs_root. 'swiftmailer/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
require_once $libs_root.'swiftmailer/lib/swift_required.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$message = Swift_Message::newInstance()
		->setSubject('Your subject')
		->setFrom(array('john@doe.com' => 'John Doe'))
		->setTo(array('receiver@domain.org', 'other@domain.org' => 'A name'))
		->setBody('Here is the message itself')
		->addPart('<q>Here is the message itself</q>', 'text/html')
	;
	var_dump($message);
}
