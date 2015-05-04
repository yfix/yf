<?php

class yf_test_403 {
	function show() {
		no_graphics(true);
		header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 403 Forbidden');
		echo 'Сайт недоступен в вашей стране<br />'.PHP_EOL.'This website is not available in your country';
		exit;
	}
}
