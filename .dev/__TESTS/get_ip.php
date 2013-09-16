<?php  


class get_ip_test extends PHPUnit_Framework_TestCase {

	function _get_servers_ips(){
		$ip_list = cache_get('ip_servers_list');
		if(!empty($ip_list)){
			return $ip_list;
		}

		$query = db()->query('SELECT base_ip, ip_aliases  FROM '.db('servers'));
		while($result = db()->fetch_assoc($query)){
			$ip_list[$result['base_ip']] = $result['base_ip'];
			if(!empty($result['ip_aliases'])){
				$aliases = explode("\n", $result['ip_aliases']);
				foreach ((array)$aliases as $item){
					$ip_list[$item] = $item; 
				}
			}
		}
		cache_set('ip_servers_list', $ip_list);
		return $ip_list;
	}


	function _get_ip ($check_type = '') {
		$start_time = microtime(true);
		// Get from cache
		$cache_name ='_CURRENT_'.$check_type.'IP';  
		if (isset($GLOBALS[$cache_name])) {
			return $GLOBALS[$cache_name];
		}

		$ip_storage = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR'
		);

		//ips in array
		$ignore_ips = $this->_get_servers_ips();

		foreach ((array)$ip_storage as $element){
			if (!empty($_SERVER[$element])){
				$current_ip = $this->_check_ip($_SERVER[$element], $ignore_ips, $check_type);
				if($current_ip){
					echo "\n\n".$exec_time = microtime(true) - $start_time."\n\n";
					return $GLOBALS[$cache_name] = $current_ip; 
				}
			}
		}

		// Put into cache
		$GLOBALS[$cache_name] = '';
		echo "\n\n".$exec_time = microtime(true) - $start_time."\n\n";
		return '';
	}

	function _check_ip($ip, $ignore_ips = '', $check_type = ''){
		$masks = array(
			"0.0.0.0/8",		// Current network (only valid as source address)	RFC 1700
			"10.0.0.0/8",		// Private network	RFC 1918
			"127.0.0.0/8",		// Loopback	RFC 3330
			"128.0.0.0/16",		// Reserved (IANA)	RFC 3330
			"169.254.0.0/16",	// Link-Local	RFC 3927
			"172.16.0.0/12",	// Private network	RFC 1918
			"191.255.0.0/16",	// Reserved (IANA)	RFC 3330
			"192.0.0.0/24",		// Reserved (IANA)	RFC 3330
			"192.0.2.0/24",		// Documentation and example code	RFC 3330
			"192.88.99.0/24",	// IPv6 to IPv4 relay	RFC 3068
			"192.168.0.0/16",	// Private network	RFC 1918
			"198.18.0.0/15",	// Network benchmark tests	RFC 2544
			"223.255.255.0/24",	// Reserved (IANA)	RFC 3330
			"224.0.0.0/4",		// Multicasts (former Class D network)	RFC 3171
			"240.0.0.0/4",		// Reserved (former Class E network)	RFC 1700
			"255.255.255.255/",	// Broadcast
		);
		$ip = preg_replace('/[^\d\.]/', ',',  $ip);	
		$ips = explode(",",$ip);
		foreach ((array)$ips as $item){
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
			foreach ((array)$masks as $mask){
				
				//Check ip by masks
				list($net_addr, $net_mask) = explode('/', $mask);
				if($net_mask <= 0){ 
					continue; 
				} 
				if(empty($net_mask) && $item == $net_addr){
					$flag = false;
					break;
				}
				$ip_binary_string = sprintf("%032b",ip2long($item)); 
				$net_binary_string = sprintf("%032b",ip2long($net_addr)); 
				if(substr_compare($ip_binary_string,$net_binary_string,0,$net_mask) === 0){ 
					$flag = false;
				}


			}
			if($flag){
				if(function_exists('geoip_country_code_by_name') && strtoupper($check_type) == 'GEO') {
					$test = @geoip_country_code_by_name($ip);
					echo $item.' - '.$test."\n";
					if($test){
						return trim($ip);
					}
				} else {
					return trim($ip);
				}
			}
		}
		return false;
	}



	public function test_1() {
		$test_array = array(
			'172.19.177.198, 170.51.255.218'	=> '170.51.255.218',
			'172.19.177.198, 10.21.0.218'		=> '',
			'172.44.141.63, 189.204.26.200'		=> '189.204.26.200',
			'100.43.83.158'						=> '100.43.83.158',
			'141.63.172.26, 189.204.26.200'		=> '141.63.172.26',
			'50.23.132.250, % 188.165.140.51  & .19.177.198, 170.51.255.218 170.51.255.217 | 170.51.255.215 5 . %%%234.123.345.234'	=> '170.51.255.218',
			'110.45.192.73,108_168_141_178'		=> '110.45.192.73',
			'118.70.127.134,108.168.141.178'	=> '118.70.127.134',
			'110.45.192.73 | 94.23.1.212'		=> '110.45.192.73',
			'213.186.127.8, 94.23.1.212'		=> '213.186.127.8',
			'213.186.127.10 " remote_addr_ip":"94.23.1.212"'	=> '213.186.127.10',
			'213.186.127.7","emote_addr_ip":"94.23.1.212"'		=> '213.186.127.7',
			'213.86.127.9","remote_addr_ip":"94.23.1.212"'		=> '213.86.127.9',
			'213.86.127.13","remote_addr_ip":"94.23.1.212"'	=> '213.86.127.13',
			'102.219.202.23, 124.81.238.226'	=> '124.81.238.226',
		);
		$ignore_ips = $this->_get_servers_ips();
		foreach ((array)$test_array as $test_ip => $expect_ip){
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $key;
			$this->assertEquals($expect_ip, $this->_check_ip($test_ip, $ignore_ips, 'GEO'));
		}
	}

}
