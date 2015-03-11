#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/yf_domit.git' => 'yf_domit/'),
	'require_once' => array('yf_domit/xml_domit_rss.php'),
	'example' => function() {
		$rss_doc = new xml_domit_rss_document('https://github.com/blog/all.atom', '/tmp');
		var_dump($rss_doc);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
