<?php

/**
* Spider detection code
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_spider_detection {

	/** @const */
	const data_dir = 'share/spiders/';
	/** @var */
	public $well_known_bots = [
		'googlebot'				=> 'Google',
		'google.com/bot'		=> 'Google',
		'mediapartners-google'	=> 'Google',
		'adsbot-google' 		=> 'Google',
		'yahooseeker'			=> 'Yahoo',
		'slurp'					=> 'Yahoo',
		'inktomi'				=> 'Yahoo',
		'baiduspider'			=> 'Baidu',
		'yandex.com/bots'		=> 'Yandex',
		'bingbot'				=> 'Bing',
		'msnbot'				=> 'Bing',
		'bingpreview'			=> 'Bing',
		'facebookexternalhit'	=> 'Facebook',
		'vkshare'				=> 'VK',
		'pingdom'				=> 'Pingdom',
		'http://'				=> 'Bot', // Examples: http://Anonymouse.org/ (Unix) | Mozilla/5.0 (compatible; vkShare; +http://vk.com/dev/Share)
		'curl'					=> 'Curl',
		'wget'					=> 'Wget',
		'bot'					=> 'Bot',
		'spider'				=> 'Spider',
		'crawler'				=> 'Crawler',
		'http-java-client'		=> 'Bot',
		'python-urllib'			=> 'Bot',
	];
	/** @var */
	public $browsers_keywords = [
		'opera',
		'presto',
		'gecko',
		'firefox',
		'msie',
		'trident',
		'windows',
		'webkit',
		'chrome',
		'safari',
	];

	/**
	* new method checking for spider by ip address (database from http://www.iplists.com/)
	*/
	function _is_spider ($ip = '', $ua = '') {
		$CHECK_IP = false;
		$CHECK_UA = false;
		if ($ip && preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip)) {
			$CHECK_IP = true;
		}
		if (strlen($ua)) {
			$ua = strtolower($ua);
			$CHECK_UA = true;
		}
		if (!$CHECK_IP && !$CHECK_UA) {
			return false;
		}
		// try by user agent strings
		if ($CHECK_UA) {
			foreach ((array)$this->well_known_bots as $findme => $name) {
				if (false !== strpos($ua, $findme)) {
					return $name;
				}
			}
#			foreach ((array)$this->browsers_keywords as $findme) {
#				if (false !== strpos($ua, $findme)) {
#					return false;
#				}
#			}
			if ($uas = $this->load_spiders_uas()) {
				foreach ((array)$uas as $_test_ua => $name) {
					if (!$_test_ua) {
						continue;
					}
					if (false !== strpos($_test_ua, $ua)) {
						return $name;
					}
				}
			}
		}
		if ($CHECK_IP) {
			if ($ips = $this->load_spiders_ips()) {
				$ip_tmp = explode('.', $ip);
				$ip_digits_4 = implode('.', $ip_tmp);
				unset($ip_tmp[3]);
				$ip_digits_3 = implode('.', $ip_tmp);
				unset($ip_tmp[2]);
				$ip_digits_2 = implode('.', $ip_tmp);

				if (isset($ips[$ip_digits_4])) {
					return $ips[$ip_digits_4];
				} elseif (isset($ips[$ip_digits_3])) {
					return $ips[$ip_digits_3];
				} elseif (isset($ips[$ip_digits_2])) {
					return $ips[$ip_digits_2];
				}
			}
		}
		return false;
	}

	/**
	* Return spiders IPs array
	*/
	function load_spiders_ips () {
		$cache_name	= 'spiders_ip';
		if (isset($this->_cache[$cache_name])) {
			return $this->_cache[$cache_name];
		}
		if ($this->_cache[$cache_name] = cache_get($cache_name)) {
			return $this->_cache[$cache_name];
		}
		$ext = '.txt';
		$ext_len = strlen($ext);
		$globs = [
			'app'		=> APP_PATH. self::data_dir. '*'. $ext,
			'project'	=> PROJECT_PATH. self::data_dir. '*'. $ext,
			'yf'		=> YF_PATH. self::data_dir. '*'. $ext,
		];
		$paths = [];
		foreach ((array)$globs as $glob) {
			foreach (glob($glob) as $path) {
				$paths[] = $path;
			}
		}
		$data = [];
		foreach ((array)$paths as $path) {
			if (!$path) {
				continue;
			}
			$name = substr(basename($path), 0, -$ext_len);
			$tmp = file($path);
			$name = '';
			foreach ((array)$tmp as $line) {
				$line = trim($line);
				if (!strlen($line)) {
					// Clean spider name
					$name = '';
					continue;
				}
				if ($line[0] === '#') {
					// Assign spider name
				} elseif ($name) {
					$data[$line] = $name;
				}
			}
		}
		$prev_cached_data = $this->_cache[$cache_name];
		if ($data && !is_array($prev_cached_data)) {
			cache_set($cache_name, $data);
			$this->_cache[$cache_name] = $data;
		}
		return $data;
	}

	/**
	* Return spiders UAs array
	*/
	function load_spiders_uas () {
		$cache_name	= 'spiders_ua';
		if (isset($this->_cache[$cache_name])) {
			return $this->_cache[$cache_name];
		}
		if ($this->_cache[$cache_name] = cache_get($cache_name)) {
			return $this->_cache[$cache_name];
		}
		$ext = '.txt';
		$ext_len = strlen($ext);
		$globs = [
			'app'		=> APP_PATH. self::data_dir. '*'. $ext,
			'project'	=> PROJECT_PATH. self::data_dir. '*'. $ext,
			'yf'		=> YF_PATH. self::data_dir. '*'. $ext,
		];
		$paths = [];
		foreach ((array)$globs as $glob) {
			foreach (glob($glob) as $path) {
				$paths[] = $path;
			}
		}
		$data = [];
		foreach ((array)$paths as $path) {
			if (!$path) {
				continue;
			}
			$name = substr(basename($path), 0, -strlen('.txt'));
			$tmp = file($path);
			$name = '';
			foreach ((array)$tmp as $line) {
				$line = trim($line);
				if (!strlen($line)) {
					// Clean spider name
					$name = '';
					continue;
				}
				if ($line[0] == '#') {
					// Assign spider name
					if (!$name) {
						$name = substr($line, 2);
					} elseif (substr($line, 0, 5) == '# UA ') {
						$_cache_ua = trim(strtolower(substr($line, 5)), "\"'");
						$data[$_cache_ua] = $name;
					}
				}
			}
		}
		$prev_cached_data = $this->_cache[$cache_name];
		if ($data && !is_array($prev_cached_data)) {
			cache_set($cache_name, $data);
			$this->_cache[$cache_name] = $data;
		}
		return $data;
	}

	/**
	* Return SQL part for detecting search engine ips
	*/
	function get_spiders_ips_sql ($field_name = 'ip') {
		if (!$field_name) {
			$field_name = 'ip';
		}
		$spiders_ips = $this->load_spiders_ips();
		if (!$spiders_ips) {
			return '';
		}

		$sql = '';
		$full_ips = [];
		$ips_without_1_dot = [];
		foreach ((array)$spiders_ips as $ip => $_s_name) {
			$dots = substr_count($ip, '.');
			if ($dots == 3) {
				$full_ips[$ip] = $ip;
			} elseif ($dots == 2) {
				$ips_without_1_dot[$ip] = $ip;
			}
		}
		if ($full_ips) {
			$sql .= ' '.$field_name." IN('".implode("','", $full_ips)."')\n ";
		}
		if ($ips_without_1_dot) {
			$sql .= ($sql ? ' OR ' : '').' REVERSE(SUBSTRING(REVERSE('.$field_name."), LOCATE('.', REVERSE(".$field_name.")) + 1)) IN('".implode("','", $ips_without_1_dot)."')\n";
		}
		return $sql;
	}

	/**
	* Searches given URL for known search engines hosts
	* @return string name of the found search engine
	*/
	function is_search_engine_url ($url = '') {
		$url = trim($url);
		if (!strlen($url)) {
			return false;
		}
		$host = parse_url($url, PHP_URL_HOST);
		$host = trim($host);
		if (substr($host, 0, 4) == 'www.') {
			$host = substr($host, 4);
		}
		if (!strlen($host)) {
			return false;
		}
		// Prepare search engines list
		if (!isset($this->_cache_se_hosts)) {
			$tmp = [];
			foreach (main()->get_data('search_engines') as $A) {
				$_host = trim($A['search_url']);
				if (substr($_host, 0, 4) == 'www.') {
					$_host = substr($_host, 4);
				}
				if (strlen($_host)) {
					$tmp[$_host] = $A['name'];
				}
			}
			$this->_cache_se_hosts = $tmp;
			unset($tmp);
		}
		if (isset($this->_cache_se_hosts[$host])) {
			return $this->_cache_se_hosts[$host];
		}
		return false;
	}
}
