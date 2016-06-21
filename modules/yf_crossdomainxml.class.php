<?php

class yf_crossdomainxml {
	function show() {
		header('Content-Type: text/xml', $replace = true);
		$host = (DEBUG_MODE ? $_GET['host'] : '') ?: $_SERVER['HTTP_HOST']; // $_GET['host'] just for debug purposes
		$prod_hosts = main()->PRODUCTION_DOMAIN ?: parse_url(WEB_PATH, PHP_URL_HOST);
		if (is_string($prod_hosts)) {
			$prod_hosts = [$prod_hosts];
		}
		// Based on example from twitter https://twitter.com/crossdomain.xml
		if (!main()->is_dev() && in_array($host, $prod_hosts)) {
			$out = '<?xml version="1.0" ?>
				<cross-domain-policy xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.adobe.com/xml/schemas/PolicyFile.xsd">
				<allow-access-from domain="'.$host.'"/>
				<allow-access-from domain="api.'.$host.'"/>
				<allow-access-from domain="search.'.$host.'"/>
				<allow-access-from domain="static.'.$host.'"/>
				<site-control permitted-cross-domain-policies="master-only"/>
				<allow-http-request-headers-from domain="*.'.$host.'" headers="*" secure="false"/>
				</cross-domain-policy>
			';
		} else {
			// !!! DO NOT USE THIS FOR PRODUCTON:
			// http://stackoverflow.com/questions/213251/can-someone-post-a-well-formed-crossdomain-xml-sample
			// http://www.hardened-php.net/library/poking_new_holes_with_flash_crossdomain_policy_files.html#badly_configured_crossdomain.xml
			$out = '<?xml version="1.0" ?><cross-domain-policy><allow-access-from domain="*" /></cross-domain-policy>';
		}
		header('Content-Type: text/xml', $replace = true);
		exit;
	}
}
