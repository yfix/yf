<?php

/**
* Spider detection code
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_spider_detection {

	/** @var */
	public $not_spiders = array(
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
	);
	/** @var */
	public $well_known_bots = array(
		'googlebot'		=> 'Google',
		'yahooseeker'	=> 'Yahoo',
		'slurp'			=> 'Yahoo',
		'inktomi'		=> 'Yahoo',
		'baiduspider'	=> 'Baidu',
		'yandex/'		=> 'Yandex',
		'bot'			=> 'Some Bot',
		'spider'		=> 'Some Spider',
		'crawler'		=> 'Some Crawler',
	);

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
			// Quick check by not spiders user agents
			foreach ((array)$this->not_spiders as $findme) {
				if (false !== strpos($ua, $findme)) {
					return false;
				}
			}
			foreach ((array)$this->well_known_bots as $findme => $name) {
				if (false !== strpos($ua, $findme)) {
					return $name;
				}
			}

			$this->load_spiders_uas();

			if ($this->_cache_ua) {
				foreach ((array)$this->_cache_ua as $_test_ua => $name) {
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
			$this->load_spiders_ips();

			if ($this->_cache_ip) {
				$ip_tmp = explode('.', $ip);
				$ip_digits_4 = implode('.', $ip_tmp);
				unset($ip_tmp[3]);
				$ip_digits_3 = implode('.', $ip_tmp);
				unset($ip_tmp[2]);
				$ip_digits_2 = implode('.', $ip_tmp);
				// Do check
				if (isset($this->_cache_ip[$ip_digits_4])) {
					return $this->_cache_ip[$ip_digits_4];
				} elseif ($this->_cache_ip[$ip_digits_3]) {
					return $this->_cache_ip[$ip_digits_3];
				} elseif ($this->_cache_ip[$ip_digits_2]) {
					return $this->_cache_ip[$ip_digits_2];
				}
			}
		}
		return '';
	}

	/**
	* Return spiders IPs array
	*/
	function load_spiders_ips () {
		if ($this->_cache_ip) {
			return false;
		}

		$CACHE_CORE_NAME_IP	= 'spiders_ip';
		$this->_cache_ip = cache()->get($CACHE_CORE_NAME_IP);

		foreach ((array)_class('dir')->scan_dir(INCLUDE_PATH. 'share/spiders/', true, '/[a-z\_\-]\.txt$/i') as $path) {
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
				} elseif ($name) {
					$this->_cache_ip[$line] = $name;
				}
			}
		}
		cache()->put($CACHE_CORE_NAME_IP, $this->_cache_ip);
	}

	/**
	* Return spiders UAs array
	*/
	function load_spiders_uas () {
		if ($this->_cache_ua) {
			return false;
		}

		$CACHE_CORE_NAME_UA	= 'spiders_ua';
		$this->_cache_ua = cache()->get($CACHE_CORE_NAME_UA);

		foreach ((array)_class('dir')->scan_dir(INCLUDE_PATH. 'share/spiders/', true, '/[a-z\_\-]\.txt$/i') as $path) {
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
						$this->_cache_ua[$_cache_ua] = $name;
					}
				}
			}
		}
		cache()->put($CACHE_CORE_NAME_UA, $this->_cache_ua);
	}

	/**
	* Return SQL part for detecting search engine ips
	*/
	function get_spiders_ips_sql ($field_name = 'ip') {
		if (!$field_name) {
			$field_name = 'ip';
		}
		$this->load_spiders_ips();

		$sql = '';
		$full_ips = array();
		$ips_without_1_dot = array();
		foreach ((array)$this->_cache_spiders as $ip => $_s_name) {
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
			$tmp = array();
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
