#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/swiftmailer.git' => 'swiftmailer/');
$autoload_config = array();
require __DIR__.'/_config.php';

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
