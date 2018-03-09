<?php

if (!function_exists('apache_request_headers')) {
	// From here: http://php.net/manual/en/function.apache-request-headers.php
	function apache_request_headers() {
		$arh = [];
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = [];
				// do some nasty string manipulations to restore the original letter case. This should work in most cases
				$rx_matches = explode('_', $arh_key);
				if (count((array)$rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val) {
						$rx_matches[$ak_key] = ucfirst($ak_val);
					}
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return ($arh);
	}
}

if (!function_exists('cal_days_in_month')) {
	function cal_days_in_month($calendar, $month, $year) {
		return date('t', mktime(0, 0, 0, $month, 1, $year));
	} 
} 
if (!defined('CAL_GREGORIAN')) {
	define('CAL_GREGORIAN', 1); 
}