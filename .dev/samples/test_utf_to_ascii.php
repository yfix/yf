<?php

$url = "http://www.spainhouses.net/propiedad/casa-en-venta-a-estrenar-en-matalascaÃ+as-234189es.htm";
$base_url = "http://www.spainhouses.net/propiedad/casa-en-venta-a-estrenar-en-matalascaÃ+as-234189es.htm";

$url = iconv('ISO-8859-15', 'UTF-8', $url);
$base_url = iconv('ISO-8859-15', 'UTF-8', $base_url);

$test_file = INCLUDE_PATH."test.html";
if (!file_exists($test_file)) {
	file_put_contents($test_file, file_get_contents($url));
}
$text = file_get_contents($test_file);
preg_match_all("/<a[^>]*? href=[\'\"]{0,1}([^>]+?)[\'\"]{0,1} [^>]*?>/ims", $text, $m);
foreach ((array)$m[1] as $k => $v) {

	$enc = mb_detect_encoding($v);
	if ($enc == "UTF-8") {
		$v = iconv($enc, "US-ASCII//TRANSLIT", $v);
	}
//echo utf8_decode($v)."<br />";
//	$v = iconv('ISO-8859-15', 'UTF-8', $v);
//	$v = utf8toiso8859($v);
//	$v = utf8_decode($v);
//	$v = utf8_to_unicode($v);
//	$v = rawurldecode($v);
	echo htmlspecialchars($v)." ||| ".htmlspecialchars(common()->url_to_absolute($base_url, $v))."<br />\n";
}

