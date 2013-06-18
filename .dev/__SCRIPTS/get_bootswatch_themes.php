#!/usr/bin/php
<?php
$html = file_get_contents("https://raw.github.com/thomaspark/bootswatch/gh-pages/index.html");
preg_match('~<ul class="dropdown-menu" id="swatch-menu">(?P<slice>.*?)</ul>~ims', $html, $m1);
$themes_file = '../__INSTALL/bootswatch_themes.txt';
if (preg_match_all('~<li[^>]*><a href="(?P<theme>[a-z0-9_-]+)/">[^<]+</a></li>~ims', $m1['slice'], $m)) {
	file_put_contents($themes_file, implode("\n", $m['theme']));
}

$d = "./bootswatch_copy/";
if (!file_exists($d)) {
	mkdir($d, 0777, true);
}
foreach(explode("\n", file_get_contents($themes_file)) as $theme) {
	$f = $d. $theme.'-bootstrap.min.css';
	if (file_exists($f) || $theme == "default") {
		continue;
	}
	$url = 'http://netdna.bootstrapcdn.com/bootswatch/2.3.2/'.$theme.'/bootstrap.min.css';
	file_put_contents($f, file_get_contents($url));
}

$f = $d."jquery.min.js";
$url = "http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js";
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}
$f = $d."bootstrap.min.js";
$url = "http://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js";
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}
