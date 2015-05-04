#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/geoip-api-php.git' => 'geoip_api_php/'),
	'manual' => function($loader) {
		if (!extension_loaded('geoip')) {
			require_once $loader->libs_root.'geoip_api_php/src/geoip.inc';
			require_once $loader->libs_root.'geoip_api_php/src/geoipcity.inc';
			require_once $loader->libs_root.'geoip_api_php/src/timezone.php';
		}
	},
	'example' => function() {
		if (!extension_loaded('geoip')) {
			$gi = geoip_open('/usr/local/share/GeoIP/GeoIP.dat', GEOIP_STANDARD);
			echo geoip_country_code_by_addr($gi, '24.24.24.24') . "\t" . geoip_country_name_by_addr($gi, '24.24.24.24'). PHP_EOL;
			echo geoip_country_code_by_addr($gi, '80.24.24.24') . "\t" . geoip_country_name_by_addr($gi, '80.24.24.24'). PHP_EOL;
			geoip_close($gi);
		} else {
			echo geoip_country_code_by_name('24.24.24.24'). PHP_EOL;
			echo geoip_country_code_by_name('80.24.24.24'). PHP_EOL;
		}
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
