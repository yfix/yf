#!/usr/bin/php
<?php
$html = file_get_contents('https://raw.github.com/thomaspark/bootswatch/gh-pages/index.html');
preg_match('~<ul[^>]+class="dropdown-menu"[^>]+aria-labelledby="themes"[^>]*>(?P<slice>.*?)</ul>~ims', $html, $m1);
$themes_file = './bootswatch_themes.txt';
if (preg_match_all('~<li[^>]*><a[^>]+href="./(?P<theme>[a-z0-9_-]+)/">[^<]+</a>~ims', $m1['slice'], $m)) {
	file_put_contents($themes_file, implode("\n", $m['theme']));
}
if (!file_exists($themes_file) || !filesize($themes_file)) {
	exit('ERROR: Themes not found');
}

$d = './bootswatch_copy-2.3.2/';
if (!file_exists($d)) {
	mkdir($d, 0777, true);
}
foreach(explode("\n", file_get_contents($themes_file)) as $theme) {
	$f = $d. $theme.'-bootstrap.min.css';
	if (file_exists($f) || $theme == 'default') {
		continue;
	}
	$url = 'http://netdna.bootstrapcdn.com/bootswatch/2.3.2/'.$theme.'/bootstrap.min.css';
	file_put_contents($f, file_get_contents($url));
}

$f = $d.'jquery.min.js';
$url = 'http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js';
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}
$f = $d.'bootstrap.min.js';
$url = 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js';
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}

///////////////

$d = './bootswatch_copy-3.0.0/';
if (!file_exists($d)) {
	mkdir($d, 0777, true);
}
foreach(explode("\n", file_get_contents($themes_file)) as $theme) {
	$f = $d. $theme.'-bootstrap.min.css';
	if (file_exists($f) || $theme == 'default') {
		continue;
	}
	$url = 'http://netdna.bootstrapcdn.com/bootswatch/3.0.0/'.$theme.'/bootstrap.min.css';
	file_put_contents($f, file_get_contents($url));
}

$f = $d.'jquery.min.js';
$url = 'http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js';
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}
$f = $d.'bootstrap.min.js';
$url = 'http://netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js';
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}

