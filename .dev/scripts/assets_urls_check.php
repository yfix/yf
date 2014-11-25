#!/usr/bin/php
<?php

$regex = '//(cdn|netdna)';
$path = '/home/www/yf/';

function get_url_size($url) {
	if (substr($url, 0, 2) === '//') {
		$url = 'http:'.$url;
	}
	exec('curl -4 -f --connect-timeout 3 "'.$url.'" 2>/dev/null | wc -c', $out);
	return $out[0];
}

$matches = array();

// /home/www/yf/.dev/samples/assets_prototype.php:			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-validate.js',
exec('egrep "'.$regex.'" --include="*.php" -r "'.$path.'" | grep -v "'.basename(__FILE__).'"', $matches);
// /home/www/yf/templates/admin/ng_app_lib.stpl:    <link href="//cdn.rawgit.com/mgcrea/angular-motion/master/dist/angular-motion{js_min}.css" rel="stylesheet">
exec('egrep "'.$regex.'" --include="*.stpl" -r "'.$path.'"', $matches);

$min_regex = '~\{[^\}]*?min[^\}]*?\}~';
foreach ((array)$matches as $k => $v) {
	if (preg_match($min_regex, $v)) {
		$matches[$k] = preg_replace($min_regex, '.min', $v);
		$matches[] = preg_replace($min_regex, '', $v);
	}
}
$urls = array();
$url_paths = array();
foreach ((array)$matches as $v) {
	$path = substr($v, 0, strpos($v, ':'));
	if (!preg_match('~(?P<url>//(cdn|netdna).+?\.(js|css))[\'"\{]+~ims', trim(substr($v, strlen($path.':'))), $m)) {
		continue;
	}
	$url = trim($m['url']);
	if (!strlen($url) || false !== strpos($url, '$')) {
		continue;
	}
	$urls[$url] = $url;
	$url_paths[$url][$path] = $path;
}
foreach ($urls as $url) {
	$size = get_url_size($url);
	foreach ($url_paths[$url] as $path) {
		echo ($size > 50 ? 'GOOD' : 'BAD').' | '.$url.' | '.$path.' | '.$size. PHP_EOL;
	}
}
