#!/usr/bin/php
<?php
$html = file_get_contents("https://raw.github.com/thomaspark/bootswatch/gh-pages/index.html");
preg_match('~<ul class="dropdown-menu" id="swatch-menu">(?P<slice>.*?)</ul>~ims', $html, $m1);
if (preg_match_all('~<li[^>]*><a href="(?P<theme>[a-z0-9_-]+)/">[^<]+</a></li>~ims', $m1['slice'], $m)) {
	file_put_contents('../__INSTALL/bootswatch_themes.txt', implode("\n", $m['theme']));
}
