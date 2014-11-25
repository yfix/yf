#!/usr/bin/php
<?php

$data = require __DIR__.'/assets_urls_collect.php';

function get_url_size($url) {
	if (substr($url, 0, 2) === '//') {
		$url = 'http:'.$url;
	}
	return strlen(file_get_contents($url, false, stream_context_create(array('http' => array('timeout' => 5)))));
#	exec('curl -4 -f --connect-timeout 3 --max-time 5 "'.$url.'" 2>/dev/null | wc -c', $out);
#	return $out[0];
}

foreach ($data['urls'] as $url) {
	$size = get_url_size($url);
	foreach ($data['paths'][$url] as $path) {
		echo ($size > 50 ? 'GOOD' : 'BAD').' | '.$url.' | '.$path.' | '.$size. PHP_EOL;
	}
}
