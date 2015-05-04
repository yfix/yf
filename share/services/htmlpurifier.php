#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/htmlpurifier.git' => 'htmlpurifier/'),
	'require_once' => array('htmlpurifier/library/HTMLPurifier.auto.php'),
	'example' => function() {
		$purifier = new HTMLPurifier();
		$dirty_html = '<script type="text/javascript">alert(\'Unsafe\');</script><p class=MsoNormal>Hoopla! This is some';
		$clean_html = $purifier->purify($dirty_html);
		echo $clean_html. PHP_EOL;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
