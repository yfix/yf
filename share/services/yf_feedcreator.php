#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/yf_feedcreator.git' => 'yf_feedcreator/'),
	'require_once' => array('yf_feedcreator/feedcreator.class.php'),
	'example' => function() {
		$rss = new UniversalFeedCreator();
		$rss->title = 'my title';
		$rss->description = 'my desc';
		$out = $rss->outputFeed('RSS2.0');
		echo $out;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
