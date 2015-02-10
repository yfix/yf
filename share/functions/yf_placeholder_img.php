<?php

/**
* Simple replacement for http://placehold.it/
*/
function yf_placeholder_img($w = 100, $h = 100, $params = array()) {
	$w = abs((int)$w ?: 100);
	$h = abs((int)$h ?: 100);
	$text = $params['text'] ?: $w.' x '.$h;

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

	$ctext = $params['color_text'] ?: '777';
	$cbg = $params['color_bg'] ?: 'bbb';

	strlen($ctext) === 3 && $ctext = str_repeat(substr($ctext, 0, 1), 2). str_repeat(substr($ctext, 1, 1), 2). str_repeat(substr($ctext, 2, 1), 2);
	strlen($cbg) === 3 && $cbg = str_repeat(substr($cbg, 0, 1), 2). str_repeat(substr($cbg, 1, 1), 2). str_repeat(substr($cbg, 2, 1), 2);
	$color_text = imagecolorallocate($im, hexdec(substr($ctext, 0, 2)), hexdec(substr($ctext, 2, 2)), hexdec(substr($ctext, 4, 2)));
	$color_bg = imagecolorallocate($im, hexdec(substr($cbg, 0, 2)), hexdec(substr($cbg, 2, 2)), hexdec(substr($cbg, 4, 2)));

	$min_size = $params['min_size'] ?: 5;
	$max_size = $params['max_size'] ?: 14;
	$def_size = ceil($w / 10) + 1;
	if ($def_size < $min_size) {
		$def_size = $min_size;
	}
	if ($def_size > $max_size) {
		$def_size = $max_size;
	}
	$font_size = $params['font_size'] ?: $def_size;
	$font_angle = $params['font_angle'] ?: 0;

	// Set the background
	imagefilledrectangle($im, 0, 0, $w, $h, $color_bg);

	$bbox = imagettfbbox($font_size, $font_angle, $font, $text);
	$x = $bbox[0] + (imagesx($im) / 2) - ($bbox[4] / 2);
	$y = $bbox[1] + (imagesy($im) / 2) - ($bbox[5] / 2);
	imagettftext($im, $font_size, $font_angle, $x, $y, $color_text, $font, $text);

	ob_start();
	imagepng($im);
	imagedestroy($im);
	$data = ob_get_clean();

	if (!$params['no_out']) {
		header('Content-Type: image/png', true);
		header('Last-Modified: '. gmdate('D, d M Y 00:01:01') . ' GMT', true);
		header('Content-Length: '.strlen($data), true);
		header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet', true);
		header_remove('Pragma');
		header_remove('Cache-Control');
		header_remove('Expires');
		header_remove('Set-Cookie');
		echo $data;
	}
	return $data;
}
