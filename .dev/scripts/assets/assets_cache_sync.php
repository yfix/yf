#!/usr/bin/php
<?php

$cache_dir = dirname(dirname(__DIR__)).'/assets_cache/';

$bs_v3 = '3.3.0';
$bs_v2 = '2.3.2';
$fa3 = '3.2.1';
$fa4 = '4.2.0';
$jquery_v = '1.11.1';
$jquery_url = 'http://ajax.googleapis.com/ajax/libs/jquery/'.$jquery_v.'/jquery.min.js';

$dir_bs3 = $cache_dir.'bootswatch/'.$bs_v3.'/';
$dir_bs2 = $cache_dir.'bootswatch/'.$bs_v2.'/';

$themes_bs3_file = $cache_dir.'bootswatch/themes_bs3.txt';
$themes_bs2_file = $cache_dir.'bootswatch/themes_bs2.txt';

function save_url_to_file($url, $file) {
	$dir = dirname($file);
	if (!file_exists($dir)) {
		mkdir($dir, 0755, true);
	}
	$str = file_get_contents($url);
	if (!strlen($str)) {
		return false;
	}
	if (file_exists($file) && file_get_contents($file) === $str) {
		return true;
	}
	return file_put_contents($file, $str);
}

function get_urls_from_css($css) {
	preg_match_all('~url\(\'(?P<url>.*?)\'\)~ims', $css, $m);
	$urls = [];
	foreach ((array)$m['url'] as $url) {
		if (substr($url, 0, strlen('../')) === '../') {
			$url = substr($url, strlen('../'));
		}
		if (false !== ($pos = strpos($url, '#'))) {
			$url = substr($url, 0, $pos);
		}
		if (false !== ($pos = strpos($url, '?'))) {
			$url = substr($url, 0, $pos);
		}
		$urls[$url] = $url;
	}
	return $urls;
}

function get_themes_bs3() {
	global $themes_bs3_file;

	$html = file_get_contents('https://raw.github.com/thomaspark/bootswatch/gh-pages/index.html');
	preg_match('~<ul[^>]+class="dropdown-menu"[^>]+aria-labelledby="themes"[^>]*>(?P<slice>.*?)</ul>~ims', $html, $m1);
	if (preg_match_all('~<li[^>]*><a[^>]+href="./(?P<theme>[a-z0-9_-]+)/">[^<]+</a>~ims', $m1['slice'], $m)) {
		$dir = dirname($themes_bs3_file);
		if (!file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
		foreach ($m['theme'] as $k => $v) {
			if (!$v || $v === 'default') {
				unset($m['theme'][$k]);
			}
		}
		file_put_contents($themes_bs3_file, trim(implode(PHP_EOL, $m['theme'])));
	}
	if (!file_exists($themes_bs3_file) || !filesize($themes_bs3_file)) {
		exit('ERROR: Themes not found');
	}
	return explode(PHP_EOL, trim(file_get_contents($themes_bs3_file)));
}

function get_themes_bs2() {
	global $themes_bs2_file;
	return explode(PHP_EOL, trim(file_get_contents($themes_bs2_file)));
}

// Bootstrap 3
foreach (get_themes_bs3() as $theme) {
	$file = $dir_bs3. $theme.'-bootstrap.min.css';
	save_url_to_file('http://netdna.bootstrapcdn.com/bootswatch/'.$bs_v3.'/'.$theme.'/bootstrap.min.css', $file);
}
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/'.$bs_v3.'/css/bootstrap.min.css', $dir_bs3.'bootstrap.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/'.$bs_v3.'/css/bootstrap-theme.min.css', $dir_bs3.'bootstrap-theme.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/'.$bs_v3.'/js/bootstrap.min.js', $dir_bs3.'bootstrap.min.js');
save_url_to_file($jquery_url, $dir_bs3.'jquery.min.js');

// Bootstrap 2
foreach (get_themes_bs2() as $theme) {
	$file = $dir_bs2. $theme.'-bootstrap.min.css';
	save_url_to_file('http://netdna.bootstrapcdn.com/bootswatch/'.$bs_v2.'/'.$theme.'/bootstrap.min.css', $file);
}
save_url_to_file('http://netdna.bootstrapcdn.com/twitter-bootstrap/'.$bs_v2.'/css/bootstrap-combined.min.css', $dir_bs2.'bootstrap-combined.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/twitter-bootstrap/'.$bs_v2.'/js/bootstrap.min.js', $dir_bs2.'bootstrap.min.js');
save_url_to_file($jquery_url, $dir_bs2.'jquery.min.js');

// Jquery
save_url_to_file($jquery_url, $cache_dir.'jquery/'.$jquery_v.'/jquery.min.js');
save_url_to_file('http://ajax.googleapis.com/ajax/libs/jqueryui/'.$jquery_v.'/jquery-ui.min.js', $cache_dir.'jquery-ui/'.$jquery_v.'/jquery-ui.min.js');

// Font Awesome
save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/'.$fa3.'/css/font-awesome.min.css', $cache_dir.'fontawesome/'.$fa3.'/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir.'fontawesome/'.$fa3.'/css/font-awesome.min.css')) as $url) {
	save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/'.$fa3.'/'.$url, $cache_dir.'fontawesome/'.$fa3.'/'.$url);
}
save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/'.$fa4.'/css/font-awesome.min.css', $cache_dir.'fontawesome/'.$fa4.'/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir.'fontawesome/'.$fa4.'/css/font-awesome.min.css')) as $url) {
	save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/'.$fa4.'/'.$url, $cache_dir.'fontawesome/'.$fa4.'/'.$url);
}
