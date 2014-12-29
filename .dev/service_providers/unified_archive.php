#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/php-unified-archive.git' => 'php-unified-archive/');
$autoload_config = array('php-unified-archive/src/' => 'wapmorgan\UnifiedArchive');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$out = \wapmorgan\UnifiedArchive\UnifiedArchive::archiveNodes('./', 'samples_archive.zip', $fake = true);
	var_export($out);
}
