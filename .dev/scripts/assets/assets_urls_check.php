#!/usr/bin/php
<?php

$data = require __DIR__.'/assets_urls_collect.php';

function get_url_size($url) {
	if (substr($url, 0, 2) === '//') {
		$url = 'http:'.$url;
	}
	return strlen(file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]])));
}

foreach ($data['urls'] as $url) {
	$size = get_url_size($url);
	foreach ($data['paths'][$url] as $path) {
		echo ($size > 50 ? 'GOOD' : 'BAD').' | '.$url.' | '.$path.' | '.$size. PHP_EOL;
	}
}
