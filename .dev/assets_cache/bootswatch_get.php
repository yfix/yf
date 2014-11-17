#!/usr/bin/php
<?php

$cache_dir = __DIR__;

$bs_v3 = '3.3.0';
$bs_v2 = '2.3.2';
$jquery_v = '1.11.1';
$jquery_url = 'http://ajax.googleapis.com/ajax/libs/jquery/'.$jquery_v.'/jquery.min.js';

$dir_bs3 = $cache_dir.'/bootswatch/'.$bs_v3.'/';
$dir_bs2 = $cache_dir.'/bootswatch/'.$bs_v2.'/';

$themes_bs3_file = $cache_dir.'/bootswatch/themes_bs3.txt';
$themes_bs2_file = $cache_dir.'/bootswatch/themes_bs2.txt';

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
save_url_to_file($jquery_url, $dir_bs3.'jquery.min.js');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/'.$bs_v3.'/js/bootstrap.min.js', $dir_bs3.'bootstrap.min.js');

// Bootstrap 2
foreach (get_themes_bs2() as $theme) {
	$file = $dir_bs2. $theme.'-bootstrap.min.css';
	save_url_to_file('http://netdna.bootstrapcdn.com/bootswatch/'.$bs_v2.'/'.$theme.'/bootstrap.min.css', $file);
}
save_url_to_file($jquery_url, $dir_bs2.'jquery.min.js');
save_url_to_file('http://netdna.bootstrapcdn.com/twitter-bootstrap/'.$bs_v2.'/js/bootstrap.min.js', $dir_bs2.'bootstrap.min.js');
