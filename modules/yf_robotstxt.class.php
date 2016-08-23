<?php

class yf_robotstxt {
	function show() {
		header('Content-Type: text/plain', $replace = true);
		$host = $_GET['host'] ?: $_SERVER['HTTP_HOST']; // $_GET['host'] just for debug purposes
		$prod_hosts = main()->PRODUCTION_DOMAIN ?: [];
		if (is_string($prod_hosts)) {
			$prod_hosts = [$prod_hosts];
		}
		if (!main()->is_dev() && in_array($host, $prod_hosts)) {
			// Allow indexing of whole website = production
			$out = 'User-agent: *'.PHP_EOL.'Disallow:';
		} else {
			// Deny indexing of whole website - useful for dev domains
			$out = 'User-agent: *'.PHP_EOL.'Disallow: /';
		}
		print $out;
		exit;
	}
}
