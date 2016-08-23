#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/serbanghita/Mobile-Detect.git' => 'mobile_detect/'],
	'require_once' => ['mobile_detect/Mobile_Detect.php'],
	'example' => function() {
		$ua = 'mozilla/5.0 (x11; linux x86_64) applewebkit/537.36 (khtml, like gecko) chrome/40.0.2214.115 safari/537.36';
		$detect = new Mobile_Detect([], $ua);
		var_dump($ua);
		echo 'Is mobile: '.(int)$detect->isMobile(). PHP_EOL;

		$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B440 Safari/600.1.4';
		$detect = new Mobile_Detect([], $ua);
		var_dump($ua);
		echo 'Is mobile: '.(int)$detect->isMobile(). PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
