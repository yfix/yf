#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/swiftmailer.git' => 'swiftmailer/'),
	'require_once' => array('swiftmailer/lib/swift_required.php'),
	'example' => function() {
		$message = Swift_Message::newInstance()
			->setSubject('Your subject')
			->setFrom(array('john@doe.com' => 'John Doe'))
			->setTo(array('receiver@domain.org', 'other@domain.org' => 'A name'))
			->setBody('Here is the message itself')
			->addPart('<q>Here is the message itself</q>', 'text/html')
		;
		var_dump($message);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
