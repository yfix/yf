#!/usr/bin/php
<?php

require __DIR__.'/assets_urls_collect.php';

function get_url_size($url) {
	if (substr($url, 0, 2) === '//') {
		$url = 'http:'.$url;
	}
	exec('curl -4 -f --connect-timeout 3 --max-time 5 "'.$url.'" 2>/dev/null | wc -c', $out);
	return $out[0];
}

foreach ($urls as $url) {
	$size = get_url_size($url);
	foreach ($url_paths[$url] as $path) {
		echo ($size > 50 ? 'GOOD' : 'BAD').' | '.$url.' | '.$path.' | '.$size. PHP_EOL;
	}
}
