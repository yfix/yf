#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/yf_domit.git' => 'yf_domit/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'yf_domit/xml_domit_rss.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$rss_doc = new xml_domit_rss_document('https://github.com/blog/all.atom', '/tmp');
#	$total_channels = $rss_doc->getChannelCount();
#	var_dump($total_channels);
	var_dump($rss_doc);

}
