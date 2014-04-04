#!/usr/bin/php
<?php

# wget https://launchpad.net/~ondrej/+archive/php5/+files/php5-imagick_3.1.0%7Erc2-1%7Eprecise%2B1_i386.deb
# dpkg -i php5-imagick_3.1.0~rc2-1~precise+1_i386.deb 

$force_resize = true;
$max_x = 100;
$max_y = 100;
$method = 'imagick_ext';
if (!class_exists('Imagick')) {
	$method = 'convert';
}

function _resize($f = '', $s = '') {
	global $max_x, $max_y, $method;
	if (!$f || (!is_array($f) && !$s)) {
		return false;
	}
	if (!is_array($f)) {
		$f = array($f => $s);
	}
	foreach ((array)$f as $_f => $_s) {
		$ts = microtime(true);
		if ($method == 'imagick_ext') {
			$im = new Imagick($_f);
#			$im->adaptiveResizeImage($max_x, $max_y, $bestfit = true);
			$im->thumbnailImage($max_x, $max_y, $bestfit = true);
			$im->writeImage($_s);
			$cmd = $_f.' -> '.$_s;
		} elseif ($method == 'convert') {
			$cmd = 'convert '.$_f.' -resize '.$max_x.'x'.$max_y.'^ '.$_s;
			$result = exec($cmd);
		}
		echo $cmd.' '.round(microtime(true) - $ts, 3).' sec'.PHP_EOL;
	}
}

foreach(glob('./*/*/') as $dir) {
	$to_resize = array();
	foreach(glob($dir.'product_*_full.jpg') as $img_full) {
		$img_small = str_replace('_full.jpg', '_small.jpg', $img_full);
		if (!filesize($img_full)) {
			continue;
		}
		if (file_exists($img_small) && !$force_resize) {
			continue;
		}
		$to_resize[$img_full] = $img_small;
	}
	_resize($to_resize);
}

# HINT: to remove all small images:
# find . -type f -name '*small*' | xargs rm -v
# _OR_ (slightly faster):
# find . -type f -name '*small*' -exec rm {} \;
