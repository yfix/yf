<?php

class yf_sitemapxml {
	function show() {
		header('Content-Type: text/xml', $replace = true);
		$host = $_GET['host'] ?: $_SERVER['HTTP_HOST']; // $_GET['host'] just for debug purposes
		$prod_hosts = main()->PRODUCTION_DOMAIN ?: [];
		if (is_string($prod_hosts)) {
			$prod_hosts = [$prod_hosts];
		}
		if (!main()->is_dev() && in_array($host, $prod_hosts)) {
			// Currently we have no sitemap
			// to enable sitemap.xml calls use:
#			return js_redirect(url('/site_map'));
			$_GET['object'] = 'site_map';
			$_GET['action'] = 'show';
			return module_safe('site_map')->show();
		} else {
			// Dev domains always return empty sitemap
			$out = '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>';
		}
		print $out;
		exit;
	}
}
