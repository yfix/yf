#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/geoip-api-php.git' => 'geoip_api_php/');
$autoload_config = array();
require __DIR__.'/_config.php';

if (!extension_loaded('geoip')) {
	require_once $libs_root.'geoip_api_php/src/geoip.inc';
	require_once $libs_root.'geoip_api_php/src/geoipcity.inc';
	require_once $libs_root.'geoip_api_php/src/timezone.php';
}

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

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
