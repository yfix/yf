<?php

/**
* Simple replacement for http://placehold.it/
*/
function yf_placeholder_img($w = 100, $h = 100, $text = '', $params = array()) {
	$w = (int)$w ?: 100;
	$h = (int)$h ?: 100;
	$text = $text ?: $w.' x '.$h;

	$font_try_paths = $params['font_paths'] ?: array(
		'/usr/share/fonts/',
		'/usr/share/fonts/dejavu/',
		'/usr/share/fonts/truetype/',
		'/usr/share/fonts/truetype/dejavu/',
		'/usr/share/fonts/truetype/ttf-dejavu/', // Centos
	);
	$font_name = $params['font_name'] ?: 'DejaVuSans-Bold.ttf';
	$font = '';
	foreach ($font_try_paths as $path) {
		if (file_exists($path. $font_name)) {
			$font = $path. $font_name;
			break;
		}
	}
	$im = imagecreatetruecolor($w, $h);

	$color_text = imagecolorallocate($im, 80, 80, 80);
	$color_bg = imagecolorallocate($im, 160, 160, 160);

	$font_size = $params['font_size'] ?: 12;
	$font_angle = $params['font_angle'] ?: 0;

	// Set the background
	imagefilledrectangle($im, 0, 0, $w, $h, $color_bg);

	$bbox = imagettfbbox($font_size, $font_angle, $font, $text);
	$x = $bbox[0] + (imagesx($im) / 2) - ($bbox[4] / 2) - 5;
	$y = $bbox[1] + (imagesy($im) / 2) - ($bbox[5] / 2) - 5;
	imagettftext($im, $font_size, $font_angle, $x, $y, $color_text, $font, $text);

	ob_start();
	imagepng($im);
	imagedestroy($im);
	$data = ob_get_clean();

	header('Content-Type: image/png', true);
	header('Last-Modified: '. gmdate('D, d M Y 00:01:01') . ' GMT', true);
	header('Content-Length: '.strlen($data), true);
	header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet', true);
	header_remove('Pragma');
	header_remove('Cache-Control');
	header_remove('Expires');
	header_remove('Set-Cookie');

	echo $data;
	return $data;
}
