#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/yf_feedcreator.git' => 'yf_feedcreator/');
$autoload_config = array();
require __DIR__.'/_config.php';

include_once $libs_root.'yf_feedcreator/feedcreator.class.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$rss = new UniversalFeedCreator();
	$rss->title = 'my title';
	$rss->description = 'my desc';
	$out = $rss->outputFeed('RSS2.0');
	echo $out;

}
