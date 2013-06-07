<?php

/**
*/
class grabber {

	var $NUM_THREADS = 10;
//	var $ANONYMIZER = "http://anonymouse.org/cgi-bin/anon-www.cgi/";
	var $PROXY_IP	= "";
	var $PROXY_PORT = "";
	var $_proxies = array();

	/**
	*/
	function _init() {
		set_time_limit(86400);
		ignore_user_abort(true);
		$this->TMP_DIR = INCLUDE_PATH."_grabber_tmp/";
		_mkdir_m($this->TMP_DIR);

		$this->_load_proxies();
	}

	/**
	*/
	function show() {
		$this->_get_items();
//		$this->_get_num_pages();
	}

	/**
	*/
	function _load_proxies() {
		$proxy_list_path = INCLUDE_PATH. "proxy_list.txt";
		if (file_exists($proxy_list_path)) {
			$text = file_get_contents($proxy_list_path);
			$text = preg_replace("/[^0-9\.\:\n]/ims", "", str_replace("\r", "\n", $text));
		}
		foreach (explode("\n", $text) as $_line) {
			$_line = trim($_line);
			if (!strlen($_line)) {
				continue;
			}
			list($_tmp_ip, $_tmp_port) = explode(":", $_line);
			if (!strlen($_tmp_ip) || !strlen($_tmp_port)) {
				continue;
			}
			$this->_proxies[$_tmp_ip] = $_tmp_port;
		}
	}

	/**
	*/
	function _get_items() {
		$URL_TPL	= $this->ANONYMIZER. "http://www.numberingplans.com/?page=plans&sub=phonenr&alpha_2_input={COUNTRY}&current_page={PAGE}";
		$_URL_CUT	= substr($URL_TPL, 0, strpos($URL_TPL, "{"));
		$REGEX		= "/<td class=\"basic\-text\" [^>]+>([^<]+?)<\/b><\/td>[\s\n\t]+<td class=\"basic\-text\"[^>]+?>([^<]+?)<\/td>[\s\n\t]+<td class=\"basic\-text\"[^>]+?>([^<]+?)<\/td>/ims";
		$REPLACE	= array(
			"&nbsp;"=> " ",
			"\r"	=> " ",
			"\n"	=> " ",
		);

		$COUNTRIES = $GLOBALS['main']->get_data("countries");

		$last_country_name = end($COUNTRIES);
		reset($COUNTRIES);

		$NUM_PAGES = $GLOBALS['main']->get_data("num_pages");

		$num_errors = 0;
		$NUM_ERRORS_STOP = 5;

		foreach ((array)$COUNTRIES as $cc => $_country_name) {
			$CUR_NUM_PAGES = $NUM_PAGES[$cc];
			if (!$CUR_NUM_PAGES) {
				$CUR_NUM_PAGES = 10;
			}
			for ($i = 1; $i <= $CUR_NUM_PAGES; $i++) {
				$url = str_replace(array("{COUNTRY}", "{PAGE}"), array($cc, $i), $URL_TPL);
				$cutted_url = str_replace($_URL_CUT, "", $url);
				list($_is_visited) = $GLOBALS['db']->query_fetch("SELECT `id` AS `0` FROM `".dbt_visited_urls."` WHERE `id`='".$GLOBALS['db']->es($cutted_url)."'");
				if ($_is_visited) {
					continue;
				}
				// Fill buffer with urls to get
				if (count($buffer) < $this->NUM_THREADS && $i < $CUR_NUM_PAGES) {
					$buffer[$cutted_url] = $url;
					continue;
				}
				// GO!
				$result = $this->_multi_request($buffer);

				foreach ((array)$result as $_url => $text) {
					$_cutted_url = str_replace($_URL_CUT, "", $_url);
					// Save temp name (useful for debugging)
					if (!strlen($text)) {
						continue;
					}
					$found = preg_match_all($REGEX, $text, $m);
					if (!$found) {
						$num_errors++;
						if ($num_errors >= $NUM_ERRORS_STOP) {
							$_new_proxy = each($this->_proxies);
							// Try other proxy if we have several failed requests
							if ($_new_proxy) {
								$this->PROXY_IP		= $_new_proxy["key"];
								$this->PROXY_PORT	= $_new_proxy["value"];
								$num_errors = 0;
							} else {
								exit();
							}
						}
//						sleep(1);
//						continue;
					}

					file_put_contents($this->TMP_DIR. $_cutted_url.".html", $text);

					$GLOBALS['db']->INSERT("visited_urls", array(
						"id"	=> $GLOBALS['db']->es($_cutted_url),
						"date"	=> time(),
						"found"	=> intval((bool)$found),
					));

					foreach ((array)$m[0] as $_id => $_v) {
						$phone	= str_replace(array_keys($REPLACE), array_values($REPLACE), $m[1][$_id]);
						$usage	= $m[2][$_id];
						$info	= $m[3][$_id];
				  
						$GLOBALS['db']->INSERT("plans", array(
							"phone"	=> $GLOBALS['db']->es($phone),
							"cc"	=> $GLOBALS['db']->es($cc),
							"usage"	=> $GLOBALS['db']->es($usage),
							"info"	=> $GLOBALS['db']->es($info),
						));
					}
				}
				$buffer = array();
			}
		}
	}

	/**
	*/
	function _get_num_pages() {
		$URL_TPL	= $this->ANONYMIZER. "http://www.numberingplans.com/?page=plans&sub=phonenr&alpha_2_input={COUNTRY}";
		$_URL_CUT	= substr($URL_TPL, 0, strpos($URL_TPL, "{"));
		$REGEX = "/\?page=plans&sub=phonenr&alpha_2_input=[a-z]{2}&current_page=([0-9]+?)\">last/ims";

		$counter = 0;
		$fh = fopen(INCLUDE_PATH. "num_pages.txt", "w+");

		$buffer = array();
		$result = array();

		$COUNTRIES = $GLOBALS['main']->get_data("countries");
		$last_country_name = end($COUNTRIES);
		
		foreach ((array)$COUNTRIES as $cc => $_country_name) {
			$url = str_replace("{COUNTRY}", $cc, $URL_TPL);
			// Fill buffer with urls to get
			if (count($buffer) < $this->NUM_THREADS && $_country_name != $last_country_name) {
				$buffer[$url] = $url;
				continue;
			}
			$result = $this->_multi_request($buffer);
			foreach ((array)$result as $_url => $text) {
				if (!strlen($text)) {
					continue;
				}
				// Save temp name (useful for debugging)
				file_put_contents($this->TMP_DIR. str_replace($_URL_CUT, "", $_url).".html", $text);

				if (preg_match($REGEX, $text, $m)) {
					$num_pages[$cc] = $m[1];
					fwrite($fh, "\"".substr(str_replace($_URL_CUT, "", $_url), 0, 2)."\" => '".$m[1]."',\n");
				}
			}
			$buffer = array();
		}
		fclose($fh);

		print_R($num_pages);
	}

	/**
	*/
	function _get_remote_page($page_url = "") {
		if (empty($page_url)) {
			return false;
		}
		$page_to_check = "";
		$page_url	= str_replace(" ", "%20", trim($page_url));
		$user_agent = "Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 5.1)";
		$referer	= $page_url;
		if ($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $page_url);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_REFERER, $referer);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TTIMEOUT, 		30);

			if ($this->PROXY_IP) {
				curl_setopt($ch, CURLOPT_PROXY, $this->PROXY_IP);
				if ($this->PROXY_PORT) {
					curl_setopt($ch, CURLOPT_PROXYPORT, $this->PROXY_PORT);
				}
			}
			$page_to_check = curl_exec($ch);
			curl_close ($ch);
		}
		return $page_to_check;
	}

	/**
	*/
	function _multi_request($data, $options = array()) {

	  // array of curl handles
	  $curly = array();
	  // data to be returned
	  $result = array();

	  // multi handle
	  $mh = curl_multi_init();

	  // loop through $data and create curl handles
	  // then add them to the multi-handle
	  foreach ((array)$data as $id => $d) {

		$curly[$id] = curl_init();

		$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
		curl_setopt($curly[$id], CURLOPT_URL,			$url);
		curl_setopt($curly[$id], CURLOPT_HEADER,		 0);
		curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curly[$id], CURLOPT_TTIMEOUT, 		30);

		if ($this->_cookie) {
			curl_setopt($curly[$id], CURLOPT_COOKIE, $this->_cookie);
		}
		if ($this->PROXY_IP) {
			curl_setopt($curly[$id], CURLOPT_PROXY, $this->PROXY_IP);
			if ($this->PROXY_PORT) {
				curl_setopt($curly[$id], CURLOPT_PROXYPORT, $this->PROXY_PORT);
			}
		}

		// post?
		if (is_array($d)) {
		  if (!empty($d['post'])) {
			curl_setopt($curly[$id], CURLOPT_POST,	   1);
			curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
		  }
		}

		// extra options?
		if (!empty($options)) {
		  curl_setopt_array($curly[$id], $options);
		}

		curl_multi_add_handle($mh, $curly[$id]);
	  }

	  // execute the handles
	  $running = null;
	  do {
		curl_multi_exec($mh, $running);
	  } while($running > 0);

	  // get content and remove handles
	  foreach ((array)$curly as $id => $c) {
		$result[$id] = curl_multi_getcontent($c);
		curl_multi_remove_handle($mh, $c);
	  }

	  // all done
	  curl_multi_close($mh);

	  return $result;
	}
}
?>