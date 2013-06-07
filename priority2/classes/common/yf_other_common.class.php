<?php

/**
* Other common methods container
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_other_common {

	/**
	* Get file using HTTP request (grabbed from drupal 5.1)
	*/
	function get_whois_info ($url, $server = "") {
		if (empty($url)) {
			return false;
		}
		$tmp = parse_url(trim($url));
		if (empty($tmp["host"])) {
			return false;
		}
		$url = trim($tmp["host"]);
		if (substr($url, 0, 4) == "www.") {
			$url = substr($url, 4);
		}

		require_once "Net/Whois.php";
		$whois = new Net_Whois;

		$data = $whois->query($url/*, !empty($server) ? $server : null*/);

		// Cut non-needed info
		$data = preg_replace("/NOTICE: The expiration date .+?(Registrant:)/ims", "\\1", $data, 1); 
		$data = preg_replace("/.+?(   Domain Name: )/ims", "\\1", $data, 1);
		$data = preg_replace("/([ ]{2,})/i", " ", str_replace(array("\t","\r"), array(" ","\n"), trim($data)));
		$data = preg_replace("/([\n]{2,})/i", "\n", $data);
		
		return $data;
	}

	/**
	* Get geo info by IP from db
	*/
	function _get_geo_data_from_db ($cur_ip = "") {
		$cur_ip = trim(array_pop(explode(",",preg_replace("/[^0-9\.,]/i", "", $cur_ip))));
		if (empty($cur_ip)) {
			return false;
		}
		if ($this->_is_ip_to_skip($cur_ip)) {
			return false;
		}
		$STORE_UNKNOWN_IPS = true;
		// Also check if IP is not recognized by our system and skip it
		if ($STORE_UNKNOWN_IPS && db()->query_num_rows(
			"SELECT * FROM `".db('geo_skip_ip')."` WHERE `ip` = INET_ATON('"._es($cur_ip)."')"
		)) {
			return false;
		}
		// Prepare query
		$sql = 
			"SELECT * 
			FROM `".db('geo_city_location')."` 
			WHERE `loc_id` = ( 
				SELECT `loc_id` FROM `".db('geo_city_blocks')."`
				WHERE `start_ip` <= INET_ATON('"._es($cur_ip)."') 
					AND `end_ip` >= INET_ATON('"._es($cur_ip)."') 
				LIMIT 1 
			)";
		$A = db()->query_fetch($sql);
		if (empty($A)) {
			if ($STORE_UNKNOWN_IPS) {
				db()->query(
					"INSERT INTO `".db('geo_skip_ip')."` (
						`ip`, `hits`
					) VALUES (
						INET_ATON('"._es($cur_ip)."'), 1
					) ON DUPLICATE KEY UPDATE `hits` = `hits` + 1"
				);
			}
			return false;
		}
		$geo_data = array(
			"country_code"	=> $A["country"],
			"country_name"	=> _country_name($A["country"]),
			"region_code"	=> $A["region"],
			"city_name"		=> $A["city"],
			"dma_code"		=> $A["dma_code"],
			"area_code"		=> $A["area_code"],
			"longitude"		=> $A["longitude"],
			"latitude"		=> $A["latitude"],
		);
		return $geo_data;
	}

	/**
	* Get geo info by IP from db
	*/
	function _is_ip_to_skip ($cur_ip = "") {
		// Taken from http://en.wikipedia.org/wiki/IPv4
		$ips_to_exclude = array(
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
			"255.255.255.255",	// Broadcast
		);
		foreach ((array)$ips_to_exclude as $_cur_cidr) {
			if (common()->_is_ip_in_cidr($cur_ip, $_cur_cidr)) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check if given IP matches given CIDR
	*/
	function _is_ip_in_cidr($iptocheck, $CIDR) {
		// get the base and the bits from the ban in the database
		list($base, $bits) = explode('/', $CIDR);
		// now split it up into it's classes
		list($a, $b, $c, $d) = explode('.', $base);
		// now do some bit shfiting/switching to convert to ints
		$i = ($a << 24) + ($b << 16) + ($c << 8) + $d;
		$mask = $bits == 0 ? 0 : (~0 << (32 - $bits));
		// here's our lowest int
		$low = $i & $mask;
		// here's our highest int
		$high = $i | (~$mask & 0xFFFFFFFF);
		// now split the ip were checking against up into classes
		list($a, $b, $c, $d) = explode('.', $iptocheck);
		// now convert the ip we're checking against to an int
		$check = ($a << 24) + ($b << 16) + ($c << 8) + $d;
		// if the ip is within the range, including highest/lowest values, then it's witin the CIDR range
		if ($check >= $low && $check <= $high) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	* Adaptively split large text into smaller parts by token with part size limit
	*/
	function _my_split ($text = "", $split_token = "", $split_length = 0) {
		if (!strlen($split_token) || !$split_length) {
			return array($text);
		}
		$splitted = array();

		$text_length	= strlen($text);
		$num_parts		= ceil($text_length / $split_length);
		$token_length	= strlen($_split_token);

		for ($i = 0; $i < $num_parts; $i++) {
			$_start_offset	= $i * $split_length + $token_length;
			$offsets[$i]	= strpos($text, $split_token, $_start_offset);
		}
		$offsets[$num_parts]	= $text_length;

		foreach ((array)$offsets as $_num => $_offset) {
			$_prev_offset		= $_num ? $offsets[$_num - 1] : 0;
			$splitted[$_num]	= substr($text, $_prev_offset, $_offset - $_prev_offset + $token_length);
		}
		return $splitted;
	}

	/**
	* Creates tags cloud
	* //$cloud_data - array like (key => array(text, num))
	* $cloud_data - array like (text => num)
	*/
	function _create_cloud($cloud_data = array(), $params = array()) {
		
		if (empty($cloud_data)) {
			return "";
		}

		if (empty($params["object"])) {
			$params["object"] = "tags";
		}
		if (empty($params["action"])) {
			$params["action"] = "search";
		}

		if (common()->CLOUD_ORDER == "text") {
			ksort($cloud_data);
		} elseif (common()->CLOUD_ORDER == "num") {
			arsort($cloud_data);
		}

		//Search for the max and min values of 'num' in array	
		$max_val = max($cloud_data);
		$min_val = min($cloud_data);

		foreach ((array)$cloud_data as $_text => $_num) {
			// Creating cloud
			if ($max_val !== $min_val) {
				$_cloud_fsize = common()->CLOUD_MIN_FSIZE + (
					(common()->CLOUD_MAX_FSIZE - common()->CLOUD_MIN_FSIZE)
					* ($_num - $min_val)
					/ ($max_val - $min_val)
				);
				$_cloud_fsize = round($_cloud_fsize, 2);
			} else {
				$_cloud_fsize = 1;
			}
			$replace2 = array(
				"num"			=> $_num,
				"tag_text"		=> $_text,
				"tag_search_url"=> "./?object=".$params["object"]."&action=".$params["action"]."&id=".$params["id_prefix"].($params["amp_encode"] ? str_replace(urlencode("&"), urlencode(urlencode("&")), urlencode($_text)) : urlencode($_text)),
				"cloud_fsize"	=> $_cloud_fsize,
			);
			$items .= tpl()->parse("tags/cloud_item", $replace2);
		}
		return $items;
	}	

	/**
	* Creates a relative path version of a file or directiroy name,
	* given a directory that it will be relative to.
	*
	* @access	public
	* @param string	$target		a file or directory name which will be made relative
	* @param string	$fromdir	the directory which the returned path is relative to
	* @return string				result relative path
	*/
	function get_relative_path($target, $fromdir) {
		// Check that the fromdir has a trailing slash, otherwise realpath will
		// strip the last directory name off
		if (($fromdir[strlen($fromdir) - 1] != "\\") && ($fromdir[strlen($fromdir) - 1] != "/")) {
			$fromdir .= "/";
		}
		// get a real directory name for each of the target and from directory
		$from	= realpath($fromdir);
		$target	= realpath($target);
		$to		= dirname($target );
		// Can't get relative path with drive in path - remove it
		if (($colonpos = strpos($target, ":")) != false) {
			$target = substr($target, $colonpos + 1);
		}
		if (($colonpos = strpos($from, ":")) != false) {
			$from = substr($from, $colonpos + 1);
		}
		if (($colonpos = strpos($to, ":")) != false) {
			$to = substr($to, $colonpos + 1);
		}
		$path = "../";
		$posval = 0;
		// Step through the paths until a difference is found (ignore slash, backslash differences
		// or the end of one is found
		while ((($from[$posval] == $to[$posval])
			|| (($from[$posval] == "\\") && ($to[$posval] == "/"))
			|| (($from[$posval] == "/") && ($to[$posval] == "\\")))
			&& ($from[$posval] && $to[$posval])
		) {
			$posval++;
		}
		// Save the position of the first difference
		$diffpos = $posval;
		// Check if the directories are the same or
		// the if target is in a subdirectory of the fromdir
		if ((!$from[$posval]) && ($to[$posval] == "/" || $to[$posval] == "\\" || !$to[$posval])) {
			// target is in fromdir or a subdirectory
			// Build relative path starting with a ./
			return ("./" . substr($target, $posval+1, strlen($target)));
		} else {
			// target is outside the fromdir branch
			// find out how many "../"'s are necessary
			// Step through the fromdir path, checking for slashes
			// each slash encountered requires a "../"
			while ($from[++$posval]) {
				// Check for slash
				if (($from[$posval] == "/") || ($from[$posval] == "\\")) {
					// Found a slash, add a "../"
					$path .= "../";
				}
			}
			// Search backwards to find where the first common directory
			// as some letters in the first different directory names
			// may have been the same
			$diffpos--;
			while (($to[$diffpos] != "/") && ($to[$diffpos] != "\\") && $to[$diffpos]) {
				$diffpos--;
			}
			// Build relative path to return
			return ($path . substr($target, $diffpos + 1, strlen($target)));
		}
	}

	/**
	* Parse given text using "jevix" lib
	*/
	function jevix_parse ($text = "", $params = array()) {
		// Initialize jevix
		if (!isset($this->JEVIX)) {
			$this->JEVIX = false;

			require(YF_PATH. "libs/jevix/jevix.class.php");

			if (class_exists("Jevix")) {
				$this->JEVIX = new Jevix();
			}
		}
		if (!is_object($this->JEVIX)) {
			trigger_error("COMMON: Jevix lib init fails", E_USER_WARNING);
			return $text;
		}
		// next param
		$this->JEVIX->cfgAllowTags(
			isset($params["allow_tags"]) ? $params["allow_tags"] : 
			array('a', 'img', 'i', 'b', 'u', 'em', 'strong', 'nobr', 'li', 'ol', 'ul', 'sup', 'abbr', 'pre', 'acronym', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'adabracut', 'br', 'code')
		);
		// next param
		$this->JEVIX->cfgSetTagShort(
			isset($params["tag_short"]) ? $params["tag_short"] : 
			array('br','img')
		);
		// next param
		$this->JEVIX->cfgSetTagPreformatted(
			isset($params["tag_pre"]) ? $params["tag_pre"] : 
			array('pre')
		);
		// next param
		$this->JEVIX->cfgSetTagCutWithContent(
			isset($params["cut_with_content"]) ? $params["cut_with_content"] : 
			array('script', 'object', 'iframe', 'style')
		);
		// next param
		$this->JEVIX->cfgSetXHTMLMode(
			isset($params["xhtml_mode"]) ? $params["xhtml_mode"] : 
			true
		);
		// next param
		$this->JEVIX->cfgSetAutoBrMode(
			isset($params["auto_br_mode"]) ? $params["auto_br_mode"] : 
			true
		);
		// next param
		$this->JEVIX->cfgSetAutoLinkMode(
			isset($params["auto_link_mode"]) ? $params["auto_link_mode"] : 
			true
		);
		// next param
		$this->JEVIX->cfgSetTagNoTypography(
			isset($params["tag_to_typography"]) ? $params["tag_to_typography"] : 
			'code'
		);
		// next param
		isset($params["allow_tag_params"]) ? "" : $params["allow_tag_params"] = 
			array(
				'a'		=> array('title', 'href'),
				'img'	=> array('src', 'alt' => '#text', 'title', 'align' => array('right', 'left', 'center'), 'width' => '#int', 'height' => '#int', 'hspace' => '#int', 'vspace' => '#int'),
			);
		foreach ((array)$params["allow_tag_params"] as $k => $v) {
			$this->JEVIX->cfgAllowTagParams($k, $v);
		}
		// next param
		isset($params["tag_params_required"]) ? "" : $params["tag_params_required"] = 
			array(
				'img'	=> 'src',
				'a'		=> 'href',
			);
		foreach ((array)$params["tag_params_required"] as $k => $v) {
			$this->JEVIX->cfgSetTagParamsRequired($k, $v);
		}
		// next param
		isset($params["tag_childs"]) ? "" : $params["tag_childs"] = 
			array(
				'ul'	=> 'li',
			);
		foreach ((array)$params["tag_childs"] as $k => $v) {
			$this->JEVIX->cfgSetTagChilds($k, $v, true, true);
		}
		// next param
		isset($params["tag_params_auto_add"]) ? "" : $params["tag_params_auto_add"] = 
			array(
				'a'		=> array('rel' => 'nofollow'),
				'img'	=> array('width' => '300', 'height' => '300'),
			);
		foreach ((array)$params["tag_params_auto_add"] as $k => $v) {
			$this->JEVIX->cfgSetTagParamsAutoAdd($k, $v);
		}
		// next param
		isset($params["auto_replace"]) ? "" : $params["auto_replace"] = 
			array(
				'+/-'	=> '±',
				'(c)'	=> '©',
				'(r)'	=> '®',
			);
		foreach ((array)$params["auto_replace"] as $k => $v) {
			$this->JEVIX->cfgSetAutoReplace($k, $v);
		}
		// Go with parsing
		$errors = null;
		$res = $this->JEVIX->parse($text, $this->JEVIX_ERRORS);

//		print_r($this->JEVIX_ERRORS);
		return $res;
	}

	/**
	* Get country by IP address using maxmind API (http://geolite.maxmind.com/download/geoip/api/php/)
	* @return 2-byte $country_code (uppercased) or false if something wrong
	*/
	function _get_country_by_ip ($ip = "") {
		if (!$ip) {
			return false;
		}
		if (!isset($this->_geoip_obj)) {
			$this->_geoip_obj = false;
			$db_path = INCLUDE_PATH."share/geo_ip_binary_db.dat";
			if (!file_exists($db_path)) {
				return false;
			}
			$lib_path = YF_PATH."libs/geoip/geoip.inc";
			if (!file_exists($lib_path)) {
				return false;
			}
			include_once($lib_path);
			if (function_exists("geoip_open")) {
				$this->_geoip_obj = geoip_open($db_path, GEOIP_MEMORY_CACHE);
			}
		}
		if ($this->_geoip_obj === false) {
			return false;
		}
		$country_code = geoip_country_code_by_addr($this->_geoip_obj, $ip);
		return strtoupper($country_code);
	}

	/**
	* Converter between well-known currencies
	*/
	function _currency_convert ($number = 0, $c_from = "", $c_to = "") {
		if (!$number || !$c_from || !$c_to) {
			return $number;
		}
		$c_from	= strtoupper($c_from);
		$c_to	= strtoupper($c_to);
		if ($c_from == $c_to) {
			return $number;
		}
		$allowed_currencies = array(
			"GBP", "USD", "RUB", "UAH", "MXN", "ARS", "CHF", "CAD"
		);
		$cache_name = "currency_rates";
		if (main()->USE_SYSTEM_CACHE) {
			$rates = main()->get_data($cache_name);
		}
		if (empty($rates)) {
			foreach ((array)$allowed_currencies as $currency) {
				if ($currency == "EUR") {
					continue;
				}
				$cur_for_url[$currency] = "EUR".$currency."=X";
			}
			// Example: http://download.finance.yahoo.com/d/quotes.json?s=EURUSD=X,GBPUSD=X,GBPEUR=X,GBPrub=X&f=sl1
			$url = "http://download.finance.yahoo.com/d/quotes.json?s=".implode(",", $cur_for_url)."&f=sl1";
			$data = common()->get_remote_page($url);
			preg_match_all("#\"EUR(?P<cur>[a-z]{3})=X\",(?P<rate>[0-9\.]+)#ims", $data, $m);
			foreach ((array)$m["cur"] as $i => $currency) {
				$currency = trim(strtoupper($currency));
				$rate = floatval($m["rate"][$i]);
				$eur_rates[$currency] = $rate;
				$rates["EUR"][$currency] = $eur_rates[$currency];
			}
			foreach ((array)array_keys($eur_rates) as $currency) {
				$c_to_eur	= $currency == "EUR" ? 1 : $eur_rates[$currency];
				foreach ((array)array_keys($eur_rates) as $currency2) {
					if ($currency2 == $currency) {
						continue;
					}
					$c2_to_eur	= $currency2 == "EUR" ? 1 : $eur_rates[$currency2];
	
					$v = $c2_to_eur / $c_to_eur;
					$rates[$currency][$currency2] = $v;
				}
			}
			// Fix converting back to EUR rates
			foreach ((array)$rates as $currency => $_rates) {
				if ($currency == "EUR") {
					continue;
				}
				$rates[$currency]["EUR"] = 1 / $rates["EUR"][$currency];
			}
			if (main()->USE_SYSTEM_CACHE) {
				main()->put_data($cache_name, $rates);
			}
		}
		return $number * (float)str_replace(",", ".", $rates[$c_from][$c_to]);
	}
}
