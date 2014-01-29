<?php

/**
* Some useful utils for the client (browser)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_client_utils {

	/** @conf_skip */
	private $CACHE = array();

	/**
	* Get user IP address
	*/
// TODO: add unit tests for this
	function _get_ip ($check_type = 'force') {
		$cache_name ='_CURRENT_'.$check_type.'IP';  
		if (isset($this->CACHE[$cache_name])) {
			return $this->CACHE[$cache_name];
		}
		$ip_storage = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR'
		);
		$ignore_ips = $this->_get_ignore_ips();
		foreach ((array)$ip_storage as $element) {
			if (!empty($_SERVER[$element])) {
				$current_ip = $this->_check_ip($_SERVER[$element], $ignore_ips, $check_type);
				if ($current_ip) {
					return $this->CACHE[$cache_name] = $current_ip; 
				}
			}
		}
		$ip = '';
		if ($check_type == 'force') {
			$ip = $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1';
		}
		$this->CACHE[$cache_name] = $ip;
		return $ip;
	}

	/**
	* This method can be overriden in project to contain method to list IPs to ignore
	*/
	function _get_ignore_ips () {
		return '';
	}

	/**
	*/
// TODO: add unit tests for this
	function _check_ip($ip, $ignore_ips = '', $check_type = 'force') {
		$masks = array(
			'0.0.0.0/8',		// Current network (only valid as source address)	RFC 1700
			'10.0.0.0/8',		// Private network	RFC 1918
			'127.0.0.0/8',		// Loopback	RFC 3330
			'128.0.0.0/16',		// Reserved (IANA)	RFC 3330
			'169.254.0.0/16',	// Link-Local	RFC 3927
			'172.16.0.0/12',	// Private network	RFC 1918
			'191.255.0.0/16',	// Reserved (IANA)	RFC 3330
			'192.0.0.0/24',		// Reserved (IANA)	RFC 3330
			'192.0.2.0/24',		// Documentation and example code	RFC 3330
			'192.88.99.0/24',	// IPv6 to IPv4 relay	RFC 3068
			'192.168.0.0/16',	// Private network	RFC 1918
			'198.18.0.0/15',	// Network benchmark tests	RFC 2544
			'223.255.255.0/24',	// Reserved (IANA)	RFC 3330
			'224.0.0.0/4',		// Multicasts (former Class D network)	RFC 3171
			'240.0.0.0/4',		// Reserved (former Class E network)	RFC 1700
			'255.255.255.255/',	// Broadcast
		);
		$ip = preg_replace('/[^\d\.]/', ',',  $ip);	
		$ips = explode(',',$ip);
		foreach ((array)$ips as $item) {
			if(empty($item) || (!empty($ignore_ips) && isset($ignore_ips[$item]))){
				continue;
			}
			$flag = false;
			preg_match('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/',$item,$cur_ip);
			$ip = isset($cur_ip[1]) ? $cur_ip[1] : false ;
			if (!$ip) {
				continue;
			}
			$flag = true;
			foreach ((array)$masks as $mask) {
				list($net_addr, $net_mask) = explode('/', $mask);
				if ($net_mask <= 0) {
					continue; 
				} 
				if (empty($net_mask) && $item == $net_addr) {
					$flag = false;
					break;
				}
				$ip_binary_string = sprintf('%032b',ip2long($item)); 
				$net_binary_string = sprintf('%032b',ip2long($net_addr)); 
				if (substr_compare($ip_binary_string,$net_binary_string,0,$net_mask) === 0) {
					$flag = false;
				}
			}
			if ($flag) {
				if(function_exists('geoip_country_code_by_name') && strtoupper($check_type) == 'GEO') {
					$test = @geoip_country_code_by_name($ip);
					if ($test) {
						return trim($ip);
					}
				} else {
					return trim($ip);
				}
			}
		}
		return false;
	}

	/**
	* Get browser detailed info
	*/
// TODO: add unit tests for this
	function _get_browser_info () {
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
		} else if (!isset($HTTP_USER_AGENT)) {
			$HTTP_USER_AGENT = '';
		}
		// 1. Platform
		if (strstr($HTTP_USER_AGENT, 'Win')) {
			$USER_OS = 'Win';
		} else if (strstr($HTTP_USER_AGENT, 'Mac')) {
			$USER_OS = 'Mac';
		} else if (strstr($HTTP_USER_AGENT, 'Linux')) {
			$USER_OS = 'Linux';
		} else if (strstr($HTTP_USER_AGENT, 'Android')) {
			$USER_OS = 'Android';
		} else if (strstr($HTTP_USER_AGENT, 'IOS')) {
			$USER_OS = 'IOS';
		} else if (strstr($HTTP_USER_AGENT, 'Unix')) {
			$USER_OS = 'Unix';
		} else {
			$USER_OS = 'Other';
		}
		// 2. browser and version
		// (must check everything else before Mozilla)
		if (preg_match('@Chrome/([0-9\.]+)@', $HTTP_USER_AGENT, $log_version)) {
			$USER_BROWSER_VER	= $log_version[1] . '.' . $log_version2[1];
			$USER_BROWSER_AGENT	= 'CHROME';
		} elseif (preg_match('@Opera(/| )([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
			$USER_BROWSER_VER	= $log_version[2];
			$USER_BROWSER_AGENT	= 'OPERA';
		} elseif (preg_match('@MSIE ([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
			$USER_BROWSER_VER	= $log_version[1];
			$USER_BROWSER_AGENT	= 'IE';
		} elseif (preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version)) {
			$USER_BROWSER_VER	= $log_version[1];
			$USER_BROWSER_AGENT	= 'SAFARI';
		} elseif (preg_match('@Mozilla/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
			$USER_BROWSER_VER	= $log_version[1];
			$USER_BROWSER_AGENT	= 'FIREFOX';
		} else {
			$USER_BROWSER_VER	= 0;
			$USER_BROWSER_AGENT	= 'OTHER';
		}
		$result = array(
			'USER_OS'			=> $USER_OS,
			'USER_BROWSER_VER'	=> $USER_BROWSER_VER,
			'USER_BROWSER_AGENT'=> $USER_BROWSER_AGENT,
		);
		return $result;
	}
}
